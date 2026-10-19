<?php

namespace App\Support;

/**
 * ID container Google Tag Manager dari .env (GTM_ID → services.google_tag_manager.id).
 *
 * Hanya ID berformat resmi (GTM-XXXXXXX) yang dipakai; kosong atau salah
 * tulis = GTM tidak dimuat sama sekali, sehingga tidak ada snippet rusak
 * yang memunculkan error console.
 */
class GoogleTagManager
{
    public static function id(): ?string
    {
        $id = strtoupper(trim((string) config('services.google_tag_manager.id')));

        return preg_match('/^GTM-[A-Z0-9]{4,12}$/', $id) ? $id : null;
    }
}
