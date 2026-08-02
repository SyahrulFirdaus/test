<?php

namespace App\Support;

/**
 * Status kelayakan cetak hasil analisis di browser.
 *
 * Dipakai bersama oleh penawaran (`quotation_requests`) dan tiap modelnya
 * (`quotation_items`). Status pada penawaran merupakan status terburuk di
 * antara seluruh modelnya, sehingga satu model bermasalah tidak tersembunyi
 * di balik model lain yang sudah aman.
 */
class AnalysisStatus
{
    public const READY = 'ready';

    public const WARNING = 'warning';

    public const NOT_PRINTABLE = 'not_printable';

    /** Makin besar angkanya, makin butuh perhatian. */
    private const SEVERITY = [
        self::READY => 0,
        self::WARNING => 1,
        self::NOT_PRINTABLE => 2,
    ];

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::SEVERITY);
    }

    public static function label(?string $status): string
    {
        return match ($status) {
            self::READY => 'Ready to Print',
            self::NOT_PRINTABLE => 'Not Printable',
            default => 'Need Improvement',
        };
    }

    /**
     * Status terburuk dari sekumpulan status model.
     *
     * @param  iterable<int, string|null>  $statuses
     */
    public static function worst(iterable $statuses): string
    {
        $worst = self::READY;

        foreach ($statuses as $status) {
            // Status yang tidak dikenal diperlakukan sebagai "perlu perbaikan",
            // bukan diabaikan, agar tidak menaikkan penilaian tanpa dasar.
            $normalized = isset(self::SEVERITY[$status]) ? $status : self::WARNING;

            if (self::SEVERITY[$normalized] > self::SEVERITY[$worst]) {
                $worst = $normalized;
            }
        }

        return $worst;
    }
}
