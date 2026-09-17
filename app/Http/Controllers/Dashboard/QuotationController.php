<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\PrintTechnology;
use App\Models\QuotationItem;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Notifications\CancellationRequested;
use App\Services\ActivityLogger;
use App\Services\MeshInspector;
use App\Services\PrintEstimator;
use App\Services\SellingPriceEstimator;
use App\Support\ActivityAction;
use App\Support\ActivityModule;
use App\Support\AnalysisStatus;
use App\Support\Finishing;
use App\Support\InfillPattern;
use App\Support\MaterialCatalog;
use App\Support\MaterialColor;
use App\Support\ModelFormat;
use App\Support\Printer;
use App\Support\PrintResolution;
use App\Support\QuotationStatus;
use App\Support\UploadLimit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * "Penawaran Saya" — daftar, detail, penyuntingan, dan pembatalan.
 *
 * Penyuntingan hanya terbuka selama status masih "File Sedang Direview" —
 * tahap pertama sekaligus satu-satunya yang ditandai `editable`. Begitu admin
 * memindahkannya ke "Menunggu Pembayaran", seluruh isi penawaran menjadi read
 * only supaya berkas yang sudah dikutip harganya tidak berubah di tengah jalan.
 */
class QuotationController extends Controller
{
    public function __construct(
        private readonly PrintEstimator $estimator,
        private readonly SellingPriceEstimator $sellingPrice,
        private readonly MeshInspector $inspector,
        private readonly ActivityLogger $activity,
    ) {}

    public function index(Request $request): View
    {
        $quotations = QuotationRequest::query()
            ->ownedBy($request->user())
            ->status($request->query('status'))
            ->search($request->query('q'))
            ->withCount('items')
            ->latestFirst()
            ->paginate(10)
            ->withQueryString();

        return view('dashboard.quotations.index', [
            'quotations' => $quotations,
            'statuses' => QuotationStatus::options(),
            'filters' => [
                'status' => $request->query('status'),
                'q' => $request->query('q'),
            ],
        ]);
    }

    public function show(Request $request, QuotationRequest $quotation): View
    {
        $this->authorizeOwner($request, $quotation);

        // Pembukaan detail dicatat sekali per jam, bukan setiap kali halaman
        // disegarkan: siapa yang mengaksesnya tetap terekam tanpa memenuhi
        // tabel log dengan baris yang berulang.
        $this->activity->logOnce(
            action: ActivityAction::QUOTATION_VIEW,
            description: 'Membuka detail penawaran '.$quotation->tracking_number.'.',
            subject: $quotation,
            actor: $request->user(),
        );

        return view('dashboard.quotations.show', [
            'quotation' => $quotation->load('items', 'timelineHistories'),
        ]);
    }

    /** Formulir penyuntingan isi penawaran. */
    public function edit(Request $request, QuotationRequest $quotation): View|RedirectResponse
    {
        $this->authorizeOwner($request, $quotation);

        if (! $quotation->isEditable()) {
            return redirect()
                ->route('dashboard.quotations.show', $quotation)
                ->with('error', 'Penawaran ini sudah masuk tahap "'.$quotation->status_label.'" sehingga tidak dapat diubah lagi.');
        }

        return view('dashboard.quotations.edit', [
            'quotation' => $quotation->load('items'),
            // Dibaca lewat estimator, bukan langsung dari config: teknologi dan
            // materialnya dikelola Superadmin di Price List. Kunci array tetap
            // nama katalog yang dikirim formulir, nilainya nama yang dibaca
            // pelanggan.
            'technologies' => collect($this->estimator->technologies())
                ->map(fn (array $technology, string $code) => [
                    ...$technology,
                    'materials' => collect(MaterialCatalog::offered($code, $technology['materials'] ?? []))
                        ->map(fn (array $material, string $name) => MaterialCatalog::displayName($code, $name))
                        ->all(),
                ])
                ->all(),
            'resolutions' => PrintResolution::all(),
            'printers' => Printer::all(),
            'infillDensities' => InfillPattern::densities(),
            'infillPatterns' => InfillPattern::all(),
            'materialColors' => MaterialColor::all(),
            'finishings' => Finishing::all(),
            'hollowTechnologies' => PrintTechnology::hollowCodes(),
            'drainPositions' => config('printing.hollow.drain_hole.positions', []),
            'maxModels' => UploadLimit::maxFiles(),
            'maxFileMb' => UploadLimit::maxMegabytes(),
        ]);
    }

