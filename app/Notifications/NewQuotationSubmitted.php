<?php

namespace App\Notifications;

use App\Models\QuotationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Pemberitahuan untuk admin ketika ada penawaran baru masuk.
 *
 * Disimpan lewat channel `database` sehingga dapat dihitung sebagai badge pada
 * ikon lonceng dan ditarik berkala oleh dashboard admin untuk ditampilkan
 * sebagai popup.
 */
class NewQuotationSubmitted extends Notification
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
        $models = $this->quotation->items()->count();

        return [
            'type' => 'quotation.new',
            'title' => 'Penawaran baru dari '.$this->quotation->name,
            'message' => $models.' model 3D menunggu ditinjau ('.$this->quotation->tracking_number.').',
            'quotation_id' => $this->quotation->id,
            'tracking_number' => $this->quotation->tracking_number,
            'url' => route('admin.quotations.show', $this->quotation),
        ];
    }
}
