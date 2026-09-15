<?php

namespace App\Support;

/**
 * Pembantu alur status penawaran.
 *
 * Urutan tahap sepenuhnya ditentukan `quotation_statuses` di config/printing.php,
 * sehingga timeline pada halaman tracking, pilihan status di dashboard admin, dan
 * riwayat perubahan selalu mengikuti satu sumber yang sama.
 *
 * Status terbagi tiga grup:
 *  - `flow`         tahap normal yang berjalan maju dan membentuk timeline;
 *  - `cancellation` keadaan pembatalan yang berada di luar timeline dan hanya
 *                   dipasang lewat aksi pembatalan, bukan dropdown status;
 *  - `payment`      keadaan pembayaran yang menahan penawaran pada tahapnya
 *                   sekarang (bukti ditolak) — penawaran tetap berjalan dan
 *                   perpindahannya diatur aksi verifikasi pembayaran.
 */
class QuotationStatus
{
    /**
     * Tahap pertama sekaligus satu-satunya tahap yang isinya masih boleh
     * diubah pemiliknya. Penawaran baru langsung masuk ke sini.
     */
    public const REVIEWING = 'reviewing';

    public const AWAITING_PAYMENT = 'awaiting_payment';

    public const PAYMENT_REVIEW = 'payment_review';

    public const PAYMENT_RECEIVED = 'payment_received';

    public const PAYMENT_REJECTED = 'payment_rejected';

    public const PAYMENT_EXPIRED = 'payment_expired';

    public const PRODUCTION = 'production';

    public const QUALITY_CONTROL = 'quality_control';

