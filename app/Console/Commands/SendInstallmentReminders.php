<?php

namespace App\Console\Commands;

use App\Services\PaymentTermFlow;
use Illuminate\Console\Command;

/**
 * Kirim pengingat jatuh tempo termin dan tandai yang sudah terlambat.
 *
 * Berbeda dari quotations:expire-payments yang membatalkan penawaran, perintah
 * ini tidak pernah menghentikan apa pun — pekerjaan B2B sudah berjalan saat
 * terminnya menunggu. Yang dilakukannya hanya menagih: mengingatkan sebelum
 * jatuh tempo lalu menandai termin yang terlewat agar terpantau admin.
 *
 * Dijadwalkan sekali sehari di routes/console.php.
 */
class SendInstallmentReminders extends Command
{
    protected $signature = 'payments:installment-reminders';

    protected $description = 'Kirim pengingat jatuh tempo termin dan tandai termin yang terlambat';

    public function handle(PaymentTermFlow $terms): int
    {
        // Keterlambatan ditandai lebih dulu supaya termin yang baru saja lewat
        // tidak sempat menerima pengingat "jatuh tempo hari ini".
        $overdue = $terms->markOverdue();
        $reminders = $terms->sendReminders();

        $this->info("{$overdue} termin ditandai terlambat, {$reminders} pengingat pembayaran dikirim.");

        return self::SUCCESS;
    }
}
