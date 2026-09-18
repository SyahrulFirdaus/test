<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Berkas bukti pembayaran: aturan unggah dan cara menyajikannya kembali.
 *
 * Dua lapis pemeriksaan, karena ekstensi saja dapat dipalsukan:
 *   1. `extensions` — nama berkas harus berakhiran JPG/JPEG/PNG/PDF;
 *   2. `mimetypes`  — ISI berkas (dibaca server, bukan dari browser) harus
 *      benar-benar gambar JPEG/PNG atau PDF.
 *
 * Saat disajikan, Content-Type ditetapkan dari daftar tetap di sini — bukan
 * ditebak dari isi berkas — dan diberi `nosniff`, sehingga berkas yang
 * lolos pun tidak pernah dapat dijalankan browser sebagai HTML/skrip di
 * dashboard admin.
 */
class PaymentProofFile
{
    /** @var array<string, string> ekstensi => Content-Type yang disajikan */
    private const CONTENT_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'pdf' => 'application/pdf',
    ];

    /** @return array<int, string> */
    public static function extensions(): array
    {
        return array_values(array_intersect(
            array_map('strtolower', (array) config('payment.proof.extensions', ['jpg', 'jpeg', 'png', 'pdf'])),
            array_keys(self::CONTENT_TYPES),
        ));
    }

    public static function maxKilobytes(): int
    {
        return (int) config('payment.proof.max_kilobytes', 5120);
    }

    /** @return array<int, string> */
    public static function rules(): array
    {
        $extensions = self::extensions();
        $mimeTypes = array_values(array_unique(array_map(fn (string $ext) => self::CONTENT_TYPES[$ext], $extensions)));

        return [
            'required',
            'file',
            'extensions:'.implode(',', $extensions),
            'mimetypes:'.implode(',', $mimeTypes),
            'max:'.self::maxKilobytes(),
        ];
    }

    /** @return array<string, string> pesan untuk kolom `$field` */
    public static function messages(string $field = 'proof'): array
    {
        $label = strtoupper(implode(', ', self::extensions()));

        return [
            $field.'.required' => 'Pilih berkas bukti pembayaran lebih dulu.',
            $field.'.extensions' => 'Bukti pembayaran harus berformat '.$label.'.',
            $field.'.mimetypes' => 'Isi berkas bukan gambar/PDF yang sah. Bukti pembayaran harus berformat '.$label.'.',
            $field.'.max' => 'Ukuran berkas melebihi batas '.round(self::maxKilobytes() / 1024).' MB.',
        ];
    }

    /** Sajikan bukti dari disk privat dengan Content-Type tetap. */
    public static function response(string $path, ?string $name): StreamedResponse
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $type = self::CONTENT_TYPES[$extension] ?? null;

        $headers = [
            'Content-Type' => $type ?? 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ];

        // Tipe yang tidak dikenal tidak pernah dibuka di tab: diunduh saja.
        return $type === null
            ? Storage::disk('local')->download($path, $name, $headers)
            : Storage::disk('local')->response($path, $name, $headers);
    }
}
