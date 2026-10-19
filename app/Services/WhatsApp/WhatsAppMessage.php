<?php

namespace App\Services\WhatsApp;

use App\Models\QuotationRequest;
use App\Support\QuotationStatus;
use Illuminate\Support\Carbon;

/**
 * Isi satu pesan WhatsApp: teks lengkapnya, dan — bila dikonfigurasi —
 * template Meta yang sudah disetujui beserta variabelnya.
 *
 * Teks selalu disusun, karena itulah yang disimpan pada riwayat pengiriman dan
 * yang dikirim bila template tidak dikonfigurasi. Template dipakai Cloud API
 * untuk pesan di luar jendela 24 jam (lihat config/whatsapp.php).
 */
final class WhatsAppMessage
{
    /**
     * @param  array<int, string>  $templateParameters
     */
    public function __construct(
        public readonly string $text,
        public readonly ?string $template = null,
        public readonly array $templateParameters = [],
    ) {}

    /** Pemberitahuan perubahan status penawaran untuk pemiliknya. */
    public static function quotationStatus(QuotationRequest $quotation, string $status, string $customerName, Carbon $changedAt): self
    {
        $label = QuotationStatus::label($status);
        $date = self::date($changedAt);

        $text = implode("\n", [
            '*NUSAMA3D – Informasi Penawaran*',
            '',
            'Halo '.$customerName.',',
            '',
            'Kami ingin menginformasikan bahwa status penawaran Anda telah diperbarui.',
            '',
            'Nomor Penawaran: '.$quotation->tracking_number,
            'Status: '.$label,
            'Tanggal Perubahan: '.$date,
            '',
            'Silakan login ke akun NUSAMA3D untuk melihat detail penawaran Anda.',
            '',
            'Terima kasih telah menggunakan layanan NUSAMA3D.',
        ]);

        return new self(
            $text,
            config('whatsapp.templates.status') ?: null,
            [$customerName, (string) $quotation->tracking_number, $label, $date],
        );
    }

    /** Pesan percobaan dari tombol Uji Kirim. */
    public static function test(Carbon $sentAt): self
    {
        $text = implode("\n", [
            '*NUSAMA3D – Uji Kirim WhatsApp*',
            '',
            'Ini adalah pesan percobaan dari sistem NUSAMA3D.',
            'Bila Anda menerima pesan ini, konfigurasi notifikasi WhatsApp sudah berjalan.',
            '',
            'Waktu: '.self::date($sentAt),
        ]);

        return new self($text, config('whatsapp.templates.test') ?: null);
    }

    private static function date(Carbon $at): string
    {
        return $at->copy()->timezone(config('app.timezone'))->locale('id')->translatedFormat('d F Y, H:i');
    }
}