    /** Tambah satu model 3D ke penawaran yang masih menunggu review. */
    public function storeItem(Request $request, QuotationRequest $quotation): RedirectResponse
    {
        $this->authorizeOwner($request, $quotation);
        $this->guardEditable($quotation);

        $maxModels = UploadLimit::maxFiles();
        $current = $quotation->items()->count();

        $request->validate([
            'model' => ['required', 'file', ModelFormat::rule(), 'max:'.UploadLimit::maxKilobytes()],
        ], [
            'model.required' => 'Pilih file model yang akan ditambahkan.',
            'model.extensions' => 'File model harus berformat '.ModelFormat::label().'.',
            'model.max' => 'Ukuran file melebihi batas unggah ('.UploadLimit::maxMegabytes().' MB per file).',
        ]);

        if ($current >= $maxModels) {
            throw ValidationException::withMessages([
                'model' => "Satu penawaran maksimal memuat {$maxModels} file 3D. Hapus salah satu file lebih dulu.",
            ]);
        }

        /** @var UploadedFile $file */
        $file = $request->file('model');
        $extension = strtolower($file->getClientOriginalExtension());

        // Geometri diukur di server karena berkas ini tidak melewati viewer di
        // halaman 3D Models. Berkas CAD (STEP/STP) tidak dapat diukur di sini —
        // tesselasinya menuntut kernel CAD — jadi berkasnya tetap diterima
        // dengan angka kosong sampai engineer meninjaunya.
        $measurable = ModelFormat::isMeasurable($extension);
        $stats = null;

        if ($measurable) {
            try {
                $stats = $this->inspector->inspect($file->getRealPath(), $extension);
            } catch (RuntimeException $exception) {
                throw ValidationException::withMessages([
                    'model' => 'File tidak dapat dibaca: '.$exception->getMessage(),
                ]);
            }
        }

        $template = $quotation->items()->orderBy('position')->first();

        $path = $file->storeAs(
            'quotations/'.now()->format('Y-m'),
            Str::uuid().'.'.$extension,
            'local'
        );

        $settings = [
            'quantity' => 1,
            // Bawaan mengikuti Price List — bukan lagi daftar tetap yang
            // menganggap FDM selalu ada.
            'technology' => $template?->technology ?? (PrintTechnology::codes()[0] ?? null),
            'material' => $template?->material ?? $this->defaultMaterialFor($template?->technology),
            'printer' => $template?->printer ?? Printer::default(),
            'resolution' => $template?->resolution ?? PrintResolution::default(),
            'scale_percent' => 100,
            'infill_density' => $template?->infill_density,
            'infill_pattern' => $template?->infill_pattern ?? InfillPattern::default(),
            'material_color' => $template?->material_color ?? MaterialColor::default(),
            'finishing' => $template?->finishing ?? Finishing::default(),
            'support_enabled' => (bool) ($template?->support_enabled ?? false),
            'hollow_enabled' => false,
        ];

        $modelStats = [
            'vertices' => $stats['vertices'] ?? 0,
            'triangles' => $stats['triangles'] ?? 0,
            'dimensions' => $stats['dimensions'] ?? null,
            'volume_cm3' => $stats['volume_cm3'] ?? 0,
            'surface_area_cm2' => $stats['surface_area_cm2'] ?? 0,
            'measured_by' => $measurable ? 'server' : 'pending',
        ];

        $item = $quotation->items()->create([
            'position' => $current + 1,

            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_format' => strtoupper($extension),
            'file_size' => $file->getSize(),

            'model_stats' => $modelStats,
            // Analisis kelayakan penuh berjalan di viewer; berkas yang
            // ditambahkan dari sini ditandai perlu ditinjau engineer.
            'analysis_status' => AnalysisStatus::WARNING,
            'analysis' => [[
                'id' => 'server_measured',
                'label' => 'Ditambahkan dari dashboard',
                'status' => 'warn',
                'message' => $measurable
                    ? 'Volume dan dimensi diukur di server. Kelayakan cetak akan dipastikan engineer kami.'
                    : 'Berkas CAD '.strtoupper($extension).' belum diukur otomatis. Estimasi ditetapkan engineer kami setelah file ditinjau, atau unggah lewat halaman 3D Models agar terukur langsung di browser.',
            ]],

            'material_color' => $settings['material_color'],
            'fits_build_volume' => $this->fitsBuildVolume($stats['dimensions'] ?? null, Printer::buildVolume($settings['printer'], null)),

            ...$this->estimateAttributes($settings, $modelStats),
        ]);

        $quotation->refreshSummary();

        $this->activity->log(
            action: ActivityAction::MODEL_UPLOAD,
            description: 'Menambahkan model '.$item->file_name.' ke penawaran '.$quotation->tracking_number.'.',
            subject: $item,
            new: [
                'file_name' => $item->file_name,
                'file_format' => $item->file_format,
                'file_size' => $item->file_size,
                'quotation' => $quotation->tracking_number,
                ...$item->specSnapshot(),
            ],
            actor: $request->user(),
        );

        return back()->with('status', 'File '.$file->getClientOriginalName().' berhasil ditambahkan ke penawaran.');
    }

