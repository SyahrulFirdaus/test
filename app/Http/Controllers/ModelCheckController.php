<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Services\PrintEstimator;
use App\Support\Finishing;
use App\Support\InfillPattern;
use App\Support\LeadTime;
use App\Support\MaterialCatalog;
use App\Support\MaterialColor;
use App\Support\ModelFormat;
use App\Support\Printer;
use App\Support\PrintResolution;
use App\Support\UploadLimit;
use Illuminate\Contracts\View\View;

class ModelCheckController extends Controller
{
    public function __construct(private readonly PrintEstimator $estimator) {}

    /**
     * Halaman "3D Models" — Pre-Print Analyzer.
     *
     * Pembacaan, analisis, dan seluruh simulasi (build plate, skala, infill,
     * hollow, overhang, wall thickness, hingga rincian biaya) berjalan
     * sepenuhnya di browser memakai Three.js. Server hanya menyediakan
     * parameternya lewat `printingConfig`; berkas baru dikirim ketika pengguna
     * menekan "Minta Penawaran".
     */
    /**
     * Halaman viewer 3D untuk satu model.
     *
     * Model yang ditinjau tidak dikirim lewat URL: berkasnya tetap berada di
     * browser pengguna (IndexedDB) dan dipanggil oleh JavaScript memakai id
     * pada query string. Halaman ini hanya menyediakan kerangka card beserta
     * parameter estimasinya, persis seperti halaman 3D Models.
     */
    public function viewer(): View
    {
        // Pilihan produksi (printer, resolusi, infill, warna) dibaca sendiri
        // oleh komponen card, jadi halaman ini hanya perlu parameter estimasi.
        return view('pages.model-viewer', [
            'printingConfig' => $this->browserConfig(),
        ]);
    }

    public function index(): View
    {
        $maxModels = UploadLimit::maxFiles((int) config('printing.limits.max_models_per_quotation', 10));

        $user = auth()->user();

        return view('pages.model-check', [
            'supportedFormats' => ModelFormat::display(),
            'previewMaxFileSizeMb' => 60,
            'maxModels' => $maxModels,

            // Batas tebal dinding pada Hollow Model, dipakai modal Edit Specification.
            'hollow' => config('printing.hollow'),

            // Seluruh pratinjau dan simulasi tetap terbuka untuk pengunjung
            // tanpa akun; yang menuntut login hanya pengiriman penawaran.
            // Data akun dipakai mengisi formulirnya di muka.
            'quotationUser' => $user && $user->isCustomer() ? [
                'name' => $user->name,
                'email' => $user->email,
                'whatsapp' => $user->phone,

                // Nama perusahaan dibaca dari profil perusahaan yang diisi saat
                // pendaftaran Business, bukan diketik ulang tiap kali meminta
                // penawaran. Kosong untuk akun Personal — kolomnya memang tidak
                // ditampilkan bagi mereka.
                'company' => $user->isBusiness()
                    ? $user->businessProfile?->company_name
                    : null,
            ] : null,

            'quotationIsBusiness' => (bool) $user?->isCustomer() && $user->isBusiness(),

            // Buku alamat pemilik akun, untuk memilih tujuan pengiriman tanpa
            // mengetik ulang. Kosong bila belum ada alamat tersimpan — penawaran
            // tetap dapat dikirim dan alamatnya ditanyakan admin saat review.
            'quotationAddresses' => $user && $user->isCustomer()
                ? $user->addresses()->with(Address::REGION_RELATIONS)->get()
                : collect(),

            'printingConfig' => $this->browserConfig(),
        ]);
    }

    /**
     * Halaman "3D Printing Guide".
     *
     * Panduan menyiapkan model sebelum diunggah. Seluruh angkanya — batas
     * ukuran, tebal dinding minimum, sudut overhang, dan area cetak tiap
     * teknologi — dibaca dari config/printing.php yang sama dengan yang dipakai
     * analisis di browser, jadi panduannya tidak pernah bertolak belakang
     * dengan hasil pemeriksaan yang dilihat pengguna.
     */
    public function guide(): View
    {
        // Katalog yang sama dipakai modal Edit Specification, jadi daftar
        // teknologi, material, warna, keterangan, dan batas ukurannya tidak
        // pernah ditulis dua kali.
        $technologies = MaterialCatalog::technologies();

        return view('pages.printing-guide', [
            'supportedFormats' => ModelFormat::display(),
            'previewMaxFileSizeMb' => 60,
            'maxFileSizeMb' => UploadLimit::maxMegabytes(),
            'maxModels' => UploadLimit::maxFiles((int) config('printing.limits.max_models_per_quotation', 10)),

            'technologies' => $technologies,
            // Pilihan "Custom" dilewati: ukurannya diisi sendiri oleh pengguna,
            // jadi tidak mewakili mesin yang benar-benar tersedia.
            'printers' => collect(Printer::browserPayload())
                ->reject(fn (array $printer) => $printer['custom'])
                ->values()
                ->all(),
            'limits' => config('printing.limits'),
            'overhang' => config('printing.analysis.overhang'),
            'hollowWall' => config('printing.hollow.wall_thickness_mm'),
            'supportAngleDeg' => config('printing.support.visual.overhang_angle_deg'),
        ]);
    }

