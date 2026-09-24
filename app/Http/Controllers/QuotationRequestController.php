<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuotationRequest;
use App\Models\Address;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Notifications\NewQuotationSubmitted;
use App\Services\ActivityLogger;
use App\Services\MeshInspector;
use App\Services\PrintEstimator;
use App\Services\SellingPriceEstimator;
use App\Support\ActivityAction;
use App\Support\LeadTime;
use App\Support\MaterialColor;
use App\Support\ModelFormat;
use App\Support\Printer;
use App\Support\QuotationStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use RuntimeException;

class QuotationRequestController extends Controller
{
    public function __construct(
        private readonly PrintEstimator $estimator,
        private readonly SellingPriceEstimator $sellingPrice,
        private readonly ActivityLogger $activity,
        private readonly MeshInspector $inspector,
    ) {}

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

        /*
         * Kecepatan pengerjaan ditetapkan SETELAH seluruh model diestimasi.
         *
         * Syarat Express — satu part dan total waktu mesin di bawah 18 jam —
         * berlaku atas keseluruhan pesanan, jadi waktunya baru diketahui begitu
         * tiap model selesai dihitung. Express yang diminta tanpa memenuhi
         * syaratnya diturunkan menjadi Standard di sini, bukan dipercaya dari
         * kiriman browser.
         *
         * Express hanya menaikkan harga printing, jadi rinciannya cukup
         * ditempeli pengalinya — tidak ada yang perlu diestimasi ulang.
         */
        $speed = LeadTime::resolve(
            $request->input('production_speed'),
            $items->count(),
            (float) $items->sum(fn (array $item) => (float) ($item['estimated_minutes'] ?? 0)),
            $items->contains(fn (array $item) => (bool) ($item['cost_breakdown']['manual_pricing'] ?? false)),
        );

        if ($speed === LeadTime::EXPRESS) {
            $items = $items->map(function (array $item) use ($speed) {
                $item['cost_breakdown'] = $this->sellingPrice->withProductionSpeed($item['cost_breakdown'], $speed);
                $item['estimated_cost'] = $item['cost_breakdown']['selling_price'];

                return $item;
            });
        }

        // Alamat pengiriman disalin isinya, bukan sekadar ditunjuk: pelanggan
        // boleh menyunting atau menghapus alamatnya kapan saja, sedangkan
        // tujuan pengiriman penawaran yang sudah terkirim tidak boleh ikut
        // berubah. Alamat utama dipakai bila tidak ada yang dipilih.
        $address = $request->user()
            ->addresses()
            ->with(Address::REGION_RELATIONS)
            ->when(
                $request->filled('address_id'),
                fn ($query) => $query->whereKey($request->input('address_id')),
                fn ($query) => $query->where('is_default', true),
            )
            ->first();

