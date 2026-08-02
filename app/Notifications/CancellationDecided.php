<?php

namespace App\Notifications;

use App\Models\QuotationRequest;
use App\Support\QuotationStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Pemberitahuan untuk user atas keputusan admin terhadap permintaan
 * pembatalannya — disetujui atau ditolak.
 */
class CancellationDecided extends Notification
{
    use Queueable;

    public function __construct(
        private readonly QuotationRequest $quotation,
        private readonly bool $approved,
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
        $status = $this->approved
            ? QuotationStatus::CANCELLATION_APPROVED
            : QuotationStatus::CANCELLATION_REJECTED;

        return [
            'type' => 'cancellation.decided',
            'title' => $this->approved ? 'Permintaan pembatalan diterima' : 'Permintaan pembatalan ditolak',
            'message' => $this->note ?: ($this->approved
                ? 'Penawaran '.$this->quotation->tracking_number.' dihentikan sesuai permintaan Anda.'
                : 'Penawaran '.$this->quotation->tracking_number.' diteruskan. Hubungi kami bila masih ingin membatalkan.'),
            'quotation_id' => $this->quotation->id,
            'tracking_number' => $this->quotation->tracking_number,
            'status' => $status,
            'url' => route('dashboard.quotations.show', $this->quotation),
        ];
    }
}
