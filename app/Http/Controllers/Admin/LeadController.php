<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Services\ActivityLogger;
use App\Services\GoogleAdsDealExport;
use App\Support\AdminPermission;
use App\Support\ActivityAction;
use App\Support\LeadSource;
use App\Support\LeadStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Lead Management: seluruh lead dari Form landing page layanan dan Quotation
 * dalam satu tabel, beserta sumber kedatangannya (GCLID/UTM).
 *
 * Halaman ini hanya mengelola status lead dan nilai deal. Quotation,
 * pembayaran, dan produksi tetap diurus di menunya masing-masing dan tidak
 * tersentuh perubahan apa pun di sini.
 */
class LeadController extends Controller
{
    public function index(Request $request, GoogleAdsDealExport $exporter): View
    {
        $filters = $this->filters($request);

        $leads = $this->filtered($filters)
            ->withCount('quotations')
            ->latest('created_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.leads.index', [
            'leads' => $leads,
            'filters' => $filters,
            'statuses' => LeadStatus::all(),
            'sources' => LeadSource::all(),
            'services' => $this->serviceOptions(),
            'campaigns' => Lead::query()->whereNotNull('utm_campaign')->distinct()->orderBy('utm_campaign')->pluck('utm_campaign'),
            'export' => $this->exportPreview($request, $exporter),
            'summary' => [
                'total' => Lead::count(),
                'lead' => Lead::where('status', LeadStatus::LEAD)->count(),
                'deal' => Lead::where('status', LeadStatus::DEAL)->count(),
                'deal_value' => (int) Lead::where('status', LeadStatus::DEAL)->sum('deal_value'),
            ],
        ]);
    }

    public function show(Lead $lead): View
    {
        $lead->load(['user', 'quotations' => fn ($query) => $query->withCount('items')]);

        return view('admin.leads.show', [
            'lead' => $lead,
            'statuses' => LeadStatus::all(),
            'logs' => $lead->activityLogs()->get(),
        ]);
    }

