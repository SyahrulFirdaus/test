<?php

namespace App\Support;

/**
 * Angka statistik perusahaan dari config/company-stats.php, siap dirender
 * server-side: `display` sudah berisi angka akhir ("400"), sehingga bot dan
 * pengunjung tanpa JavaScript tidak pernah melihat "0".
 */
class CompanyStats
{
    /**
     * @return array<int, array{label: string, value: int|null, suffix: string, display: string}>
     */
    public static function all(): array
    {
        return collect(config('company-stats'))
            ->map(function (array $stat) {
                $value = isset($stat['since'])
                    ? max(0, now()->year - (int) $stat['since'])
                    : (isset($stat['value']) ? (int) $stat['value'] : null);

                return [
                    'label' => $stat['label'],
                    'value' => $value,
                    'suffix' => $value === null ? '' : ($stat['suffix'] ?? ''),
                    'display' => $value === null ? ($stat['placeholder'] ?? '—') : number_format($value, 0, ',', '.'),
                ];
            })
            ->all();
    }

    /** Satu statistik berdasarkan labelnya, mis. "Customer". */
    public static function find(string $label): ?array
    {
        return collect(self::all())->first(fn (array $stat) => strcasecmp($stat['label'], $label) === 0);
    }
}
