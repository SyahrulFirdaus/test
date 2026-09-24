<?php

namespace App\Support;

use App\Models\PrintColor;
use App\Models\PrintMaterialColor;
use App\Services\PrintEstimator;
use Throwable;

/**
 * Simulasi warna material.
 *
 * Pilihan ini hanya mengubah tampilan model di viewer dan dicatat sebagai
 * preferensi pelanggan pada permintaan penawaran — berkas model yang diunggah
 * sama sekali tidak diubah.
 *
 * Daftarnya dimiliki TIAP MATERIAL (tabel `print_material_colors`), diisi
 * Superadmin pada form Tambah/Ubah Material, sehingga PLA Plus dan PETG dapat
 * menawarkan warna yang berbeda. Palet lama (`print_colors`) tinggal sebagai
 * lapisan dasar agar kunci warna pada penawaran lama tetap dikenali, dan isi
 * `printing.material_colors.options` tetap ada sebagai cadangan terakhir bila
 * tabelnya belum sempat dibuat — saat migrasi berjalan, misalnya.
 */
class MaterialColor
{
    /**
     * Cache satu permintaan.
     *
     * Warna dibaca berkali-kali dalam satu halaman (setiap material, setiap
     * model pada penawaran), jadi tabelnya cukup dibaca sekali. null berarti
     * belum pernah dibaca pada permintaan ini.
     *
     * @var array<string, array<string, string>>|null
     */
    private static ?array $cache = null;

    /** @return array<string, array<string, string>> */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        try {
            /*
             * Palet lama menjadi lapisan DASAR, bukan sumber pilihan.
             *
             * Warna kini dimiliki tiap material, tetapi penawaran lama
             * menyimpan kunci yang dahulu berasal dari palet bersama. Menaruh
             * palet di bawah membuat kunci semacam itu tetap menemukan nama dan
             * hexanya walau tidak satu material pun menawarkannya lagi.
             */
            $legacy = PrintColor::ordered()
                ->get()
                ->mapWithKeys(fn (PrintColor $color) => [
                    $color->key => ['label' => $color->label, 'hex' => $color->hex],
                ])
                ->all();

            $owned = PrintMaterialColor::ordered()
                ->get()
                ->mapWithKeys(fn (PrintMaterialColor $color) => [
                    $color->key => ['label' => $color->name, 'hex' => $color->hex],
                ])
                ->all();

            $colors = array_merge($legacy, $owned);
        } catch (Throwable) {
            // Tabelnya belum ada (mis. saat migrasi pertama dijalankan).
            $colors = [];
        }

        return self::$cache = $colors !== [] ? $colors : self::fallback();
    }

    /**
     * Daftar bawaan dari config, dipakai selama tabelnya masih kosong.
     *
     * @return array<string, array<string, string>>
     */
    public static function fallback(): array
    {
        return config('printing.material_colors.options', []);
    }

    /** Lupakan hasil bacaan tabel — dipanggil setelah menu Color menyimpan. */
    public static function forget(): void
    {
        self::$cache = null;
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function default(): string
    {
        $default = (string) config('printing.material_colors.default');

        return self::exists($default) ? $default : (self::keys()[0] ?? 'merah');
    }

    public static function exists(?string $key): bool
    {
        return $key !== null && array_key_exists($key, self::all());
    }

    /** @return array<string, string> */
    public static function resolve(?string $key): array
    {
        return self::all()[$key] ?? self::all()[self::default()] ?? ['label' => 'Merah', 'hex' => '#B8452F'];
    }

    public static function label(?string $key): string
    {
        return self::resolve($key)['label'];
    }

    public static function hex(?string $key): string
    {
        return self::resolve($key)['hex'];
    }

    /**
     * Warna yang benar-benar tersedia untuk satu material.
     *
     * Daftarnya milik material itu sendiri — diisi Superadmin pada form
     * Tambah/Ubah Material — sehingga PLA Plus dan PETG dapat menawarkan warna
     * yang berbeda. Material yang daftarnya masih kosong menerima seluruh warna
     * yang dikenal sistem, supaya pilihan warnanya tidak pernah habis.
     *
     * @return array<string, array<string, string>>
     */
    public static function forMaterial(?string $technology, ?string $material): array
    {
        $allowed = app(PrintEstimator::class)->material((string) $technology, (string) $material)['colors'] ?? null;

        if (! is_array($allowed) || $allowed === []) {
            return self::all();
        }

        $all = self::all();

        // Urutannya mengikuti daftar warna material, bukan urutan tabelnya.
        return collect($allowed)
            ->filter(fn (string $key) => isset($all[$key]))
            ->mapWithKeys(fn (string $key) => [$key => $all[$key]])
            ->all();
    }

    /** @return array<int, string> */
    public static function keysForMaterial(?string $technology, ?string $material): array
    {
        return array_keys(self::forMaterial($technology, $material));
    }

    /**
     * Warna yang dipakai bila pilihan pengguna tidak tersedia pada materialnya.
     *
     * Warna bawaan global dipakai bila memang termasuk pilihan material itu;
     * bila tidak, warna pertama yang tersedia yang dipakai.
     */
    public static function defaultForMaterial(?string $technology, ?string $material): string
    {
        $available = self::keysForMaterial($technology, $material);

        return in_array(self::default(), $available, true)
            ? self::default()
            : ($available[0] ?? self::default());
    }

    /** Sesuaikan pilihan warna dengan material — dipakai saat materialnya berganti. */
    public static function resolveForMaterial(?string $key, ?string $technology, ?string $material): string
    {
        return in_array($key, self::keysForMaterial($technology, $material), true)
            ? (string) $key
            : self::defaultForMaterial($technology, $material);
    }
}