    /** Ubah pengaturan printing dan jumlah cetak satu model. */
    public function updateItem(Request $request, QuotationRequest $quotation, QuotationItem $item): RedirectResponse
    {
        $this->authorizeOwner($request, $quotation);
        $this->guardEditable($quotation);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'technology' => ['required', 'string', 'in:'.implode(',', PrintTechnology::codes())],
            'material' => ['required', 'string', 'max:60'],
            'printer' => ['required', 'string', 'in:'.implode(',', Printer::keys())],
            'resolution' => ['nullable', 'string', 'in:'.implode(',', PrintResolution::keys())],
            'scale_percent' => ['nullable', 'numeric', 'min:10', 'max:400'],
            'infill_density' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'infill_pattern' => ['nullable', 'string', 'in:'.implode(',', InfillPattern::keys())],
            'material_color' => ['nullable', 'string', 'in:'.implode(',', MaterialColor::keys())],
            'finishing' => ['nullable', 'string', 'in:'.implode(',', Finishing::keys())],
            'support_enabled' => ['nullable', 'boolean'],
            'hollow_enabled' => ['nullable', 'boolean'],
            'hollow_wall_thickness_mm' => ['nullable', 'numeric', 'min:0.1', 'max:50'],
            'hollow_drain_diameter_mm' => ['nullable', 'numeric', 'min:0.1', 'max:50'],
            'hollow_drain_position' => ['nullable', 'string', 'in:'.implode(',', array_keys(config('printing.hollow.drain_hole.positions', [])))],
        ], [
            'quantity.required' => 'Jumlah cetak wajib diisi.',
            'quantity.min' => 'Jumlah cetak minimal 1 unit.',
        ]);

        if (! $this->estimator->supports($validated['technology'], $validated['material'])) {
            throw ValidationException::withMessages([
                'material' => "Material {$validated['material']} tidak tersedia untuk teknologi {$validated['technology']}.",
            ]);
        }

        // Spesifikasi lama direkam sebelum disimpan; inilah sisi "Before" pada
        // halaman detail Activity Logs.
        $before = $item->specSnapshot();

        $stats = is_array($item->model_stats) ? $item->model_stats : [];
        $buildVolume = Printer::buildVolume($validated['printer'], is_array($item->build_volume) ? $item->build_volume : null);

        // Volume support hasil pengukuran di viewer hanya berlaku untuk skala
        // dan pilihan support yang sama; begitu salah satunya berubah, server
        // kembali memakai rumus simulasinya.
        $scaleUnchanged = abs((float) ($validated['scale_percent'] ?? 100) - (float) $item->scale_percent) < 0.01;
        $measuredSupport = $scaleUnchanged && $item->support_volume_cm3 !== null
            ? (float) $item->support_volume_cm3
            : null;

        $item->update([
            // Warna mengikuti material yang dipilih: resin bening hanya tersedia
            // bening, part logam hanya warna aslinya.
            'material_color' => MaterialColor::resolveForMaterial(
                $validated['material_color'] ?? $item->material_color,
                $validated['technology'],
                $validated['material'],
            ),
            'fits_build_volume' => $this->fitsBuildVolume($stats['dimensions'] ?? null, $buildVolume, (float) ($validated['scale_percent'] ?? 100)),

            ...$this->estimateAttributes([
                ...$validated,
                'support_volume_cm3' => $measuredSupport,
            ], $stats),
        ]);

        $quotation->refreshSummary();

        // Hanya pengaturan yang benar-benar berbeda yang tercatat, sehingga
        // penyimpanan tanpa perubahan tidak meninggalkan baris log kosong.
        $this->activity->logChanges(
            action: ActivityAction::SPEC_UPDATE,
            before: $before,
            after: $item->fresh()->specSnapshot(),
            description: 'Mengubah spesifikasi model '.$item->file_name.' pada penawaran '.$quotation->tracking_number.'.',
            subject: $item,
            actor: $request->user(),
            subjectLabel: $item->file_name,
        );

        return back()->with('status', "Pengaturan {$item->file_name} berhasil diperbarui.");
    }

    /** Hapus satu model dari penawaran; penawaran harus menyisakan minimal satu. */
    public function destroyItem(Request $request, QuotationRequest $quotation, QuotationItem $item): RedirectResponse
    {
        $this->authorizeOwner($request, $quotation);
        $this->guardEditable($quotation);

        if ($quotation->items()->count() <= 1) {
            return back()->with('error', 'Penawaran harus memuat minimal satu file 3D. Batalkan penawaran bila memang tidak jadi dipesan.');
        }

        $name = $item->file_name;

        // Spesifikasi model yang dihapus ikut direkam sebagai sisi "Before":
        // begitu barisnya hilang, tidak ada lagi tempat membacanya.
        $removed = [
            'file_name' => $item->file_name,
            'file_format' => $item->file_format,
            'quotation' => $quotation->tracking_number,
            ...$item->specSnapshot(),
        ];

        $item->delete();

        // Nomor urut dirapatkan agar penomoran model tetap runut.
        $quotation->items()->orderBy('position')->get()
            ->each(fn (QuotationItem $remaining, int $index) => $remaining->update(['position' => $index + 1]));

        $quotation->refreshSummary();

        $this->activity->log(
            action: ActivityAction::MODEL_DELETE,
            description: 'Menghapus model '.$name.' dari penawaran '.$quotation->tracking_number.'.',
            subject: $quotation,
            old: $removed,
            actor: $request->user(),
            module: ActivityModule::MODELS,
            subjectLabel: $name,
        );

        return back()->with('status', "File {$name} berhasil dihapus dari penawaran.");
    }

    /**
     * Pembatalan penawaran.
     *
     * Selama masih "File Sedang Direview" pembatalan langsung berlaku. Setelah
     * berkas mulai direview, yang tercatat adalah permintaan pembatalan yang
     * menunggu keputusan admin.
     */
    public function cancel(Request $request, QuotationRequest $quotation): RedirectResponse
    {
        $this->authorizeOwner($request, $quotation);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $reason = filled($validated['reason'] ?? null) ? trim($validated['reason']) : null;

        $statusBefore = $quotation->status;

        if ($quotation->canBeCancelledDirectly()) {
            $quotation->update([
                'status' => QuotationStatus::CANCELLED_BY_USER,
                'cancellation_reason' => $reason,
                'cancellation_requested_at' => now(),
                'cancellation_resolved_at' => now(),
            ]);

            $quotation->recordHistory(
                QuotationStatus::CANCELLED_BY_USER,
                $reason ?? 'Penawaran dibatalkan oleh pemiliknya sebelum masuk proses review.',
                $request->user()->name,
            );

            $this->activity->log(
                action: ActivityAction::QUOTATION_CANCEL,
                description: 'Membatalkan penawaran '.$quotation->tracking_number
                    .($reason ? ' dengan alasan: '.$reason : '.'),
                subject: $quotation,
                old: ['status' => QuotationStatus::label($statusBefore)],
                new: ['status' => QuotationStatus::label(QuotationStatus::CANCELLED_BY_USER)],
                actor: $request->user(),
            );

            return redirect()
                ->route('dashboard.quotations.show', $quotation)
                ->with('status', 'Penawaran berhasil dibatalkan.');
        }

        if (! $quotation->canRequestCancellation()) {
            return back()->with('error', 'Penawaran ini sudah tidak dapat dibatalkan.');
        }

        $quotation->update([
            'status' => QuotationStatus::CANCELLATION_REQUESTED,
            'status_before_cancellation' => $quotation->status,
            'cancellation_reason' => $reason,
            'cancellation_requested_at' => now(),
            'cancellation_resolved_at' => null,
        ]);

        $quotation->recordHistory(
            QuotationStatus::CANCELLATION_REQUESTED,
            $reason ?? 'Pemilik penawaran mengajukan pembatalan dan menunggu persetujuan admin.',
            $request->user()->name,
        );

        $this->activity->log(
            action: ActivityAction::CANCELLATION_REQUEST,
            description: 'Mengajukan pembatalan penawaran '.$quotation->tracking_number
                .($reason ? ' dengan alasan: '.$reason : '.'),
            subject: $quotation,
            old: ['status' => QuotationStatus::label($statusBefore)],
            new: ['status' => QuotationStatus::label(QuotationStatus::CANCELLATION_REQUESTED)],
            actor: $request->user(),
        );

        Notification::send(User::admins()->get(), new CancellationRequested($quotation));

        return redirect()
            ->route('dashboard.quotations.show', $quotation)
            ->with('status', 'Permintaan pembatalan terkirim. Admin akan meninjau pengajuan Anda.');
    }

    /* ------------------------------------------------------------------ */

    /** Penawaran milik akun lain tidak boleh terbaca sama sekali. */
    private function authorizeOwner(Request $request, QuotationRequest $quotation): void
    {
        abort_unless($quotation->user_id === $request->user()->id, 404);
    }

    private function guardEditable(QuotationRequest $quotation): void
    {
        if (! $quotation->isEditable()) {
            throw ValidationException::withMessages([
                'status' => 'Penawaran sudah berstatus "'.$quotation->status_label.'" sehingga isinya tidak dapat diubah lagi.',
            ]);
        }
    }

    /**
     * Hitung ulang estimasi satu model beserta kolom pengaturannya.
     *
     * Angka estimasi tidak pernah diambil dari kiriman browser: seluruhnya
     * dihitung App\Services\PrintEstimator memakai config/printing.php, persis
     * seperti saat penawaran pertama kali dikirim.
     *
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $stats
     * @return array<string, mixed>
     */
    /**
     * Material pertama yang tersedia untuk sebuah teknologi.
     *
     * Dipakai sebagai nilai bawaan saat pelanggan menambah model tanpa model
     * lain yang dapat dicontoh. Dibaca dari Price List, jadi teknologi yang
     * ditambahkan Superadmin pun punya nilai bawaan tanpa perubahan kode.
     */
    private function defaultMaterialFor(?string $technology): ?string
    {
        $technology ??= PrintTechnology::codes()[0] ?? null;

        if ($technology === null) {
            return null;
        }

        return array_key_first($this->estimator->technology($technology)['materials'] ?? []);
    }

    private function estimateAttributes(array $settings, array $stats): array
    {
        $printer = Printer::exists($settings['printer'] ?? null) ? (string) $settings['printer'] : Printer::default();
        $buildVolume = Printer::buildVolume($printer, null);

        // Volume dasar (sebelum diskalakan) menjadi masukan estimator; skala
        // diberlakukan di dalamnya.
        $baseVolume = (float) ($stats['volume_cm3'] ?? 0);
        $surfaceArea = isset($stats['surface_area_cm2']) ? (float) $stats['surface_area_cm2'] : null;
        $dimensions = is_array($stats['dimensions'] ?? null) ? $stats['dimensions'] : null;

        $estimate = $this->estimator->estimate(
            (string) $settings['technology'],
            (string) $settings['material'],
            $baseVolume,
            (int) $settings['quantity'],
            [
                'support' => filter_var($settings['support_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'support_volume_cm3' => $settings['support_volume_cm3'] ?? null,
                'dimensions' => $dimensions,
                'resolution' => $settings['resolution'] ?? null,

                'scale' => ((float) ($settings['scale_percent'] ?? 100)) / 100,
                'surface_area_cm2' => $surfaceArea,
                'infill_density' => $settings['infill_density'] ?? null,
                'infill_pattern' => $settings['infill_pattern'] ?? null,
                'finishing' => $settings['finishing'] ?? null,
                'hollow' => [
                    'enabled' => filter_var($settings['hollow_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'wall_thickness_mm' => $settings['hollow_wall_thickness_mm'] ?? null,
                    'drain_hole_diameter_mm' => $settings['hollow_drain_diameter_mm'] ?? null,
                    'drain_hole_position' => $settings['hollow_drain_position'] ?? null,
                ],

                'printer' => $printer,
                'build_volume' => $buildVolume,
            ],
        );

        // Harga ditetapkan rumus Harga Jual Price List, memakai berat dan waktu
        // yang baru saja dihitung di atas.
        $pricing = $this->sellingPrice->calculate([
            'technology' => (string) $settings['technology'],
            'material' => (string) $settings['material'],
            'printer_name' => Printer::name($printer),
            'quantity' => (int) $settings['quantity'],
            'total_weight_g' => $estimate['total_weight_g'],
            'minutes' => $estimate['total_minutes'],
            'dimensions' => $dimensions ?: null,
        ]);

        return [
            'technology' => strtoupper((string) $settings['technology']),
            'material' => (string) $settings['material'],

            'printer' => $printer,
            'printer_name' => Printer::name($printer),
            'build_volume' => $buildVolume,

            'quantity' => (int) $settings['quantity'],
            'scale_percent' => round($estimate['scale'] * 100, 2),
            'resolution' => $estimate['resolution'],
            'layer_height_mm' => $estimate['layer_height_mm'],
            'infill_density' => $estimate['infill_density'],
            'infill_pattern' => $estimate['infill_pattern'],
            'finishing' => $estimate['finishing'],
            'support_enabled' => $estimate['support_enabled'],
            'support_type' => $estimate['support_enabled'] ? config('printing.support.default_type') : null,

            'hollow_enabled' => $estimate['hollow_enabled'],
            'hollow_wall_thickness_mm' => $estimate['hollow_wall_thickness_mm'],
            'hollow_drain_diameter_mm' => $estimate['hollow_drain_diameter_mm'],
            'hollow_drain_position' => $estimate['hollow_drain_position'],

            'model_volume_cm3' => $estimate['model_volume_cm3'],
            'material_volume_cm3' => $estimate['material_volume_cm3'],
            'support_volume_cm3' => $estimate['support_volume_cm3'],
            'estimated_weight_g' => $estimate['weight_g'],
            'support_weight_g' => $estimate['support_weight_g'],
            'estimated_minutes' => $estimate['total_minutes'],
            // Harga mengikuti rumus Harga Jual Price List, sama seperti saat
            // permintaannya pertama kali dikirim; perhitungannya ikut disimpan
            // agar halaman admin tidak perlu menghitung ulang.
            'estimated_cost' => $pricing['selling_price'],
            'cost_breakdown' => $pricing,
        ];
    }

    /**
     * Apakah model masih muat di area cetak mesinnya.
     *
     * Pemeriksaan di sini sederhana — membandingkan sisi terpanjang model
     * dengan sisi terpanjang meja tanpa mencoba memutar model, jadi hasilnya
     * cukup sebagai penanda awal, bukan pengganti susunan di viewer.
     *
     * @param  array<string, mixed>|null  $dimensions
     * @param  array<string, mixed>|null  $buildVolume
     */
    private function fitsBuildVolume(?array $dimensions, ?array $buildVolume, float $scalePercent = 100): bool
    {
        if ($dimensions === null || $buildVolume === null) {
            return true;
        }

        $scale = $scalePercent / 100;

        $model = collect(['x', 'y', 'z'])->map(fn (string $axis) => (float) ($dimensions[$axis] ?? 0) * $scale)->sort()->values();
        $plate = collect(['x', 'y', 'z'])->map(fn (string $axis) => (float) ($buildVolume[$axis] ?? 0))->sort()->values();

        foreach ([0, 1, 2] as $index) {
            if ($model[$index] > $plate[$index]) {
                return false;
            }
        }

        return true;
    }
}
