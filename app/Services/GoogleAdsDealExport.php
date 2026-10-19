<?php

namespace App\Services;

use App\Models\Lead;
use App\Support\LeadStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Export CSV Deal untuk impor konversi offline Google Ads (Data Manager).
 *
 * Hanya lead berstatus Deal yang waktu Deal-nya (deal_at, zona Asia/Jakarta)
 * berada di rentang tanggal. Setiap baris memakai data asli Lead Management —
 * tidak ada nilai yang dikarang:
 *  - Email / Phone Number : dari lead, atau akun pelanggan bila kosong;
 *                           divalidasi, HP dinormalisasi ke +62…;
 *  - Google Click ID      : GCLID first touch yang tersimpan;
 *  - Conversion Value     : nilai deal yang diisi admin (bukan estimasi);
 *  - Order ID             : stabil per lead untuk deduplikasi Google Ads.
 *
 * Baris yang tidak memenuhi syarat dilewati beserta alasannya; data yang
 * tidak valid dikosongkan, tidak pernah diganti nilai fiktif. Mengekspor
 * tidak mengubah status, nilai, quotation, maupun pembayaran.
 */
class GoogleAdsDealExport
{
    public const HEADERS = [
        'Email',
        'Phone Number',
        'Google Click ID',
        'Conversion Name',
        'Conversion Time',
        'Conversion Value',
        'Conversion Currency',
    ];

    /**
     * @return array{rows: array<int, array{lead: Lead, values: array<int, string>, notes: array<int, string>}>, skipped: array<int, array{lead: Lead, reasons: array<int, string>}>}
     */
    public function prepare(string $from, string $to, bool $onlyNew = false): array
    {
        $rows = [];
        $skipped = [];

        foreach ($this->deals($from, $to, $onlyNew) as $lead) {
            [$values, $reasons, $notes] = $this->row($lead);

            if ($reasons !== []) {
                $skipped[] = ['lead' => $lead, 'reasons' => $reasons];
            } else {
                $rows[] = ['lead' => $lead, 'values' => $values, 'notes' => $notes];
            }
        }

        return ['rows' => $rows, 'skipped' => $skipped];
    }

    /** @return array<int, string> */
    public function headers(): array
    {
        return config('leads.google_ads_export.order_id')
            ? [...self::HEADERS, 'Order ID']
            : self::HEADERS;
    }

    /**
     * CSV UTF-8 (tanpa BOM — BOM membuat header pertama tidak dikenali
     * Google), dipisah koma, setiap nilai di-escape fputcsv.
     *
     * @param  array<int, array{values: array<int, string>}>  $rows
     */
    public function csv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $this->headers(), ',', '"', '');

        foreach ($rows as $row) {
            fputcsv($handle, $row['values'], ',', '"', '');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /** Catat bahwa deal ini sudah ikut diekspor (tanpa menyentuh updated_at). */
    public function markExported(Collection $leads): void
    {
        $ids = $leads->pluck('id')->all();

        if ($ids === []) {
            return;
        }

        $now = now();

        Lead::query()->whereKey($ids)->whereNull('ads_exported_at')->toBase()->update(['ads_exported_at' => $now]);
        Lead::query()->whereKey($ids)->toBase()->increment('ads_export_count');
    }

    public function filename(): string
    {
        return 'Google_Ads_Deal_'.now(config('leads.google_ads_export.timezone'))->format('Y-m-d').'.csv';
    }

    /** @return Collection<int, Lead> */
    private function deals(string $from, string $to, bool $onlyNew): Collection
    {
        $timezone = config('leads.google_ads_export.timezone');
        $start = Carbon::createFromFormat('Y-m-d', $from, $timezone)->startOfDay()->utc();
        $end = Carbon::createFromFormat('Y-m-d', $to, $timezone)->endOfDay()->utc();

        return Lead::query()
            ->with('user:id,email,phone')
            ->where('status', LeadStatus::DEAL)
            ->whereNotNull('deal_at')
            ->whereBetween('deal_at', [$start, $end])
            ->when($onlyNew, fn ($query) => $query->whereNull('ads_exported_at'))
            ->orderBy('deal_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, string>, 2: array<int, string>} [nilai kolom, alasan dilewati, catatan]
     */
    private function row(Lead $lead): array
    {
        $config = config('leads.google_ads_export');
        $reasons = [];
        $notes = [];

        $email = self::email($lead->email ?: $lead->user?->email);
        $phone = self::phone($lead->whatsapp ?: $lead->user?->phone);
        $gclid = self::gclid($lead->gclid);

        if (filled($lead->email ?: $lead->user?->email) && $email === null) {
            $notes[] = 'Email tidak valid, dikosongkan.';
        }

        if (filled($lead->whatsapp ?: $lead->user?->phone) && $phone === null) {
            $notes[] = 'Nomor HP tidak valid, dikosongkan.';
        }

        if (filled($lead->gclid) && $gclid === null) {
            $notes[] = 'GCLID tidak valid, dikosongkan.';
        }

        if ($gclid === null && ($email !== null || $phone !== null)) {
            $notes[] = 'Tanpa GCLID — dicocokkan lewat email/HP (enhanced conversions for leads).';
        }

        if ($lead->deal_value === null) {
            $reasons[] = 'Nilai deal belum diisi.';
        }

        if ($lead->deal_at === null) {
            $reasons[] = 'Waktu Deal tidak tercatat.';
        }

        if ($gclid === null && $email === null && $phone === null) {
            $reasons[] = 'Tidak ada GCLID, email, maupun nomor HP yang valid untuk dicocokkan Google Ads.';
        }

        $values = [
            $email ?? '',
            $phone ?? '',
            $gclid ?? '',
            $config['conversion_name'],
            $lead->deal_at?->copy()->setTimezone($config['timezone'])->format($config['time_format']) ?? '',
            (string) ($lead->deal_value ?? ''),
            $config['currency'],
        ];

        if ($config['order_id']) {
            $values[] = $config['order_id_prefix'].$lead->id;
        }

        return [$values, $reasons, $notes];
    }

    /** Email huruf kecil tanpa spasi, atau null bila tidak valid. */
    public static function email(?string $email): ?string
    {
        $email = mb_strtolower(trim((string) $email));

        return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /**
     * Nomor Indonesia format internasional +62…: menerima awalan 0, 62,
     * +62, atau 8; selain itu (atau panjang tidak wajar) null.
     */
    public static function phone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        $digits = match (true) {
            str_starts_with($digits, '62') => $digits,
            str_starts_with($digits, '0') => '62'.substr($digits, 1),
            str_starts_with($digits, '8') => '62'.$digits,
            default => null,
        };

        // 62 + 8–13 digit nomor nasional (pola nomor seluler/PSTN Indonesia).
        return $digits !== null && preg_match('/^62[1-9]\d{7,12}$/', $digits) ? '+'.$digits : null;
    }

    /** GCLID hanya huruf, angka, "_" dan "-"; selain itu null. */
    public static function gclid(?string $gclid): ?string
    {
        $gclid = trim((string) $gclid);

        return preg_match('/^[A-Za-z0-9_\-]{10,255}$/', $gclid) ? $gclid : null;
    }
}