    public const READY_TO_SHIP = 'ready_to_ship';

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
        return self::flowKeys()[0] ?? self::REVIEWING;
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
     * Hanya tahap paling awal ("File Sedang Direview") yang ditandai `editable`
     * di config; begitu admin memindahkannya ke tahap berikutnya, seluruh data
     * penawaran menjadi read only.
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
            self::PAYMENT_EXPIRED,
        ], true);
    }

    /**
     * Pengelompokan tahap untuk ringkasan dashboard.
     *
     * Dikumpulkan di sini — bukan disebar sebagai daftar status di tiap
     * controller — supaya angka "pesanan aktif" pada dashboard, penyaringan
     * daftar penawaran, dan pelacakan produksi selalu memakai batasan yang sama.
     */

    /** Penawaran yang berhenti, entah selesai atau dibatalkan. */
    public static function closedKeys(): array
    {
        return [
            self::COMPLETED,
            self::CANCELLED_BY_USER,
            self::CANCELLATION_APPROVED,
            self::PAYMENT_EXPIRED,
        ];
    }

    /** Pembatalan yang benar-benar menghentikan penawaran. */
    public static function cancelledKeys(): array
    {
        return [
            self::CANCELLED_BY_USER,
            self::CANCELLATION_APPROVED,
            self::PAYMENT_EXPIRED,
        ];
    }

    /** Tahap pengerjaan setelah pembayaran diterima, sebelum diserahkan. */
    public static function productionKeys(): array
    {
        return [
            self::PAYMENT_RECEIVED,
            self::PRODUCTION,
            self::QUALITY_CONTROL,
            self::READY_TO_SHIP,
        ];
    }

    /** Tahap yang menunggu penyelesaian pembayaran dari pelanggan. */
    public static function paymentKeys(): array
    {
        return [
            self::AWAITING_PAYMENT,
            self::PAYMENT_REVIEW,
            self::PAYMENT_REJECTED,
        ];
    }

    /** Penawaran yang masih berjalan — belum selesai dan belum dibatalkan. */
    public static function openKeys(): array
    {
        return array_values(array_diff(self::keys(), self::closedKeys()));
    }

    /**
     * Tahap alur yang mewakili status ini pada timeline.
     *
     * Status di luar alur maju tidak punya posisi sendiri, jadi timeline-nya
     * memakai tahap terakhir yang benar-benar dijalani: pembatalan memakai
     * `status_before_cancellation` (diteruskan pemanggil), sedangkan bukti
     * pembayaran yang ditolak atau kedaluwarsa kembali ke tahap "Menunggu
     * Pembayaran".
     */
    public static function timelineAnchor(?string $status, ?string $fallback = null): ?string
    {
        if (in_array($status, [self::PAYMENT_REJECTED, self::PAYMENT_EXPIRED], true)) {
            return self::AWAITING_PAYMENT;
        }

        return self::isCancellation($status) ? ($fallback ?? $status) : $status;
    }

    /** Posisi tahap dalam alur; -1 bila status tidak dikenal atau di luar alur. */
    public static function position(?string $status): int
    {
        $index = array_search($status, self::flowKeys(), true);

        return $index === false ? -1 : $index;
    }

    /**
     * Tahap tepat sesudah status ini pada alur, atau null bila sudah di ujung.
     *
     * Dihitung dari tahap acuan (lihat `timelineAnchor`), sehingga status di
     * luar alur maju — bukti pembayaran ditolak, pengajuan pembatalan —
     * meneruskan alurnya dari tahap terakhir yang benar-benar dijalani.
     */
    public static function next(?string $status, ?string $fallback = null): ?string
    {
        $position = self::position(self::timelineAnchor($status, $fallback));

        if ($position < 0) {
            return null;
        }

        return self::flowKeys()[$position + 1] ?? null;
    }

    /**
     * Tahap yang boleh dipasang admin dari status sekarang.
     *
     * Alurnya selangkah demi selangkah: hanya tahap yang sedang berjalan dan
     * tepat satu tahap sesudahnya. Tahap yang sudah dilewati tidak dapat
     * dipilih ulang, dan tahap yang lebih jauh tidak dapat dilompati sebelum
     * tahap sebelumnya benar-benar tersimpan.
     *
     * @return array<int, string>
     */
    public static function selectableFrom(?string $status, ?string $fallback = null): array
    {
        $anchor = self::timelineAnchor($status, $fallback);

        // Status di luar alur yang tidak menyimpan tahap acuan — mis. pembatalan
        // tanpa `status_before_cancellation` — hanya boleh kembali ke tahap awal.
        if (self::position($anchor) < 0) {
            return [self::first()];
        }

        return array_values(array_filter([$anchor, self::next($anchor)]));
    }

    /** Apakah perpindahan ke `$target` sah dari status sekarang. */
    public static function canTransitionTo(?string $status, ?string $target, ?string $fallback = null): bool
    {
        return in_array($target, self::selectableFrom($status, $fallback), true);
    }

    /**
     * Susunan dropdown status admin beserta keadaan tiap tahap.
     *
     * Seluruh tahap tetap ditampilkan supaya urutannya terbaca utuh; yang
     * membedakan hanya `selectable`. Keadaannya dihitung dari status yang
     * tersimpan, jadi menyegarkan halaman selalu mengembalikan kunci yang sama.
     *
     * @return array<int, array{key: string, label: string, state: string, hint: string, selectable: bool}>
     */
    public static function manualChoices(?string $status, ?string $fallback = null): array
    {
        $current = self::position(self::timelineAnchor($status, $fallback));
        $selectable = self::selectableFrom($status, $fallback);

        $choices = [];

        foreach (self::flow() as $key => $stage) {
            $position = self::position($key);

            $state = match (true) {
                $current >= 0 && $position < $current => 'done',
                $current >= 0 && $position === $current => 'current',
                $position === $current + 1 => 'next',
                default => 'locked',
            };

            $choices[] = [
                'key' => $key,
                'label' => $stage['label'],
                'state' => $state,
                'hint' => match ($state) {
                    'done' => 'sudah dilewati',
                    'current' => 'status saat ini',
                    'next' => 'tahap berikutnya',
                    default => 'terkunci',
                },
                'selectable' => in_array($key, $selectable, true),
            ];
        }

        return $choices;
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
