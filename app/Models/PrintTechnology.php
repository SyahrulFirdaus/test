<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Satu teknologi cetak beserta seluruh parameter produksinya.
 *
 * Inilah pengganti blok `technologies` di config/printing.php. Superadmin
 * mengelolanya lewat Price List, dan seluruh sistem membacanya dari sini: tab
 * Price List, pilihan Technology pada Edit Specification, validasi penawaran,
 * estimasi berat/waktu, sampai penetapan Harga Jual.
 *
 * JANGAN tertukar dengan App\Models\Technology — yang itu adalah isi halaman
 * "Technologies" pada website publik (slug, gambar, tagline, keunggulan) dan
 * tidak menyentuh perhitungan apa pun. Yang ini parameter produksinya.
 *
 * `code` adalah kunci yang tersimpan pada `quotation_items.technology`. Karena
 * penawaran lama menunjuk ke sana, kodenya tidak boleh diubah setelah dipakai;
 * formulir penyuntingan mengunci kolomnya.
 */
class PrintTechnology extends Model
{
    protected $table = 'print_technologies';

    protected $fillable = [
        'code',
        'name',
        'family',
        'description',
        'build_volume_x',
        'build_volume_y',
        'build_volume_z',
        'shell_ratio',
        'default_infill',
        'infill_note',
        'min_wall_thickness_mm',
        'support_volume_factor',
        'layer_height_min',
        'layer_height_max',
        'throughput_cm3_per_hour',
        'setup_hours',
        'setup_fee',
        'machine_rate_per_hour',
        'allows_hollow',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'build_volume_x' => 'integer',
            'build_volume_y' => 'integer',
            'build_volume_z' => 'integer',
            'shell_ratio' => 'float',
            'default_infill' => 'float',
            'min_wall_thickness_mm' => 'float',
            'support_volume_factor' => 'float',
            'layer_height_min' => 'float',
            'layer_height_max' => 'float',
            'throughput_cm3_per_hour' => 'float',
            'setup_hours' => 'float',
            'setup_fee' => 'float',
            'machine_rate_per_hour' => 'float',
            'allows_hollow' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Material yang dijual untuk teknologi ini, urut menurut namanya.
     *
     * Mesinnya ikut dimuat karena batas ukuran cetak tiap material diturunkan
     * dari volume cetak mesin itu — tanpa ini, menyusun katalog berarti satu
     * kueri tambahan per material.
     */
    public function materials(): HasMany
    {
        return $this->hasMany(PrintMaterial::class)->with('machine')->orderBy('material');
    }

    /** Rumus & parameter simulasi Harga Jual miliknya pada tab Harga. */
    public function pricingFormula(): ?PricingFormula
    {
        return PricingFormula::where('technology', $this->code)->first();
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('code');
    }

    /**
     * Kunci tab pada halaman Price List, mis. "fdm".
     *
     * Diturunkan dari kodenya supaya tab lama (fdm/sla) tetap beralamat
     * sama seperti sebelum teknologi dapat ditambah sendiri.
     */
    public function tabKey(): string
    {
        return strtolower($this->code);
    }

    /** Sudah dipakai penawaran, sehingga kodenya tidak boleh berubah lagi. */
    public function isInUse(): bool
    {
        return QuotationItem::where('technology', $this->code)->exists();
    }

    /* ----------------------------------------------- daftar yang diingat --- */

    /** @var Collection<string, self>|null */
    private static ?Collection $cache = null;

    protected static function booted(): void
    {
        // Kode selalu huruf kapital: dibandingkan apa adanya di banyak tempat,
        // termasuk `strtoupper()` pada estimator.
        static::saving(function (self $technology) {
            $technology->code = strtoupper(trim((string) $technology->code));
        });

        // Daftar yang diingat harus gugur begitu isinya berubah, supaya
        // teknologi baru langsung berlaku pada permintaan yang sama.
        static::saved(fn () => static::forgetCache());
        static::deleted(fn () => static::forgetCache());

        // Tab Harga menampilkan satu baris rumus per teknologi. Barisnya dibuat
        // di sini, bukan dituntut diisi Superadmin lebih dulu, supaya teknologi
        // baru langsung punya parameter yang dapat disunting dan tab Harga
        // tidak pernah menemukan teknologi tanpa baris.
        static::created(function (self $technology) {
            PricingFormula::firstOrCreate(
                ['technology' => $technology->code],
                PricingFormula::defaultsFor($technology),
            );
        });
    }

    public static function forgetCache(): void
    {
        static::$cache = null;
    }

    /**
     * Seluruh teknologi, diingat selama satu permintaan.
     *
     * Dibaca penyedia parameter tingkat rendah — support factor, rentang layer
     * height, daftar kode untuk validasi — yang dipanggil berkali-kali dalam
     * satu alur perhitungan.
     *
     * @return Collection<string, self>
     */
    public static function cached(): Collection
    {
        return static::$cache ??= static::ordered()->get()->keyBy('code');
    }

    public static function findByCode(?string $code): ?self
    {
        return $code === null ? null : static::cached()->get(strtoupper($code));
    }

    public static function idFor(string $code): ?int
    {
        return static::findByCode($code)?->getKey();
    }

    /** @return array<int, string> kode seluruh teknologi, untuk validasi & tab */
    public static function codes(): array
    {
        return static::cached()->keys()->all();
    }

    /** @return array<int, string> teknologi yang menyediakan Hollow Model */
    public static function hollowCodes(): array
    {
        return static::cached()
            ->filter(fn (self $technology) => $technology->allows_hollow)
            ->keys()
            ->values()
            ->all();
    }

    public static function exists(?string $code): bool
    {
        return static::findByCode($code) !== null;
    }

    /* -------------------------------------------------------- estimator --- */

    /**
     * Bentuk yang selama ini dipakai `config('printing.technologies.X')`.
     *
     * Materialnya disertakan agar pemanggil menerima satu bentuk utuh, persis
     * seperti membaca config dahulu — sehingga App\Services\PrintEstimator dan
     * seluruh turunannya tidak perlu tahu datanya kini berasal dari basis data.
     *
     * @return array<string, mixed>
     */
    public function toEstimatorArray(): array
    {
        return [
            'name' => $this->name,
            'family' => $this->family,
            'description' => $this->description,
            'build_volume' => [
                'x' => $this->build_volume_x,
                'y' => $this->build_volume_y,
                'z' => $this->build_volume_z,
            ],
            'shell_ratio' => $this->shell_ratio,
            'default_infill' => $this->default_infill,
            'infill_note' => $this->infill_note,
            'min_wall_thickness_mm' => $this->min_wall_thickness_mm,
            'support_volume_factor' => $this->support_volume_factor,
            'layer_height_range' => [
                'min' => $this->layer_height_min,
                'max' => $this->layer_height_max,
            ],
            'throughput_cm3_per_hour' => $this->throughput_cm3_per_hour,
            'setup_hours' => $this->setup_hours,
            'setup_fee' => $this->setup_fee,
            'machine_rate_per_hour' => $this->machine_rate_per_hour,
            'allows_hollow' => $this->allows_hollow,

            // Calculator mengenali material lewat NAMANYA — itulah yang
            // tersimpan pada `quotation_items.material`. Sejak material dapat
            // menunjuk mesin, satu nama boleh muncul di beberapa mesin, jadi
            // yang berlaku di sini dipilih tegas: baris TERTUA. Dengan begitu
            // menambahkan "PLA+" untuk mesin kedua tidak menggeser harga
            // penawaran yang sudah berjalan.
            'materials' => $this->materials
                // Urutan tampilnya tetap menurut nama material seperti semula;
                // `sortBy('id')` hanya dipakai sesaat untuk menentukan baris
                // mana yang menang ketika satu nama dipakai beberapa mesin.
                ->sortBy('id')
                ->unique('material')
                ->sortBy('material')
                ->keyBy('material')
                ->map(fn (PrintMaterial $material) => $material->toEstimatorArray())
                ->all(),
        ];
    }

    /** Kode yang diusulkan dari nama teknologi, mis. "Binder Jetting" → "BJ". */
    public static function suggestCode(string $name): string
    {
        $initials = collect(preg_split('/\s+/', trim($name)))
            ->filter()
            ->map(fn (string $word) => Str::substr($word, 0, 1))
            ->implode('');

        return strtoupper(Str::limit($initials !== '' ? $initials : $name, 12, ''));
    }
}
