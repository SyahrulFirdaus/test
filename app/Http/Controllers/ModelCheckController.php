<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\PrintTechnology;
use App\Services\PrintEstimator;
use App\Services\SellingPriceEstimator;
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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ModelCheckController extends Controller
{
    public function __construct(
        private readonly PrintEstimator $estimator,
        private readonly SellingPriceEstimator $sellingPrice,
    ) {}

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
     * Pola id model di browser: UUID dari crypto.randomUUID(), atau cadangan
     * "m-<waktu>-<acak>" (lihat resources/js/modules/model-store.js).
     */
    public const MODEL_ID_PATTERN = '[A-Za-z0-9][A-Za-z0-9-]{0,63}';

    /**
     * Halaman 3D Viewer satu model: /3d-models/{model}/viewer.
     *
     * Hanya pratinjau — tanpa spesifikasi cetak, berat, maupun harga. Berkasnya
     * tidak diambil dari server: `{model}` adalah id berkas yang tersimpan di
     * browser pengunjung (IndexedDB) sejak diunggah, dan halaman ini hanya
     * meneruskan id itu ke JavaScript. Pola id dibatasi di route, jadi tidak ada
     * path berkas maupun teks bebas yang pernah sampai ke halaman.
     */
    public function show(string $model): View
    {
        return view('pages.model-viewer', [
            'modelId' => $model,
        ]);
    }

    /**
     * Alamat lama /3d-models/viewer?model={id} — diteruskan ke alamat barunya
     * supaya bookmark dan tautan lama tetap sampai.
     */
    public function legacyViewer(Request $request): RedirectResponse
    {
        $model = (string) $request->query('model', '');

        return preg_match('/^'.self::MODEL_ID_PATTERN.'$/', $model) === 1
            ? redirect()->route('models.viewer.show', $model)
            : redirect()->route('models');
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

            // Tombol "Lihat 3D" pada daftar berpindah ke halaman viewer di tab
            // yang sama. JavaScript mengganti penanda MODELID dengan id model.
            'viewerUrl' => route('models.viewer.show', ['model' => 'MODELID']),

            // Tombol "Learn More" pada Edit Specification menuju bagian
            // material yang sedang dipilih di halaman panduan.
            'guideUrl' => route('models.guide'),

            // Harga material per gram dikirim sebagai tarif JUAL, bukan
            // komponen HPP — lihat SellingPriceEstimator::publicTechnologies().
            'technologies' => $this->sellingPrice->publicTechnologies($this->estimator->browserPayload()),

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
                'technologies' => PrintTechnology::hollowCodes(),
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

            // Yang dihitung ulang di browser hanyalah Harga Jual, dan satu-satunya
            // komponennya yang bergantung ukuran adalah Basic Fee. Komponen biaya
            // lama (support removal, finishing per cm2, quality control,
            // pembulatan) tidak lagi dikirim karena tidak lagi menentukan harga
            // apa pun — lihat App\Services\SellingPriceEstimator.
            'cost' => [
                'basicFee' => ['tiers' => \App\Support\BasicFee::browserPayload()],
            ],

            // Tarif JUAL dari Price List (Risk & Profit sudah dilebur) supaya
            // harga yang dilihat pelanggan sama dengan hasil hitung server,
            // tanpa membuka HPP, Machine Cost, Risk %, maupun Profit %.
            'pricing' => $this->sellingPrice->browserPayload(),

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
