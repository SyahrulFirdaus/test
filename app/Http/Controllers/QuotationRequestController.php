<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuotationRequest;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Notifications\NewQuotationSubmitted;
use App\Services\PrintEstimator;
use App\Support\MaterialColor;
use App\Support\Printer;
use App\Support\QuotationStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class QuotationRequestController extends Controller
{
    public function __construct(private readonly PrintEstimator $estimator) {}

    /**
     * Simpan permintaan penawaran beserta seluruh berkas modelnya.
     *
     * Satu permintaan dapat memuat beberapa model sekaligus. Semuanya berbagi
     * satu Nomor Tracking, tetapi masing-masing menyimpan berkas, pengaturan
     * printing, hasil analisis, dan estimasinya sendiri di `quotation_items`.
     *
     * Estimasi tidak diambil mentah-mentah dari browser — server menghitung
     * ulang tiap model memakai App\Services\PrintEstimator sehingga angka yang
     * tercatat selalu mengikuti config/printing.php, bukan data kiriman klien.
     */
    public function store(StoreQuotationRequest $request): JsonResponse
    {
        // Setiap model dicetak pada mesinnya sendiri. Mesin di tingkat atas
        // hanya menjadi nilai bawaan bagi model yang tidak menyebutkannya.
        $fallbackPrinter = Printer::exists($request->input('printer'))
            ? (string) $request->input('printer')
            : Printer::default();

        $items = collect($request->items())
            ->values()
            ->map(fn (array $item, int $index) => $this->prepareItem(
                $item,
                $index + 1,
                Printer::exists($item['printer'] ?? null) ? (string) $item['printer'] : $fallbackPrinter,
                $item['build_volume'] ?? $request->input('build_volume'),
            ));

        $quotation = DB::transaction(function () use ($request, $items) {
            $quotation = QuotationRequest::create([
                // Penawaran selalu melekat pada akun pembuatnya sehingga muncul
                // di "Penawaran Saya" dan dapat disunting selama masih ditunggu
                // review.
                'user_id' => $request->user()->id,

                'tracking_number' => $this->generateTrackingNumber(),

                'name' => $request->string('name')->toString(),
                'email' => $request->string('email')->toString(),
                'whatsapp' => $request->string('whatsapp')->toString(),
                'company' => $request->input('company'),
                'notes' => $request->input('notes'),

                // Kolom ringkasan penawaran: berkas & pilihan produksi diisi dari
                // model pertama, sedangkan angka estimasi merupakan penjumlahan
                // seluruh model.
                ...QuotationRequest::summaryFrom($items),

                'status' => QuotationStatus::first(),
            ]);

            $items->each(fn (array $item) => $quotation->items()->create($item));

            return $quotation;
        });

        // Riwayat langsung dibuka dengan tahap pertama, sehingga halaman tracking
        // sudah punya satu entri sejak permintaan masuk.
        $quotation->recordHistory(
            QuotationStatus::first(),
            $items->count() > 1
                ? 'Permintaan penawaran berisi '.$items->count().' model berhasil dikirim.'
                : 'Permintaan penawaran berhasil dikirim.'
        );

        // Seluruh admin diberi tahu supaya penawaran baru langsung terlihat di
        // ikon lonceng dashboard mereka.
        Notification::send(User::admins()->get(), new NewQuotationSubmitted($quotation));

        return response()->json([
            'message' => 'Permintaan penawaran berhasil dikirim.',
            'tracking_number' => $quotation->tracking_number,
            'model_count' => $items->count(),
            'tracking_url' => route('tracking.show', $quotation->tracking_number),
            'document_url' => route('tracking.document', $quotation->tracking_number),
        ], 201);
    }

    /**
     * Simpan berkas satu model lalu hitung ulang estimasinya di server.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function prepareItem(array $item, int $position, string $printer, mixed $customVolume): array
    {
        /** @var UploadedFile $file */
        $file = $item['model'];
        $extension = strtolower($file->getClientOriginalExtension());

        $buildVolume = Printer::buildVolume($printer, is_array($customVolume) ? $customVolume : null);

        // Berkas disimpan pada disk privat (storage/app/private), tidak dapat
        // diakses langsung lewat URL. Pengunduhan hanya lewat dashboard admin.
        $path = $file->storeAs(
            'quotations/'.now()->format('Y-m'),
            Str::uuid().'.'.$extension,
            'local'
        );

        $modelStats = StoreQuotationRequest::decodeJson($item['model_stats'] ?? null);

        // Dimensi diambil dari statistik model untuk menghitung kebutuhan
        // support; bila tidak tersedia, pengali kelangsingan jatuh ke 1.0.
        $dimensions = collect($modelStats['dimensions'] ?? [])
            ->only(['x', 'y', 'z'])
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (float) $value)
            ->all();

        // Luas permukaan dibutuhkan untuk menghitung cangkang Hollow Model dan
        // komponen biaya finishing.
        $surfaceArea = is_numeric($modelStats['surface_area_cm2'] ?? null)
            ? (float) $modelStats['surface_area_cm2']
            : null;

        $scale = is_numeric($item['scale_percent'] ?? null) ? ((float) $item['scale_percent']) / 100 : 1.0;

        $estimate = $this->estimator->estimate(
            (string) $item['technology'],
            (string) $item['material'],
            (float) $item['model_volume_cm3'],
            (int) $item['quantity'],
            [
                'support' => filter_var($item['support_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'dimensions' => $dimensions ?: null,
                'support_volume_cm3' => $item['support_volume_cm3'] ?? null,
                'resolution' => $item['resolution'] ?? null,

                'scale' => $scale,
                'surface_area_cm2' => $surfaceArea,
                'infill_density' => $item['infill_density'] ?? null,
                'infill_pattern' => $item['infill_pattern'] ?? null,
                'finishing' => $item['finishing'] ?? null,
                'hollow' => [
                    'enabled' => filter_var($item['hollow_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'wall_thickness_mm' => $item['hollow_wall_thickness_mm'] ?? null,
                    'drain_hole_diameter_mm' => $item['hollow_drain_diameter_mm'] ?? null,
                    'drain_hole_position' => $item['hollow_drain_position'] ?? null,
                ],

                'printer' => $printer,
                'build_volume' => $buildVolume,
            ],
        );

        return [
            'position' => $position,

            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_format' => strtoupper($extension),
            'file_size' => $file->getSize(),

            'model_stats' => $modelStats,
            'analysis_status' => (string) $item['analysis_status'],
            'analysis' => StoreQuotationRequest::decodeJson($item['analysis'] ?? null),

            'technology' => strtoupper((string) $item['technology']),
            'material' => (string) $item['material'],

            // Mesin milik model ini sendiri — 1 printer, 1 build plate, 1 objek.
            'printer' => $printer,
            'printer_name' => Printer::name($printer),
            'build_volume' => $buildVolume,

            'quantity' => (int) $item['quantity'],
            'scale_percent' => round($estimate['scale'] * 100, 2),
            'resolution' => $estimate['resolution'],
            'layer_height_mm' => $estimate['layer_height_mm'],
            'infill_density' => $estimate['infill_density'],
            'infill_pattern' => $estimate['infill_pattern'],
            'support_enabled' => $estimate['support_enabled'],
            'support_type' => $estimate['support_enabled'] ? config('printing.support.default_type') : null,

            'hollow_enabled' => $estimate['hollow_enabled'],
            'hollow_wall_thickness_mm' => $estimate['hollow_wall_thickness_mm'],
            'hollow_drain_diameter_mm' => $estimate['hollow_drain_diameter_mm'],
            'hollow_drain_position' => $estimate['hollow_drain_position'],

            // Warna disesuaikan dengan materialnya: resin bening hanya tersedia
            // bening, part logam hanya warna aslinya.
            'material_color' => MaterialColor::resolveForMaterial(
                $item['material_color'] ?? null,
                (string) $item['technology'],
                (string) $item['material'],
            ),

            'finishing' => $estimate['finishing'],

            // Kesesuaian dengan area cetak diperiksa di browser pada susunan
            // yang benar-benar terlihat pengguna; nilainya dicatat agar tim
            // produksi tahu model mana yang perlu disiasati.
            'fits_build_volume' => filter_var($item['fits_build_volume'] ?? true, FILTER_VALIDATE_BOOLEAN),

            'model_volume_cm3' => $estimate['model_volume_cm3'],
            'material_volume_cm3' => $estimate['material_volume_cm3'],
            'support_volume_cm3' => $estimate['support_volume_cm3'],
            'estimated_weight_g' => $estimate['weight_g'],
            'support_weight_g' => $estimate['support_weight_g'],
            'estimated_minutes' => $estimate['total_minutes'],
            'estimated_cost' => $estimate['total_cost'],
            'cost_breakdown' => $estimate['breakdown'],
        ];
    }

    /**
     * Nomor tracking berformat QTN-YYYYMMDD-XXXXXX.
     *
     * Enam karakter acak di akhir dipakai alih-alih nomor urut: halaman tracking
     * dapat diakses tanpa login, jadi nomor yang berurutan akan membuat data
     * permintaan pelanggan lain mudah ditebak satu per satu.
     */
    private function generateTrackingNumber(): string
    {
        do {
            $number = 'QTN-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (QuotationRequest::where('tracking_number', $number)->exists());

        return $number;
    }
}
