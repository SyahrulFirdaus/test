<?php

namespace App\Support;

use App\Models\PrintTechnology;
use Illuminate\Support\Collection;

/**
 * Halaman-halaman Price List dan struktur menunya di sidebar.
 *
 * Price List tidak lagi satu halaman bertab: tiap item menu punya route dan
 * halamannya sendiri. Kunci halaman di sini sama dengan kunci tab lama
 * (`fdm`, `slai`, `machine-cost`, `harga`, `packaging`, `teknologi`), jadi
 * tautan lama `?tab=` tetap dapat diterjemahkan ke halaman barunya.
 */
class PriceListPage
{
    public const MACHINE_COST = 'machine-cost';

    /** Rumus Harga Otomatis (dahulu halaman "Harga"). Kuncinya tetap `harga` agar `?tab=harga` lama berlaku. */
    public const HARGA = 'harga';

    /** Rumus Harga Manual (dahulu "Rumus Harga SLA" di halaman SLA). */
    public const HARGA_MANUAL = 'harga-manual';

    public const PACKAGING = 'packaging';

    public const TEKNOLOGI = 'teknologi';

    /** Alamat halaman untuk satu kunci, beserta query tambahan. */
    public static function url(string $key, array $query = []): string
    {
        return match ($key) {
            self::MACHINE_COST => route('superadmin.price-list.machine-cost.index', $query),
            self::HARGA => route('superadmin.price-list.harga', $query),
            self::HARGA_MANUAL => route('superadmin.price-list.harga-manual', $query),
            self::PACKAGING => route('superadmin.price-list.packaging.index', $query),
            self::TEKNOLOGI => route('superadmin.price-list.technologies.index', $query),
            default => route('superadmin.price-list.technology', ['slug' => self::slugFor($key)] + $query),
        };
    }

    /** Alamat halaman material satu teknologi. */
    public static function technologyUrl(PrintTechnology $technology, array $query = []): string
    {
        return self::url($technology->tabKey(), $query);
    }

    /** Kunci halaman yang dikenal, untuk menerjemahkan `?tab=` lama. */
    public static function exists(?string $key): bool
    {
        return $key !== null
            && (in_array($key, [self::MACHINE_COST, self::HARGA, self::HARGA_MANUAL, self::PACKAGING, self::TEKNOLOGI], true)
                || self::technologyForKey($key) !== null);
    }

    /**
     * Grup menu sidebar beserta itemnya: [kunci => label].
     *
     * @return array<int, array{key: string, label: string, icon: string, items: array<string, string>}>
     */
    public static function menu(): array
    {
        return [
            [
                'key' => 'teknologi-material',
                'label' => 'Teknologi & Material',
                'icon' => 'cube',
                'items' => self::technologies()
                    ->mapWithKeys(fn (PrintTechnology $technology) => [$technology->tabKey() => 'Teknologi '.$technology->tabLabel()])
                    ->all(),
            ],
            ['key' => 'machine-cost', 'label' => 'Machine Cost', 'icon' => 'printer', 'items' => [self::MACHINE_COST => 'Machine Cost']],
            // Packaging tidak punya item menu sendiri: biayanya bagian dari Rumus
            // Harga Otomatis, dan halamannya dibuka dari sana (lihat current()).
            ['key' => 'harga', 'label' => 'Harga', 'icon' => 'tag', 'items' => [
                self::HARGA_MANUAL => 'Rumus Harga Manual',
                self::HARGA => 'Rumus Harga Otomatis',
            ]],
            ['key' => 'teknologi', 'label' => 'Teknologi', 'icon' => 'orbit', 'items' => [self::TEKNOLOGI => 'Teknologi']],
        ];
    }

    /**
     * Kunci halaman yang sedang dibuka menurut route permintaan ini — juga
     * saat menyunting data milik halaman itu (mis. Tambah Material MJF).
     */
    public static function current(): ?string
    {
        $request = request();
        $technology = $request->route('technology');

        return match (true) {
            $request->routeIs('superadmin.price-list.technology') => self::technologyForSlug((string) $request->route('slug'))?->tabKey(),
            $request->routeIs('superadmin.price-list.materials.*') => $technology instanceof PrintTechnology ? $technology->tabKey() : null,
            $request->routeIs('superadmin.price-list.machine-cost.*') => self::MACHINE_COST,
            $request->routeIs('superadmin.price-list.harga') => self::HARGA,
            $request->routeIs('superadmin.price-list.harga-manual') => self::HARGA_MANUAL,
            // Packaging dikelola dari halaman Rumus Harga Otomatis.
            $request->routeIs('superadmin.price-list.packaging.*') => self::HARGA,
            $request->routeIs('superadmin.price-list.technologies.*') => self::TEKNOLOGI,
            default => null,
        };
    }

    /* ------------------------------------------------------- teknologi --- */

    /** Teknologi yang ditawarkan, dalam urutan tampilnya. */
    public static function technologies(): Collection
    {
        // Dibaca sidebar dan halaman sekaligus; daftar yang diingat
        // PrintTechnology gugur sendiri begitu ada teknologi yang berubah.
        return PrintTechnology::cached()
            // Teknologi nonaktif tetap dikelola di Price List (materialnya dapat
            // disiapkan sebelum dinyalakan); hanya yang diarsipkan disembunyikan.
            ->filter(fn (PrintTechnology $technology) => $technology->archived_at === null)
            ->values();
    }

    public static function technologyForSlug(string $slug): ?PrintTechnology
    {
        return self::technologies()->first(fn (PrintTechnology $technology) => $technology->slug() === strtolower($slug));
    }

    public static function technologyForKey(string $key): ?PrintTechnology
    {
        return self::technologies()->first(fn (PrintTechnology $technology) => $technology->tabKey() === $key);
    }

    private static function slugFor(string $key): string
    {
        return self::technologyForKey($key)?->slug() ?? $key;
    }
}
