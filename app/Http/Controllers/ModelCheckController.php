<?php

namespace App\Http\Controllers;

use App\Services\PrintEstimator;
use App\Support\Finishing;
use App\Support\InfillPattern;
use App\Support\MaterialColor;
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
            'supportedFormats' => ['STL', 'OBJ'],
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
            ] : null,

            'printingConfig' => $this->browserConfig(),
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

            'technologies' => $this->estimator->browserPayload(),
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
