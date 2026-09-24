<?php

namespace App\Http\Requests;

use App\Rules\ModelFile;
use App\Services\PrintEstimator;
use App\Support\AnalysisStatus;
use App\Support\Finishing;
use App\Support\InfillPattern;
use App\Support\LeadTime;
use App\Support\MaterialColor;
use App\Support\ModelFormat;
use App\Support\Printer;
use App\Support\PrintResolution;
use App\Support\UploadLimit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Batas jumlah model dalam satu permintaan penawaran. */
    public function maxItems(): int
    {
        return UploadLimit::maxFiles((int) config('printing.limits.max_models_per_quotation', 10));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Nama, email, nomor WhatsApp, dan nama perusahaan sengaja tidak
            // divalidasi di sini: keempatnya bukan lagi isian pelanggan.
            // Nilainya dibaca langsung dari akun yang sedang masuk saat
            // penawaran disimpan — nama perusahaan dari profil perusahaan milik
            // akun Business — sehingga profil yang diperbarui otomatis dipakai
            // penawaran berikutnya dan kiriman yang disusun sendiri tidak dapat
            // memalsukan identitas maupun mengaku mewakili perusahaan lain.
            'notes' => ['nullable', 'string', 'max:2000'],

            // Kecepatan pengerjaan berlaku untuk seluruh pesanan. Syarat Express
            // TIDAK diperiksa di sini melainkan di controller, karena bergantung
            // pada jumlah part dan total waktu mesin hasil estimasi server —
            // lihat App\Support\LeadTime::resolve().
            'production_speed' => ['nullable', 'string', 'in:'.implode(',', [LeadTime::STANDARD, LeadTime::EXPRESS])],

            // Alamat pengiriman dipilih dari buku alamat pemilik akun. Boleh
            // kosong: pelanggan yang belum sempat mengisi alamat tetap dapat
            // meminta penawaran, dan admin menanyakannya saat review.
            // Pemeriksaan kepemilikan mencegah alamat akun lain ikut terpakai.
            'address_id' => [
                'nullable',
                Rule::exists('addresses', 'id')->where('user_id', $this->user()?->id),
            ],

            // Sisa dari saat seluruh model masih berbagi satu mesin. Tetap
            // diterima sebagai nilai bawaan bagi model yang tidak menyebutkan
            // mesinnya sendiri.
            'printer' => ['nullable', 'string', 'in:'.implode(',', Printer::keys())],
            'build_volume' => ['nullable', 'array'],
            'build_volume.x' => ['nullable', 'numeric', 'min:1', 'max:5000'],
            'build_volume.y' => ['nullable', 'numeric', 'min:1', 'max:5000'],
            'build_volume.z' => ['nullable', 'numeric', 'min:1', 'max:5000'],

            // Satu permintaan penawaran memuat satu model atau lebih. Setiap
            // model membawa berkas, pengaturan printing, dan hasil analisisnya
            // sendiri sehingga tidak saling memengaruhi.
            'items' => ['required', 'array', 'min:1', 'max:'.$this->maxItems()],

            // Ekstensi saja dapat dipalsukan; ModelFile memeriksa isi berkasnya.
            'items.*.model' => ['required', 'file', ModelFormat::rule(), 'max:'.UploadLimit::maxKilobytes(), new ModelFile],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],

            // Satu model dicetak pada satu mesin, jadi pilihan printer dan
            // ukuran area cetaknya melekat pada modelnya masing-masing.
            'items.*.printer' => ['nullable', 'string', 'in:'.implode(',', Printer::keys())],
            'items.*.build_volume' => ['nullable', 'array'],
            'items.*.build_volume.x' => ['nullable', 'numeric', 'min:1', 'max:5000'],
            'items.*.build_volume.y' => ['nullable', 'numeric', 'min:1', 'max:5000'],
            'items.*.build_volume.z' => ['nullable', 'numeric', 'min:1', 'max:5000'],
            'items.*.technology' => ['required', 'string', 'max:10'],
            'items.*.material' => ['required', 'string', 'max:60'],
            'items.*.model_volume_cm3' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'items.*.resolution' => ['nullable', 'string', 'in:'.implode(',', PrintResolution::keys())],
            'items.*.support_enabled' => ['nullable', 'boolean'],
            // Volume support hasil pengukuran geometri di viewer; bila tidak
            // dikirim, server memakai rumus simulasi di config/printing.php.
            'items.*.support_volume_cm3' => ['nullable', 'numeric', 'min:0', 'max:1000000'],

            // Simulasi yang dijalankan di browser. Seluruhnya dihitung ulang di
            // server, jadi yang divalidasi hanya kewajaran nilainya.
            'items.*.scale_percent' => ['nullable', 'numeric', 'min:10', 'max:400'],
            'items.*.infill_density' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'items.*.infill_pattern' => ['nullable', 'string', 'in:'.implode(',', InfillPattern::keys())],
            'items.*.material_color' => ['nullable', 'string', 'in:'.implode(',', MaterialColor::keys())],
            'items.*.finishing' => ['nullable', 'string', 'in:'.implode(',', Finishing::keys())],
            'items.*.fits_build_volume' => ['nullable', 'boolean'],

            'items.*.hollow_enabled' => ['nullable', 'boolean'],
            'items.*.hollow_wall_thickness_mm' => ['nullable', 'numeric', 'min:0.1', 'max:50'],
            'items.*.hollow_drain_diameter_mm' => ['nullable', 'numeric', 'min:0.1', 'max:50'],
            'items.*.hollow_drain_position' => ['nullable', 'string', 'in:'.implode(',', array_keys(config('printing.hollow.drain_hole.positions', [])))],

            'items.*.analysis_status' => ['required', 'string', 'in:'.implode(',', AnalysisStatus::keys())],
            'items.*.analysis' => ['nullable', 'string', 'max:60000'],
            'items.*.model_stats' => ['nullable', 'string', 'max:20000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Belum ada model yang dilampirkan. Unggah minimal satu file '.ModelFormat::label().'.',
            'items.min' => 'Belum ada model yang dilampirkan. Unggah minimal satu file '.ModelFormat::label().'.',
            'items.max' => 'Satu permintaan penawaran maksimal memuat '.$this->maxItems().' model.',

            'items.*.model.required' => 'File model belum terlampir. Unggah ulang file Anda lalu coba lagi.',
            'items.*.model.extensions' => 'File model harus berformat '.ModelFormat::label().'.',
            'items.*.model.max' => 'Ukuran file melebihi batas unggah server ('.UploadLimit::maxMegabytes().' MB).',
            'items.*.quantity.required' => 'Jumlah cetak wajib diisi untuk setiap model.',
            'items.*.quantity.min' => 'Jumlah cetak minimal 1 unit.',
        ];
    }

    /**
     * Terima juga payload satu model dengan field di tingkat atas.
     *
     * Bentuk lama (`model`, `technology`, `material`, …) dipetakan menjadi
     * `items.0.*` supaya endpoint ini tetap menerima permintaan dari halaman
     * yang berkasnya sempat ter-cache di browser pengunjung.
     */
    protected function prepareForValidation(): void
    {
        if (is_array($this->input('items')) || is_array($this->file('items'))) {
            return;
        }

        $scalars = collect([
            'quantity', 'technology', 'material', 'model_volume_cm3', 'resolution',
            'support_enabled', 'support_volume_cm3', 'analysis_status', 'analysis', 'model_stats',
            'scale_percent', 'infill_density', 'infill_pattern', 'material_color', 'finishing',
            'hollow_enabled', 'hollow_wall_thickness_mm', 'hollow_drain_diameter_mm', 'hollow_drain_position',
        ])
            ->filter(fn (string $key) => $this->has($key))
            ->mapWithKeys(fn (string $key) => [$key => $this->input($key)])
            ->all();

        if ($scalars === [] && ! $this->hasFile('model')) {
            return;
        }

        $this->merge(['items' => [$scalars]]);

        if ($this->hasFile('model')) {
            $this->files->set('items', [['model' => $this->file('model')]]);

            // Berkas hasil konversi sudah sempat di-cache oleh hasFile() di
            // atas, jadi cachenya dibuang agar `items.0.model` benar-benar
            // terbaca saat validasi.
            $this->convertedFiles = null;
        }
    }

    /**
     * Pastikan kombinasi teknologi dan material tiap model memang tersedia di
     * config/printing.php, bukan sekadar teks bebas kiriman klien.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $estimator = app(PrintEstimator::class);

                foreach ((array) $this->input('items', []) as $index => $item) {
                    $technology = (string) ($item['technology'] ?? '');
                    $material = (string) ($item['material'] ?? '');

                    if ($technology === '' || $material === '') {
                        continue;
                    }

                    if ($estimator->technology($technology) === null) {
                        $validator->errors()->add("items.{$index}.technology", 'Teknologi cetak tidak dikenal.');

                        continue;
                    }

                    if (! $estimator->supports($technology, $material)) {
                        $validator->errors()->add(
                            "items.{$index}.material",
                            "Material {$material} tidak tersedia untuk teknologi {$technology}."
                        );
                    }
                }
            },
        ];
    }

    /**
     * Model-model yang dikirim, sudah tergabung antara field teks dan berkasnya.
     *
     * @return array<int, array<string, mixed>>
     */
    public function items(): array
    {
        return array_values($this->validated('items'));
    }

    /** Data JSON dari browser di-decode dengan aman, gagal decode dianggap kosong. */
    public static function decodeJson(mixed $value): array
    {
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
