<?php

namespace App\Notifications;

use App\Models\PaymentTerm;
use App\Support\PaymentTermStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Pemberitahuan untuk pelanggan atas keputusan admin terhadap skema pembayaran
 * bertahap yang diajukannya — disetujui beserta jadwal terminnya, atau ditolak
 * beserta alasannya.
 */
class PaymentTermDecided extends Notification
{
    use Queueable;

    public function __construct(
        private readonly PaymentTerm $term,
        private readonly bool $approved,
        private readonly ?string $reason = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $quotation = $this->term->quotation;
        $status = $this->approved ? PaymentTermStatus::APPROVED : PaymentTermStatus::REJECTED;

        return [
            'type' => 'payment.term.decided',
            'title' => PaymentTermStatus::label($status),
            'message' => $this->approved
                ? 'Skema '.$this->term->scheme_label.' untuk penawaran '.$quotation->tracking_number
                    .' disetujui. Jadwal pembayaran terminnya sudah dapat dilihat.'
                : 'Skema '.$this->term->scheme_label.' untuk penawaran '.$quotation->tracking_number
                    .' ditolak.'.($this->reason ? ' Alasan: '.$this->reason : ''),
            'quotation_id' => $quotation->id,
            'tracking_number' => $quotation->tracking_number,
            'payment_term_id' => $this->term->id,
            'status' => $status,
            'url' => route('dashboard.quotations.payment', $quotation),
        ];
    }
}