    /**
     * Parameter simulasi yang dibaca JavaScript.
     *
     * Halaman daftar (3D Models) dan halaman viewer memakai angka yang
     * sama persis, jadi keduanya membaca dari satu sumber di sini.
     *
     * @return array<string, mixed>
     */
    private function browserConfig(): array
    {
        return [
            'auth' => [
                'check' => auth()->check(),
                'loginUrl' => route('login'),
                'registerUrl' => route('register'),
            ],

            // Tombol "Lihat 3D" pada daftar membuka halaman ini di tab baru,
            // dengan id model dititipkan lewat query string.
            'viewerUrl' => route('models.viewer'),

            // Tombol "Learn More" pada Edit Specification menuju bagian
            // material yang sedang dipilih di halaman panduan.
            'guideUrl' => route('models.guide'),

            'technologies' => $this->estimator->browserPayload(),

            // Jam mesin ditampilkan kepada pelanggan sebagai rentang hari kerja,
            // memakai tingkatan yang sama dengan perhitungan di server.
            'leadTime' => LeadTime::browserPayload(),

            'resolutions' => PrintResolution::browserPayload(),
            'defaultResolution' => PrintResolution::default(),

            'printers' => Printer::browserPayload(),
            'defaultPrinter' => Printer::default(),
            'customPrinterKey' => config('printing.printers.custom_key', 'custom'),
            'customPrinterLimits' => config('printing.printers.custom_limits'),

            'infill' => [
                'densities' => InfillPattern::densities(),
                'defaultPattern' => InfillPattern::default(),
                'patterns' => InfillPattern::browserPayload(),
            ],

            'hollow' => [
                'technologies' => config('printing.hollow.technologies'),
                'wallThickness' => config('printing.hollow.wall_thickness_mm'),
                'drainDiameter' => config('printing.hollow.drain_hole.diameter_mm'),
                'drainCount' => config('printing.hollow.drain_hole.count'),
                'defaultDrainPosition' => config('printing.hollow.drain_hole.default_position'),
            ],

            'materialColors' => [
                'default' => MaterialColor::default(),
                'options' => MaterialColor::all(),
            ],

            'finishing' => [
                'default' => Finishing::default(),
                'options' => Finishing::browserPayload(),
            ],

            // Rincian biaya dihitung ulang di browser dengan angka yang sama
            // persis seperti yang dipakai server.
            'cost' => [
                'supportRemovalFee' => config('printing.cost.support_removal_fee'),
                'finishing' => [
                    'ratePerCm2' => config('printing.cost.finishing.rate_per_cm2'),
                    'minimum' => config('printing.cost.finishing.minimum'),
                ],
                'qualityControl' => [
                    'percent' => config('printing.cost.quality_control.percent'),
                    'minimum' => config('printing.cost.quality_control.minimum'),
                ],
                'rounding' => config('printing.cost.rounding'),
            ],

            'analysis' => [
                'overhang' => [
                    'safeDeg' => config('printing.analysis.overhang.safe_deg'),
                    'warnDeg' => config('printing.analysis.overhang.warn_deg'),
                    'colors' => config('printing.analysis.overhang.colors'),
                ],
                'wallThickness' => [
                    'colors' => config('printing.analysis.wall_thickness.colors'),
                    'maxTriangles' => config('printing.analysis.wall_thickness.max_triangles'),
                    'maxSamples' => config('printing.analysis.wall_thickness.max_samples'),
                ],
            ],

            'support' => [
                'defaultEnabled' => config('printing.support.default_enabled'),
                'infill' => config('printing.support.infill'),
                'heightInfluence' => config('printing.support.height_influence'),
                'maxAspect' => config('printing.support.max_aspect'),
                'defaultType' => config('printing.support.default_type'),
                'types' => config('printing.support.types'),
                'visual' => [
                    'overhangAngleDeg' => config('printing.support.visual.overhang_angle_deg'),
                    'gridSizeMm' => config('printing.support.visual.grid_size_mm'),
                    'maxCellsPerAxis' => config('printing.support.visual.max_cells_per_axis'),
                    'pillarShrink' => config('printing.support.visual.pillar_shrink'),
                    'minPillarHeightMm' => config('printing.support.visual.min_pillar_height_mm'),
                    'plateToleranceMm' => config('printing.support.visual.plate_tolerance_mm'),
                    'baseHeightMm' => config('printing.support.visual.base_height_mm'),
                    'baseExpand' => config('printing.support.visual.base_expand'),
                    'color' => config('printing.support.visual.color'),
                    'opacity' => config('printing.support.visual.opacity'),
                ],
            ],

            'limits' => [
                'minDimensionMm' => config('printing.limits.min_dimension_mm'),
                'warnDimensionMm' => config('printing.limits.warn_dimension_mm'),
                'maxTrianglesFullAnalysis' => config('printing.limits.max_triangles_full_analysis'),
                'uploadMaxBytes' => UploadLimit::maxBytes(),
                // Seluruh model dikirim dalam satu POST, jadi batas
                // gabungannya mengikuti post_max_size.
                'uploadMaxTotalBytes' => UploadLimit::maxTotalBytes(),
                'maxModels' => UploadLimit::maxFiles(),
            ],
        ];
    }
}
