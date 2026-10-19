<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Support\ActivityAction;
use App\Support\LeadSource;
use App\Support\LeadStatus;
use App\Support\LeadTracking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Mencatat lead dari Form landing page layanan dan dari Quotation.
 *
 * Aturan penggabungan (agar tidak ada lead ganda):
 *  - Kontak dianggap sama bila akun, email, atau nomor WhatsApp-nya sama.
 *  - Selama lead kontak itu masih berjalan (Lead/Quotation), form dan
 *    quotation berikutnya masuk ke lead yang sama. Setelah Deal atau Batal,
 *    kiriman berikutnya membuka lead baru.
 *  - Setiap quotation tetap tercatat sendiri dan tertaut ke lead-nya.
 *
 * Data tracking (GCLID/UTM) adalah first touch: diisi sekali saat lead dibuat
 * dan tidak ditimpa sesudahnya.
 */
class LeadRecorder
{
    public function __construct(private readonly ActivityLogger $activity) {}

    /**
     * Tautkan quotation baru ke lead kontaknya, atau buat lead baru.
     *
     * Tidak pernah menggagalkan quotation: kesalahan apa pun hanya dicatat di
     * log aplikasi.
     */
    public function recordQuotation(QuotationRequest $quotation, Request $request): ?Lead
    {
        try {
            return DB::transaction(function () use ($quotation, $request) {
                $tracking = LeadTracking::fromRequest($request);
                $email = $this->email($quotation->email);
                $phone = Lead::normalizePhone($quotation->whatsapp);

                $lead = $this->findOpen($quotation->user_id, $email, $phone);

                if ($lead === null) {
                    $lead = Lead::create([
                        'source' => LeadSource::QUOTATION,
                        'user_id' => $quotation->user_id,
                        'name' => $quotation->name,
                        'company' => $quotation->company ?: null,
                        'email' => $email,
                        'whatsapp' => $phone,
                        'service' => '3D Printing',
                        'source_url' => $this->sourceUrl($request) ?? route('models'),
                        'status' => LeadStatus::QUOTATION,
                        ...$this->trackingAttributes($tracking),
                    ]);

                    $this->activity->log(
                        action: ActivityAction::LEAD_CREATE,
                        description: 'Lead baru dari quotation '.$quotation->tracking_number.'.',
                        subject: $lead,
                        new: ['source' => LeadSource::label($lead->source), 'status' => $lead->status_label],
                        actor: $quotation->user,
                    );
                } else {
                    $this->complete($lead, [
                        'user_id' => $quotation->user_id,
                        'company' => $quotation->company ?: null,
                        'email' => $email,
                        'whatsapp' => $phone,
                    ], $tracking);

                    // Satu-satunya perubahan status otomatis: lead yang baru
                    // bertanya kini sudah mengirim quotation.
                    if ($lead->status === LeadStatus::LEAD) {
                        $lead->status = LeadStatus::QUOTATION;
                        $lead->save();

                        $this->activity->log(
                            action: ActivityAction::LEAD_UPDATE,
                            description: 'Status lead berubah otomatis karena quotation '.$quotation->tracking_number.' dikirim.',
                            subject: $lead,
                            old: ['status' => LeadStatus::label(LeadStatus::LEAD)],
                            new: ['status' => LeadStatus::label(LeadStatus::QUOTATION)],
                            actor: $quotation->user,
                        );
                    }
                }

                // Query langsung: tanpa event model (observer WhatsApp) dan tanpa
                // mengubah updated_at quotation.
                DB::table('quotation_requests')->where('id', $quotation->getKey())->update(['lead_id' => $lead->getKey()]);
                $quotation->setAttribute('lead_id', $lead->getKey());
                $quotation->syncOriginalAttribute('lead_id');

                return $lead;
            });
        } catch (Throwable $exception) {
            Log::error('Gagal mencatat lead dari quotation: '.$exception->getMessage(), [
                'quotation_id' => $quotation->getKey(),
                'exception' => $exception,
            ]);

            return null;
        }
    }

    /**
     * Simpan kiriman form landing page layanan.
     *
     * @param  array{name: string, company?: ?string, email?: ?string, whatsapp?: ?string, service?: ?string, quantity?: ?int, dimensions?: ?string, notes: string, page_url?: ?string, attachment?: ?UploadedFile}  $data
     */
    public function recordForm(array $data, Request $request, ?User $user = null): Lead
    {
        $attachment = $this->storeAttachment($data['attachment'] ?? null);

        try {
            return $this->saveForm($data, $request, $user, $attachment);
        } catch (Throwable $exception) {
            // Berkas yang terlanjur tersimpan tidak dibiarkan menjadi yatim.
            if ($attachment !== null) {
                Storage::disk('local')->delete($attachment['path']);
            }

            throw $exception;
        }
    }

