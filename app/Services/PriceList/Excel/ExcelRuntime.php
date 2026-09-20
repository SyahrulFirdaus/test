<?php

namespace App\Services\PriceList\Excel;

/**
 * Kesiapan PHP untuk membaca dan menulis berkas .xlsx.
 *
 * Berkas .xlsx sebenarnya adalah arsip zip berisi XML, jadi PhpSpreadsheet
 * menuntut ekstensi PHP `zip` — baik saat MEMBACA (Import) maupun saat MENULIS
 * (Export, Template, Contoh). Tanpa ekstensi itu yang muncul hanyalah
 * `Class "ZipArchive" not found`: benar secara teknis, tetapi tidak memberi
 * tahu pengelola apa pun tentang apa yang harus dilakukan.
 *
 * Kelas ini menerjemahkannya menjadi keterangan yang dapat ditindaklanjuti.
 *
 * Penyebab yang paling sering bukan ekstensinya belum dipasang, melainkan
 * PROSES web server-nya lebih tua daripada perubahan php.ini: `php artisan
 * serve` dan Apache membaca php.ini sekali saat dinyalakan, jadi mengaktifkan
 * `extension=zip` tidak berpengaruh sampai keduanya dinyalakan ulang. Karena
 * itu pesannya menyebut hal itu lebih dulu.
 */
class ExcelRuntime
{
    /** PHP yang menjalankan permintaan ini dapat menangani .xlsx. */
    public static function available(): bool
    {
        return class_exists(\ZipArchive::class);
    }

    /**
     * Keterangan siap tampil saat ekstensinya tidak ada.
     *
     * Disusun untuk dibaca pengelola yang menekan tombolnya, bukan untuk log:
     * satu kalimat sebab, lalu langkah yang benar-benar menyelesaikannya.
     */
    public static function unavailableMessage(): string
    {
        return 'Fitur Excel belum aktif di server: ekstensi PHP "zip" tidak termuat. '
            .'Aktifkan "extension=zip" pada php.ini, lalu NYALAKAN ULANG web server-nya '
            .'(Apache di XAMPP, atau hentikan dan jalankan lagi "php artisan serve") — '
            .'php.ini hanya dibaca saat server dinyalakan, jadi perubahannya belum berlaku '
            .'pada proses yang sudah berjalan.';
    }
}
