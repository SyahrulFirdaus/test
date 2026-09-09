<?php

namespace App\Notifications;

use App\Models\PaymentInstallment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Pemberitahuan untuk admin ketika pelanggan mengunggah bukti pembayaran salah
 * satu termin, sehingga terminnya menunggu keputusan pada menu Verifikasi
 * Pembayaran.
 */
class InstallmentProofUploaded extends Notification
{
    use Queueable;

    public function __construct(private readonly PaymentInstallment $installment) {}

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
            'type' => 'payment.installment.proof',
            'title' => 'Bukti '.$this->installment->title.' dari '.$quotation->name,
            'message' => 'Penawaran '.$quotation->tracking_number.' mengunggah bukti pembayaran '
                .$this->installment->title.' dan menunggu verifikasi.',
            'quotation_id' => $quotation->id,
            'tracking_number' => $quotation->tracking_number,
            'installment_id' => $this->installment->id,
            'url' => route('admin.payments.index', ['filter' => 'installments']),
        ];
    }
}