    /** @param  array<string, mixed>|null  $attachment */
    private function saveForm(array $data, Request $request, ?User $user, ?array $attachment): Lead
    {
        return DB::transaction(function () use ($data, $request, $user, $attachment) {
            $email = $this->email($data['email'] ?? null);
            $phone = Lead::normalizePhone($data['whatsapp'] ?? null);
            $notes = trim((string) $data['notes']);
            $service = $data['service'] ?? null;
            $quantity = isset($data['quantity']) && $data['quantity'] !== '' ? (int) $data['quantity'] : null;
            $dimensions = filled($data['dimensions'] ?? null) ? trim($data['dimensions']) : null;

            $lead = $this->findOpen($user?->getKey(), $email, $phone)
                ?? $this->findRecentDuplicate($data['name'], $notes);

            if ($lead !== null) {
                $this->complete($lead, [
                    'user_id' => $user?->getKey(),
                    'company' => $data['company'] ?? null,
                    'email' => $email,
                    'whatsapp' => $phone,
                    'service' => $service,
                    'quantity' => $quantity,
                    'dimensions' => $dimensions,
                ], LeadTracking::fromRequest($request));

                // Kebutuhan baru tidak dibuang: ditambahkan di bawah catatan lama.
                if ($notes !== '' && ! str_contains((string) $lead->notes, $notes)) {
                    $details = collect([
                        $service,
                        $quantity !== null ? $quantity.' pcs' : null,
                        $dimensions,
                    ])->filter()->implode(' · ');

                    $lead->notes = trim($lead->notes."\n\n".'['.now()->format('d/m/Y H:i').($details !== '' ? ' · '.$details : '').'] '.$notes);
                }

                if ($attachment !== null) {
                    $lead->attachments = [...($lead->attachments ?? []), $attachment];
                }

                if ($lead->isDirty()) {
                    $lead->save();
                }

                return $lead;
            }

            $lead = Lead::create([
                'source' => LeadSource::FORM,
                'user_id' => $user?->getKey(),
                'name' => $data['name'],
                'company' => $data['company'] ?? null,
                'email' => $email,
                'whatsapp' => $phone,
                'service' => $service,
                'quantity' => $quantity,
                'dimensions' => $dimensions,
                'notes' => $notes,
                'attachments' => $attachment !== null ? [$attachment] : null,
                'source_url' => $this->pageUrl($data['page_url'] ?? null, $request) ?? $this->sourceUrl($request),
                'status' => LeadStatus::LEAD,
                ...$this->trackingAttributes(LeadTracking::fromRequest($request)),
            ]);

            $this->activity->log(
                action: ActivityAction::LEAD_CREATE,
                description: 'Lead baru dari form '.($service ?: 'landing page').'.',
                subject: $lead,
                new: ['source' => LeadSource::label($lead->source), 'status' => $lead->status_label],
                actor: $user,
            );

            return $lead;
        });
    }

    /**
     * Simpan lampiran referensi di disk privat (tidak dapat dibuka publik).
     *
     * @return array{path: string, name: string, size: int, uploaded_at: string}|null
     */
    private function storeAttachment(?UploadedFile $file): ?array
    {
        if ($file === null) {
            return null;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: (string) $file->extension());
        $path = $file->storeAs('leads/'.now()->format('Y/m'), Str::random(40).'.'.$extension, 'local');

        return [
            'path' => $path,
            'name' => Str::limit(basename($file->getClientOriginalName()), 150, ''),
            'size' => (int) $file->getSize(),
            'uploaded_at' => now()->toIso8601String(),
        ];
    }

    private function findOpen(?int $userId, ?string $email, ?string $phone): ?Lead
    {
        if ($userId === null && $email === null && $phone === null) {
            return null;
        }

        return Lead::query()
            ->open()
            ->where(function (Builder $query) use ($userId, $email, $phone) {
                if ($userId !== null) {
                    $query->orWhere('user_id', $userId);
                }

                if ($email !== null) {
                    $query->orWhere('email', $email);
                }

                if ($phone !== null) {
                    $query->orWhere('whatsapp', $phone);
                }
            })
            ->oldest('id')
            ->lockForUpdate()
            ->first();
    }

    /** Form yang terkirim dua kali (klik ganda) tanpa email/WhatsApp. */
    private function findRecentDuplicate(string $name, string $notes): ?Lead
    {
        return Lead::query()
            ->open()
            ->where('source', LeadSource::FORM)
            ->where('name', $name)
            ->where('notes', $notes)
            ->where('created_at', '>=', now()->subMinutes(30))
            ->first();
    }

    /**
     * Lengkapi kolom kontak yang masih kosong; yang sudah terisi tidak ditimpa.
     *
     * @param  array<string, mixed>  $contact
     * @param  array<string, string>  $tracking
     */
    private function complete(Lead $lead, array $contact, array $tracking): void
    {
        foreach ($contact as $key => $value) {
            if (blank($lead->{$key}) && filled($value)) {
                $lead->{$key} = $value;
            }
        }

        // Lead tanpa data sumber sama sekali boleh menerima first touch-nya
        // sekarang; lead yang sudah punya tidak pernah ditimpa.
        if (! $this->hasTracking($lead) && $tracking !== []) {
            $lead->fill($this->trackingAttributes($tracking));
        }

        if ($lead->isDirty()) {
            $lead->save();
        }
    }

    private function hasTracking(Lead $lead): bool
    {
        return LeadTracking::hasAttribution($lead->only(LeadTracking::PARAMETERS))
            || filled($lead->landing_page)
            || filled($lead->referrer);
    }

    /**
     * @param  array<string, string>  $tracking
     * @return array<string, mixed>
     */
    private function trackingAttributes(array $tracking): array
    {
        $attributes = array_intersect_key($tracking, array_flip([...LeadTracking::PARAMETERS, 'landing_page', 'referrer']));

        if (isset($tracking['first_touch_at'])) {
            try {
                $attributes['first_touch_at'] = Carbon::parse($tracking['first_touch_at']);
            } catch (Throwable) {
                // Nilai cookie rusak: dibiarkan kosong.
            }
        }

        return $attributes;
    }

    private function email(?string $email): ?string
    {
        $email = mb_strtolower(trim((string) $email));

        return $email === '' ? null : $email;
    }

    /** Halaman tempat form/quotation dikirim (header Referer), hanya dari situs sendiri. */
    private function sourceUrl(Request $request): ?string
    {
        return $this->pageUrl($request->headers->get('referer'), $request);
    }

    private function pageUrl(?string $url, Request $request): ?string
    {
        if (blank($url) || parse_url($url, PHP_URL_HOST) !== $request->getHost()) {
            return null;
        }

        return Str::limit($url, 500, '');
    }
}
