<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Notifications\NewLeadSubmitted;
use App\Rules\ModelFile;
use App\Services\LeadRecorder;
use App\Services\Recaptcha;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Formulir publik yang masuk ke Lead Management:
 *  - `kontak`   — Form Kontak (<x-contact-form>) di About, Services, dan
 *                 halaman industri, dengan lampiran file hingga 60 MB;
 *  - `miniatur` — formulir konsultasi /miniatur-teknik;
 *  - tanpa `form` — form singkat landing page layanan (/services/{slug}),
 *                 yang tetap membuka WhatsApp oleh halamannya sendiri.
 *
 * Form Kontak dan Miniatur diarahkan ke /terima-kasih setelah berhasil.
 * Setiap kiriman yang tersimpan juga diemailkan ke config leads.notify_email.
 */
class LeadController extends Controller
{
    public function store(Request $request, LeadRecorder $leads, Recaptcha $recaptcha): JsonResponse|RedirectResponse
    {
        $form = in_array($request->input('form'), ['kontak', 'miniatur'], true) ? $request->input('form') : 'landing';

        // Honeypot dan pengisian yang terlalu cepat: diisi bot, tidak pernah
        // oleh manusia. Dijawab "berhasil" supaya bot tidak belajar apa-apa,
        // tetapi tidak ada yang disimpan dan tidak ada event konversi.
        if (filled($request->input('website')) || $this->tooFast($request, $form)) {
            return $this->success($request, $form, recorded: false);
        }

        $data = $request->validate($this->rules($form), $this->messages($form));

        if ($form === 'kontak' && ! $recaptcha->passes($request->input('recaptcha_token'), 'contact', $request->ip())) {
            return $this->failure($request, 'Verifikasi anti-spam gagal. Muat ulang halaman lalu kirim kembali, atau hubungi kami melalui WhatsApp.', 422);
        }

        // Lampiran hanya diterima dari formulir yang menyediakannya.
        $data['attachment'] = $form === 'landing' ? null : $request->file('attachment');

        try {
            $lead = $leads->recordForm($data, $request, $request->user());
        } catch (Throwable $exception) {
            Log::error('Gagal menyimpan lead dari form: '.$exception->getMessage(), ['exception' => $exception]);

            return $this->failure($request, 'Maaf, formulir belum dapat dikirim. Silakan coba lagi atau hubungi kami melalui WhatsApp.', 500);
        }

        $this->notifyTeam($lead, $form, $data);

        return $this->success($request, $form, recorded: true, data: $data);
    }

    /** @return array<string, mixed> */
    private function rules(string $form): array
    {
        $phone = ['string', 'max:30', 'regex:/^[0-9+()\-\s.]{6,30}$/'];

        $common = [
            'name' => ['required', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:120'],
            'notes' => ['required', 'string', 'max:2000'],
            'page_url' => ['nullable', 'string', 'max:500'],
        ];

        if ($form === 'kontak') {
            $attachment = config('leads.contact.attachment');

            return $common + [
                'email' => ['required', 'email', 'max:120'],
                'whatsapp' => ['required', ...$phone],
                'service' => ['required', 'string', Rule::in(config('leads.contact.services'))],
                'attachment' => [
                    'nullable', 'file',
                    'extensions:'.implode(',', $attachment['extensions']),
                    'max:'.$attachment['max_kb'],
                    new ModelFile,
                    $this->documentType(),
                ],
            ];
        }

        if ($form === 'miniatur') {
            $attachment = config('miniatur.attachment');

            return $common + [
                'email' => ['required', 'email', 'max:120'],
                'whatsapp' => ['required', ...$phone],
                'service' => ['required', 'string', Rule::in(config('miniatur.needs'))],
                'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
                'dimensions' => ['nullable', 'string', 'max:120'],
                'attachment' => ['nullable', 'file', 'mimes:'.implode(',', $attachment['mimes']), 'max:'.$attachment['max_kb']],
            ];
        }

        return $common + [
            'email' => ['nullable', 'email', 'max:120'],
            'whatsapp' => ['nullable', ...$phone],
            'service' => ['nullable', 'string', Rule::in(collect(config('service-pages.pages'))->pluck('wa_service')->push('Lainnya')->all())],
            'notes' => ['required', 'string', 'max:1000'],
        ];
    }

    /** PDF/JPG harus benar-benar PDF/JPEG, bukan berkas lain yang diganti namanya. */
    private function documentType(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            if (! $value instanceof UploadedFile || ! $value->isValid()) {
                return;
            }

            // Tanda tangan byte awal berkas, bukan tebakan dari namanya.
            $signature = match (strtolower($value->getClientOriginalExtension())) {
                'pdf' => '%PDF-',
                'jpg', 'jpeg' => "\xFF\xD8\xFF",
                default => null,
            };

            if ($signature === null) {
                return;
            }

            $handle = @fopen((string) $value->getRealPath(), 'rb');
            $head = $handle ? (string) fread($handle, 1024) : '';

            if ($handle) {
                fclose($handle);
            }

            // PDF boleh diawali sedikit "sampah" sebelum %PDF- (spesifikasinya
            // mengizinkan hingga 1024 byte); JPEG harus tepat di awal.
            $valid = $signature === '%PDF-' ? str_contains($head, $signature) : str_starts_with($head, $signature);

            if (! $valid) {
                $fail('Isi file tidak sesuai formatnya. Unggah file STL, STEP, OBJ, 3MF, PDF, atau JPG yang sah.');
            }
        };
    }