    /**
     * Unduh lampiran referensi dari formulir konsultasi. Berkasnya di disk
     * privat; selalu diunduh (bukan ditampilkan) dengan tipe yang dikunci.
     */
    public function attachment(Lead $lead, int $index): StreamedResponse
    {
        $file = $lead->attachments[$index] ?? null;

        abort_if(! is_array($file) || blank($file['path'] ?? null) || ! Storage::disk('local')->exists($file['path']), 404);

        return Storage::disk('local')->download($file['path'], $file['name'] ?? basename($file['path']), [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Unduh CSV Deal untuk impor konversi Google Ads (lihat GoogleAdsDealExport).
     * Tidak pernah mengunduh file kosong: bila tidak ada baris yang layak,
     * kembali ke daftar lead dengan penjelasannya.
     */
    public function exportDeals(Request $request, GoogleAdsDealExport $exporter, ActivityLogger $activity): Response|RedirectResponse
    {
        $range = $this->validatedExportRange($request);
        $result = $exporter->prepare($range['export_from'], $range['export_to'], $range['only_new']);
        $back = staff_route('leads.index', $range + ['export_preview' => 1]);

        if ($result['rows'] === []) {
            return redirect()->to($back)->with('export_error', $result['skipped'] === []
                ? 'Tidak ada transaksi Deal pada rentang tanggal ini. Tidak ada file yang diunduh.'
                : 'Tidak ada Deal yang dapat diekspor: '.count($result['skipped']).' Deal dilewati karena datanya belum lengkap. Lihat rinciannya di bawah. Tidak ada file yang diunduh.');
        }

        $csv = $exporter->csv($result['rows']);
        $exported = collect($result['rows'])->pluck('lead');

        $exporter->markExported($exported);

        $activity->log(
            action: ActivityAction::LEAD_EXPORT,
            description: 'Export CSV Deal Google Ads '.$range['export_from'].' s.d. '.$range['export_to'].': '
                .count($result['rows']).' deal diekspor, '.count($result['skipped']).' dilewati.',
            new: [
                'rentang' => $range['export_from'].' s.d. '.$range['export_to'].' (Asia/Jakarta)',
                'hanya_belum_diekspor' => $range['only_new'] ? 'Ya' : 'Tidak',
                'jumlah_diekspor' => count($result['rows']),
                'jumlah_dilewati' => count($result['skipped']),
                'lead_id' => $exported->pluck('id')->implode(', '),
            ],
            actor: $request->user(),
            subjectLabel: 'Export CSV Deal',
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$exporter->filename().'"',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Pratinjau ekspor di halaman daftar: jumlah Deal siap diekspor dan Deal
     * yang dilewati beserta alasannya, sebelum file diunduh.
     *
     * @return array<string, mixed>|null
     */
    private function exportPreview(Request $request, GoogleAdsDealExport $exporter): ?array
    {
        if (! $request->user()->can(AdminPermission::LEAD_EXPORT)) {
            return null;
        }

        $today = now(config('leads.google_ads_export.timezone'))->format('Y-m-d');
        $defaults = [
            'export_from' => now(config('leads.google_ads_export.timezone'))->startOfMonth()->format('Y-m-d'),
            'export_to' => $today,
            'only_new' => false,
        ];

        if (! $request->boolean('export_preview')) {
            return ['range' => $defaults, 'result' => null, 'headers' => $exporter->headers()];
        }

        $range = $this->validatedExportRange($request);

        return ['range' => $range, 'result' => $exporter->prepare($range['export_from'], $range['export_to'], $range['only_new']), 'headers' => $exporter->headers()];
    }

    /** @return array{export_from: string, export_to: string, only_new: bool} */
    private function validatedExportRange(Request $request): array
    {
        $validated = $request->validate([
            'export_from' => ['required', 'date_format:Y-m-d'],
            'export_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:export_from'],
            'only_new' => ['nullable', 'boolean'],
        ], [
            'export_from.required' => 'Pilih tanggal mulai.',
            'export_to.required' => 'Pilih tanggal akhir.',
            'export_from.date_format' => 'Tanggal mulai tidak valid.',
            'export_to.date_format' => 'Tanggal akhir tidak valid.',
            'export_to.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.',
        ]);

        return [
            'export_from' => $validated['export_from'],
            'export_to' => $validated['export_to'],
            'only_new' => $request->boolean('only_new'),
        ];
    }

    /** Ubah status lead dan/atau nilai deal. Tidak menyentuh quotation. */
    public function update(Request $request, Lead $lead, ActivityLogger $activity): RedirectResponse
    {
        // "Rp5.000.000" dan "5.000.000" diterima sebagai 5000000; selain angka ditolak.
        $raw = $request->input('deal_value');

        if (is_string($raw)) {
            $request->merge(['deal_value' => preg_replace('/^\s*Rp\s*|\s+|\.(?=\d{3}(\D|$))/i', '', $raw)]);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(LeadStatus::keys())],
            'deal_value' => ['nullable', 'integer', 'min:0', 'max:999999999999999'],
        ], [
            'status.required' => 'Pilih status lead.',
            'status.in' => 'Status lead tidak dikenal.',
            'deal_value.integer' => 'Nilai deal harus berupa angka bulat Rupiah, tanpa huruf atau desimal.',
            'deal_value.min' => 'Nilai deal tidak boleh negatif.',
            'deal_value.max' => 'Nilai deal terlalu besar.',
        ]);

        $old = ['status' => $lead->status, 'deal_value' => $lead->deal_value];

        $lead->status = $validated['status'];
        // Mengosongkan kolom berarti menghapus nilai; status Batal sendiri tidak menghapusnya.
        $lead->deal_value = $validated['deal_value'] === null ? null : (int) $validated['deal_value'];

        if (! $lead->isDirty(['status', 'deal_value'])) {
            return back()->with('status', 'Tidak ada perubahan pada lead ini.');
        }

        // Waktu konversi untuk Export CSV Deal (Google Ads): saat lead
        // berpindah MENJADI Deal. Tetap tersimpan bila status diubah lagi.
        if ($lead->isDirty('status') && $lead->status === LeadStatus::DEAL) {
            $lead->deal_at = now();
        }

        $lead->save();

        $activity->log(
            action: ActivityAction::LEAD_UPDATE,
            description: 'Memperbarui lead '.$lead->name.'.',
            subject: $lead,
            old: ['status' => LeadStatus::label($old['status']), 'deal_value' => Lead::rupiah($old['deal_value'])],
            new: ['status' => $lead->status_label, 'deal_value' => $lead->deal_value_label],
            actor: $request->user(),
        );

        return back()->with('status', 'Lead berhasil diperbarui.');
    }

    /** @return array<string, string|null> */
    private function filters(Request $request): array
    {
        $text = fn (string $key) => is_string($request->query($key)) && trim($request->query($key)) !== ''
            ? trim($request->query($key))
            : null;

        $date = function (string $key) use ($text) {
            $value = $text($key);

            if ($value === null) {
                return null;
            }

            try {
                return Carbon::createFromFormat('Y-m-d', $value)->format('Y-m-d');
            } catch (Throwable) {
                return null;
            }
        };

        $status = $text('status');
        $source = $text('source');

        return [
            'q' => $text('q'),
            'from' => $date('from'),
            'to' => $date('to'),
            'source' => array_key_exists((string) $source, LeadSource::all()) ? $source : null,
            'service' => $text('service'),
            'status' => in_array($status, LeadStatus::keys(), true) ? $status : null,
            'campaign' => $text('campaign'),
        ];
    }

    /** @param  array<string, string|null>  $filters */
    private function filtered(array $filters): Builder
    {
        return Lead::query()
            ->when($filters['q'], function (Builder $query, string $q) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $q).'%';
                $digits = preg_replace('/\D+/', '', $q);

                $query->where(function (Builder $query) use ($like, $digits) {
                    $query->where('name', 'like', $like)
                        ->orWhere('company', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('whatsapp', 'like', $like);

                    // "0812…" juga menemukan nomor yang tersimpan sebagai "62812…".
                    if (strlen($digits) >= 4) {
                        $query->orWhere('whatsapp', 'like', '%'.ltrim($digits, '0').'%');
                    }
                });
            })
            ->when($filters['from'], fn (Builder $query, string $from) => $query->where('created_at', '>=', Carbon::parse($from)->startOfDay()))
            ->when($filters['to'], fn (Builder $query, string $to) => $query->where('created_at', '<=', Carbon::parse($to)->endOfDay()))
            ->when($filters['source'], fn (Builder $query, string $source) => $query->where('source', $source))
            ->when($filters['service'], fn (Builder $query, string $service) => $query->where('service', $service))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['campaign'], fn (Builder $query, string $campaign) => $query->where('utm_campaign', $campaign));
    }

    /** @return array<int, string> layanan landing page, ditambah nilai lain yang sudah tersimpan */
    private function serviceOptions(): array
    {
        return collect(config('service-pages.pages'))
            ->pluck('wa_service')
            ->push('Lainnya')
            ->merge(Lead::query()->whereNotNull('service')->distinct()->pluck('service'))
            ->unique()
            ->values()
            ->all();
    }
}
