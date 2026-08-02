<?php

namespace App\Notifications;

use App\Models\QuotationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Pemberitahuan untuk admin ketika user mengajukan pembatalan atas penawaran
 * yang sudah masuk tahap review, sehingga perlu persetujuan lebih dulu.
 */
class CancellationRequested extends Notification
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
            'type' => 'cancellation.requested',
            'title' => 'Permintaan pembatalan dari '.$this->quotation->name,
            'message' => $this->quotation->cancellation_reason
                ?: 'Menunggu persetujuan pembatalan untuk '.$this->quotation->tracking_number.'.',
            'quotation_id' => $this->quotation->id,
            'tracking_number' => $this->quotation->tracking_number,
            'url' => route('admin.quotations.show', $this->quotation),
        ];
    }
}
