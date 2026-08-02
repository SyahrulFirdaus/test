<?php

namespace App\Notifications;

use App\Models\QuotationRequest;
use App\Support\QuotationStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Pemberitahuan untuk pemilik penawaran setiap kali admin memindahkan status.
 *
 * Isinya memakai label dan penjelasan status dari config/printing.php, jadi
 * menambah tahap baru tidak menuntut perubahan di sini.
 */
class QuotationStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(
        private readonly QuotationRequest $quotation,
        private readonly string $status,
        private readonly ?string $note = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'quotation.status',
            'title' => QuotationStatus::label($this->status),
            'message' => $this->note
                ?: (QuotationStatus::description($this->status) ?? 'Status penawaran Anda diperbarui.'),
            'quotation_id' => $this->quotation->id,
            'tracking_number' => $this->quotation->tracking_number,
            'status' => $this->status,
            'url' => route('dashboard.quotations.show', $this->quotation),
        ];
    }
}
