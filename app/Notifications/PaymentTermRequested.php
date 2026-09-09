<?php

namespace App\Notifications;

use App\Models\PaymentTerm;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Pemberitahuan untuk admin bahwa ada pengajuan skema pembayaran bertahap yang
 * menunggu keputusan pada menu Payment Terms.
 */
class PaymentTermRequested extends Notification
{
    use Queueable;

    public function __construct(private readonly PaymentTerm $term) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $quotation = $this->term->quotation;

        return [
            'type' => 'payment.term.requested',
            'title' => 'Pengajuan '.$this->term->scheme_label.' dari '.$quotation->name,
            'message' => 'Penawaran '.$quotation->tracking_number.' mengajukan '
                .$this->term->scheme_label.' dan menunggu persetujuan payment term.',
            'quotation_id' => $quotation->id,
            'tracking_number' => $quotation->tracking_number,
            'payment_term_id' => $this->term->id,
            'url' => route('admin.payment-terms.show', $this->term),
        ];
    }
}
