<?php

namespace App\Notifications;

use App\Models\QuotationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Pemberitahuan untuk admin ketika pelanggan mengunggah bukti pembayaran,
 * sehingga penawarannya menunggu verifikasi pada menu Verifikasi Pembayaran.
 */
class PaymentProofUploaded extends Notification
{
    use Queueable;

    public function __construct(private readonly QuotationRequest $quotation) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment.proof',
            'title' => 'Bukti pembayaran dari '.$this->quotation->name,
            'message' => 'Penawaran '.$this->quotation->tracking_number.' menunggu verifikasi pembayaran.',
            'quotation_id' => $this->quotation->id,
            'tracking_number' => $this->quotation->tracking_number,
            'url' => route('admin.payments.index'),
        ];
    }
}
