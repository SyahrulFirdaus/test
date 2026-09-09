<?php

namespace App\Console\Commands;

use App\Services\PaymentFlow;
use Illuminate\Console\Command;

/**
 * Batalkan penawaran yang melewati batas waktu pembayaran.
 *
 * Halaman pembayaran sudah memeriksa batas waktunya setiap kali dibuka, tetapi
 * penawaran yang tidak pernah dibuka lagi tetap harus berhenti sendiri —
 * itulah tugas perintah ini. Dijadwalkan tiap sepuluh menit di
 * routes/console.php.
 */
class ExpirePayments extends Command
{
    protected $signature = 'quotations:expire-payments';

    protected $description = 'Batalkan penawaran yang melewati batas waktu pembayaran';

    public function handle(PaymentFlow $payments): int
    {
        $expired = $payments->expireOverdue();

        $this->info($expired === 0
            ? 'Tidak ada penawaran yang melewati batas waktu pembayaran.'
            : "{$expired} penawaran dibatalkan karena melewati batas waktu pembayaran.");

        return self::SUCCESS;
    }
}
