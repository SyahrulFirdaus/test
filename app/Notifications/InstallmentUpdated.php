<?php

namespace App\Notifications;

use App\Models\PaymentInstallment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Pemberitahuan untuk pelanggan seputar satu termin pembayaran.
 *
 * Satu kelas menampung seluruh kabar termin — aktif, bukti diterima, bukti
 * ditolak, terlambat — karena isinya sama-sama menunjuk termin yang sama dan
 * membuka halaman yang sama; yang berbeda hanya judul dan pesannya. Dengan
 * begitu ikon lonceng tidak perlu mengenali selusin tipe notifikasi.
 */
class InstallmentUpdated extends Notification
{
    use Queueable;

    public function __construct(
        private readonly PaymentInstallment $installment,
        private readonly string $title,
        private readonly string $message,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $quotation = $this->installment->term->quotation;

        return [
            'type' => 'payment.installment',
            'title' => $this->title,
            'message' => $this->message,
            'quotation_id' => $quotation->id,
            'tracking_number' => $quotation->tracking_number,
            'installment_id' => $this->installment->id,
            'installment_number' => $this->installment->installment_number,
            'status' => $this->installment->status,
            'url' => route('dashboard.quotations.installments.show', [$quotation, $this->installment]),
        ];
    }
}