        $quotation = DB::transaction(function () use ($request, $items, $address, $speed) {
            $quotation = QuotationRequest::create([
                // Penawaran selalu melekat pada akun pembuatnya sehingga muncul
                // di "Penawaran Saya" dan dapat disunting selama masih ditunggu
                // review.
                'user_id' => $request->user()->id,

                'tracking_number' => $this->generateTrackingNumber(),

                // Identitas pemohon dibaca dari akunnya, bukan dari kiriman
                // formulir. Penawaran tetap menyimpan salinannya sebagai
                // catatan pada saat permintaan dibuat — itulah yang dilihat
                // admin dan tercetak pada dokumen penawaran.
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'whatsapp' => (string) $request->user()->phone,
                // Nama perusahaan tidak ikut dikirim formulir: untuk akun
                // Business dibaca dari profil perusahaannya sehingga selalu
                // sesuai dengan perusahaan pemilik akun, dan akun Personal
                // memang tidak memilikinya.
                'company' => $request->user()->isBusiness()
                    ? $request->user()->businessProfile?->company_name
                    : null,
                'notes' => $request->input('notes'),

                'address_id' => $address?->id,
                'shipping_address' => $address?->toSnapshot(),

                // Kolom ringkasan penawaran: berkas & pilihan produksi diisi dari
                // model pertama, sedangkan angka estimasi merupakan penjumlahan
                // seluruh model.
                ...QuotationRequest::summaryFrom($items),

                'production_speed' => $speed,
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

        $this->activity->log(
            action: ActivityAction::QUOTATION_CREATE,
            description: 'Membuat penawaran '.$quotation->tracking_number.' berisi '.$items->count().' model.',
            subject: $quotation,
            new: [
                'tracking_number' => $quotation->tracking_number,
                'model_count' => $items->count(),
                'status' => QuotationStatus::label($quotation->status),
                'estimated_cost' => $quotation->estimated_cost,
            ],
            actor: $request->user(),
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

        $volume = (float) $item['model_volume_cm3'];

        /*
        | Geometri kiriman browser tidak dipercaya begitu saja: volume, luas
        | permukaan, dan ukuran menentukan harga, dan semuanya dapat diketik
        | sendiri lewat Postman. Format mesh (STL/OBJ/3MF) diukur ulang di sini
        | dan hasil server yang dipakai.
        |
        | Dimensi browser tetap dipakai bila masuk akal, karena browser mengukur
        | model yang SUDAH diputar pengguna. Batas bawahnya: diagonal kotak hasil
        | putaran apa pun tidak mungkin lebih pendek dari sisi terpanjang kotak
        | asli. Dimensi yang lebih kecil dari itu diganti ukuran server.
        |
        | STEP/STP tidak dapat diukur server (butuh kernel CAD); angkanya tetap
        | dari browser, ditandai `measured_by = client`, dan harga penawarannya
        | memang ditinjau admin sebelum tagihan dikirim.
        */
        $measured = $this->measure($file, $extension);

        if ($measured !== null) {
            // Volume dan luas yang dikirim adalah ukuran ASLI (skala diterapkan
            // estimator), sedangkan dimensi browser sudah terskalakan.
            $volume = $measured['volume_cm3'];
            $surfaceArea = $measured['surface_area_cm2'];

            $scaleFactor = is_numeric($item['scale_percent'] ?? null) ? ((float) $item['scale_percent']) / 100 : 1.0;
            $scaledServer = array_map(fn (float $side) => round($side * $scaleFactor, 3), $measured['dimensions']);

            if (! $this->plausibleDimensions($dimensions, $scaledServer)) {
                $dimensions = $scaledServer;
            }

            $modelStats = [
                ...$modelStats,
                'volume_cm3' => $volume,
                'surface_area_cm2' => $surfaceArea,
                'dimensions' => $dimensions,
                'measured_by' => 'server',
            ];
        } else {
            $modelStats['measured_by'] = 'client';
        }

        $scale = is_numeric($item['scale_percent'] ?? null) ? ((float) $item['scale_percent']) / 100 : 1.0;

        $estimate = $this->estimator->estimate(
            (string) $item['technology'],
            (string) $item['material'],
            $volume,
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

        $pricing = $this->sellingPrice->calculate([
            'technology' => (string) $item['technology'],
            'material' => (string) $item['material'],
            // Kunci printer (bukan namanya): Machine Cost dicari lewat
            // machine_costs.printer_key, sama seperti Calculator di browser.
            'printer' => $printer,
            'quantity' => (int) $item['quantity'],
            'total_weight_g' => $estimate['total_weight_g'],
            'minutes' => $estimate['total_minutes'],
            'dimensions' => $dimensions ?: null,
        ]);

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

            // Harga penawaran ditetapkan rumus Harga Jual Price List, dan
            // seluruh perhitungannya ikut disimpan — bukan hanya hasilnya —
            // supaya halaman admin dapat menjelaskan asal angkanya tanpa perlu
            // menghitung ulang dengan parameter yang mungkin sudah berubah.
            'estimated_cost' => $pricing['selling_price'],
            'cost_breakdown' => $pricing,
        ];
    }

    /**
     * Ukur geometri berkas mesh di server.
     *
     * @return array{volume_cm3: float, surface_area_cm2: float, dimensions: array<string, float>}|null
     *         null bila formatnya tidak dapat diukur atau berkasnya tidak memuat geometri
     */
    private function measure(UploadedFile $file, string $extension): ?array
    {
        if (! ModelFormat::isMeasurable($extension)) {
            return null;
        }

        try {
            $stats = $this->inspector->inspect((string) $file->getRealPath(), $extension);
        } catch (RuntimeException) {
            return null;
        }

        if ((int) ($stats['triangles'] ?? 0) <= 0 || ! is_array($stats['dimensions'] ?? null)) {
            return null;
        }

        return [
            'volume_cm3' => (float) ($stats['volume_cm3'] ?? 0),
            'surface_area_cm2' => (float) ($stats['surface_area_cm2'] ?? 0),
            'dimensions' => collect($stats['dimensions'])
                ->only(['x', 'y', 'z'])
                ->map(fn ($value) => (float) $value)
                ->all(),
        ];
    }

    /**
     * Dimensi browser masih mungkin berasal dari model yang sama setelah diputar.
     *
     * @param  array<string, float>  $client
     * @param  array<string, float>  $server
     */
    private function plausibleDimensions(array $client, array $server): bool
    {
        if (count($client) !== 3) {
            return false;
        }

        $diagonal = sqrt(array_sum(array_map(fn (float $side) => $side ** 2, $client)));

        return $diagonal >= max($server) * 0.99;
    }

    /** Nomor tracking; aturannya ada pada App\Models\QuotationRequest. */
    private function generateTrackingNumber(): string
    {
        return QuotationRequest::generateTrackingNumber();
    }
}