    /** @return array<string, string> */
    private function messages(string $form): array
    {
        $maxMb = ($form === 'kontak' ? config('leads.contact.attachment.max_kb') : config('miniatur.attachment.max_kb')) / 1024;

        return [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp.regex' => 'Nomor WhatsApp hanya boleh berisi angka.',
            'service.required' => $form === 'kontak' ? 'Pilih layanan.' : 'Pilih jenis kebutuhan.',
            'service.in' => $form === 'kontak' ? 'Pilih layanan yang tersedia.' : 'Pilih jenis kebutuhan yang tersedia.',
            'quantity.required' => 'Jumlah pesanan wajib diisi.',
            'quantity.integer' => 'Jumlah pesanan harus berupa angka.',
            'quantity.min' => 'Jumlah pesanan minimal 1.',
            'notes.required' => $form === 'kontak' ? 'Kebutuhan wajib diisi.' : 'Deskripsi kebutuhan wajib diisi.',
            'attachment.extensions' => 'Lampiran harus berupa file STL, STEP, OBJ, 3MF, PDF, atau JPG.',
            'attachment.mimes' => 'Lampiran harus berupa gambar (JPG, PNG, WebP) atau PDF.',
            'attachment.max' => 'Ukuran lampiran maksimal '.$maxMb.' MB.',
            'attachment.uploaded' => 'Lampiran gagal diunggah. Ukuran maksimal '.$maxMb.' MB.',
        ];
    }

    /**
     * Form Kontak membawa waktu tampil halaman (terenkripsi). Kiriman yang
     * datang kurang dari beberapa detik sesudahnya hampir pasti bot.
     */
    private function tooFast(Request $request, string $form): bool
    {
        if ($form !== 'kontak') {
            return false;
        }

        try {
            $renderedAt = (int) Crypt::decryptString((string) $request->input('form_started'));
        } catch (Throwable) {
            return true;
        }

        return now()->timestamp - $renderedAt < (int) config('leads.min_fill_seconds');
    }

    /** @param  array<string, mixed>  $data */
    private function notifyTeam(Lead $lead, string $form, array $data): void
    {
        $email = config('leads.notify_email');

        if (blank($email)) {
            return;
        }

        $label = match ($form) {
            'kontak' => 'Form Kontak',
            'miniatur' => 'Form Konsultasi Miniatur',
            default => 'Form Landing Page Layanan',
        };

        $submission = collect($data)->except('attachment')->all();
        $submission['attachment_name'] = $data['attachment']?->getClientOriginalName();

        // Email gagal tidak menggagalkan kiriman: datanya sudah aman di admin.
        try {
            Notification::route('mail', $email)->notify(new NewLeadSubmitted($lead, $label, $submission));
        } catch (Throwable $exception) {
            Log::error('Gagal mengirim email lead: '.$exception->getMessage(), ['lead_id' => $lead->id]);
        }
    }

    /** @param  array<string, mixed>  $data  isian yang tersimpan (untuk event generate_lead) */
    private function success(Request $request, string $form, bool $recorded, array $data = []): JsonResponse|RedirectResponse
    {
        $message = 'Terima kasih, kebutuhan Anda sudah kami terima. Tim kami akan segera menghubungi Anda.';

        // Form singkat landing page tetap seperti semula: halaman itu sendiri
        // yang membuka WhatsApp.
        if ($form === 'landing') {
            return $request->expectsJson()
                ? response()->json(['message' => $message], 201)
                : back()->with('lead_sent', $message);
        }

        // Penanda sekali pakai: halaman Terima Kasih mengirim event
        // generate_lead hanya untuk kiriman yang benar-benar tersimpan.
        if ($recorded) {
            $request->session()->flash('lead_submitted', [
                'tipe' => 'kontak',
                'form' => $form,
                'form_location' => $this->formLocation($data['page_url'] ?? null),
                'service' => $data['service'] ?? null,
                // Enhanced Conversions — dibaca halaman Terima Kasih dari sesi,
                // tidak pernah lewat URL.
                'user_data' => array_filter([
                    'email' => filled($data['email'] ?? null) ? mb_strtolower(trim($data['email'])) : null,
                    'phone_number' => ($phone = Lead::normalizePhone($data['whatsapp'] ?? null)) ? '+'.$phone : null,
                ]),
            ]);
        }

        $redirect = route('thanks', ['tipe' => 'kontak']);

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'redirect' => $redirect], 201)
            : redirect()->to($redirect);
    }

    /** "services-reverse-engineering", "about", "industri-otomotif-motorsport", "home". */
    private function formLocation(?string $pageUrl): string
    {
        $path = trim((string) parse_url((string) $pageUrl, PHP_URL_PATH), '/');

        return $path === '' ? 'home' : str_replace('/', '-', $path);
    }

    private function failure(Request $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message], $status)
            : back()->withInput()->withErrors(['form' => $message]);
    }
}
