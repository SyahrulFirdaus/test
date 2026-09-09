<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Penawaran yang batas waktu pembayarannya lewat dibatalkan otomatis menjadi
| "Penawaran Dibatalkan (Expired)". Halaman pembayaran juga memeriksanya
| sendiri saat dibuka, jadi jadwal ini yang mengurus penawaran yang memang
| tidak pernah dibuka lagi pemiliknya.
*/
Schedule::command('quotations:expire-payments')->everyTenMinutes()->withoutOverlapping();

/*
| Pengingat jatuh tempo termin pembayaran Business beserta penandaan termin
| yang terlambat. Cukup sekali sehari: ambang pengingatnya dihitung dalam
| hitungan hari, dan tiap tahap hanya dikirim sekali per termin.
*/
Schedule::command('payments:installment-reminders')->dailyAt('08:00')->withoutOverlapping();
