<?php

namespace App\Support;

/**
 * Pembantu alur status penawaran.
 *
 * Urutan tahap sepenuhnya ditentukan `quotation_statuses` di config/printing.php,
 * sehingga timeline pada halaman tracking, pilihan status di dashboard admin, dan
 * riwayat perubahan selalu mengikuti satu sumber yang sama.
 *
 * Status terbagi dua grup:
 *  - `flow`         tahap normal yang berjalan maju dan membentuk timeline;
 *  - `cancellation` keadaan pembatalan yang berada di luar timeline dan hanya
 *                   dipasang lewat aksi pembatalan, bukan dropdown status.
 */
class QuotationStatus
{
    public const RECEIVED = 'received';

    public const REVIEWING = 'reviewing';

    public const COMPLETED = 'completed';

    public const CANCELLED_BY_USER = 'cancelled_by_user';

    public const CANCELLATION_REQUESTED = 'cancellation_requested';

    public const CANCELLATION_APPROVED = 'cancellation_approved';

    public const CANCELLATION_REJECTED = 'cancellation_rejected';

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return config('printing.quotation_statuses', []);
    }

    /** Tahap-tahap yang membentuk alur maju penawaran. */
    public static function flow(): array
    {
        return array_filter(self::all(), fn (array $stage) => ($stage['group'] ?? 'flow') === 'flow');
    }

    /** Keadaan pembatalan, di luar alur maju. */
    public static function cancellations(): array
    {
        return array_filter(self::all(), fn (array $stage) => ($stage['group'] ?? 'flow') === 'cancellation');
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /** @return array<int, string> */
    public static function flowKeys(): array
    {
        return array_keys(self::flow());
    }

    /** @return array<string, string> daftar kunci => label, untuk dropdown & filter */
    public static function options(): array
    {
        return array_map(fn (array $stage) => $stage['label'], self::all());
    }

    /**
     * Pilihan status yang boleh dipasang admin secara manual.
     *
     * Status pembatalan sengaja tidak ikut: perpindahannya diatur alur
     * pengajuan dan persetujuan pembatalan, bukan dipilih dari dropdown.
     *
     * @return array<string, string>
     */
    public static function manualOptions(): array
    {
        return array_map(fn (array $stage) => $stage['label'], self::flow());
    }

    public static function exists(string $status): bool
    {
        return array_key_exists($status, self::all());
    }

    public static function label(?string $status): string
    {
        return self::all()[$status]['label'] ?? (string) $status;
    }

    public static function description(?string $status): ?string
    {
        return self::all()[$status]['description'] ?? null;
    }

    public static function first(): string
    {
        return self::flowKeys()[0] ?? self::RECEIVED;
    }

    /** Apakah status ini termasuk keadaan pembatalan. */
    public static function isCancellation(?string $status): bool
    {
        $stage = self::all()[$status] ?? null;

        return $stage !== null && ($stage['group'] ?? 'flow') === 'cancellation';
    }

    /**
     * Apakah isi penawaran masih boleh diubah pemiliknya.
     *
     * Hanya tahap paling awal ("Menunggu Review") yang ditandai `editable` di
     * config; begitu admin memindahkannya ke "File Sedang Direview", seluruh
     * data penawaran menjadi read only.
     */
    public static function isEditable(?string $status): bool
    {
        return (bool) (self::all()[$status]['editable'] ?? false);
    }

    /** Status akhir yang tidak lagi berjalan. */
    public static function isClosed(?string $status): bool
    {
        return in_array($status, [
            self::COMPLETED,
            self::CANCELLED_BY_USER,
            self::CANCELLATION_APPROVED,
        ], true);
    }

    /** Posisi tahap dalam alur; -1 bila status tidak dikenal atau di luar alur. */
    public static function position(?string $status): int
    {
        $index = array_search($status, self::flowKeys(), true);

        return $index === false ? -1 : $index;
    }

    /**
     * Susun langkah-langkah timeline beserta keadaannya terhadap status sekarang.
     *
     * @return array<int, array{key: string, label: string, description: ?string, optional: bool, state: string}>
     */
    public static function timeline(?string $current): array
    {
        $currentPosition = self::position($current);

        $steps = [];

        foreach (self::flow() as $key => $stage) {
            $position = self::position($key);

            $steps[] = [
                'key' => $key,
                'label' => $stage['label'],
                'description' => $stage['description'] ?? null,
                'optional' => (bool) ($stage['optional'] ?? false),
                'state' => match (true) {
                    $currentPosition < 0 => 'upcoming',
                    $position < $currentPosition => 'done',
                    $position === $currentPosition => 'current',
                    default => 'upcoming',
                },
            ];
        }

        return $steps;
    }
}
