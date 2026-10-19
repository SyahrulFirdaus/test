<?php

namespace App\Notifications;

use App\Models\Lead;
use App\Support\LeadSource;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email ke tim CS (config leads.notify_email) setiap formulir publik
 * berhasil dikirim. Lampiran tidak ikut dikirim — ukurannya bisa sampai
 * 60 MB — melainkan diunduh dari halaman detail lead di admin.
 */
class NewLeadSubmitted extends Notification
{
    use Queueable;

    /** @param  array<string, mixed>  $submission  isian kiriman ini */
    public function __construct(
        public readonly Lead $lead,
        public readonly string $formLabel,
        public readonly array $submission,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lead = $this->lead;
        $data = $this->submission;
        $value = fn ($v) => filled($v) ? (string) $v : '—';

        $mail = (new MailMessage)
            ->subject('Lead baru: '.$data['name'].' — '.$value($data['service'] ?? null).' ['.$this->formLabel.']')
            ->greeting('Lead baru dari '.$this->formLabel)
            ->line('**Nama:** '.$value($data['name']))
            ->line('**Perusahaan:** '.$value($data['company'] ?? null))
            ->line('**Email:** '.$value($data['email'] ?? null))
            ->line('**WhatsApp:** '.$value($data['whatsapp'] ?? null))
            ->line('**Layanan:** '.$value($data['service'] ?? null));

        if (filled($data['quantity'] ?? null)) {
            $mail->line('**Jumlah:** '.$data['quantity'].' pcs');
        }

        if (filled($data['dimensions'] ?? null)) {
            $mail->line('**Dimensi:** '.$data['dimensions']);
        }

        $mail->line('**Kebutuhan:**')
            ->line($value($data['notes'] ?? null))
            ->line('**Lampiran:** '.(filled($data['attachment_name'] ?? null) ? $data['attachment_name'].' (unduh dari admin)' : '—'))
            ->line('**Halaman:** '.$value($lead->source_url))
            ->line('**Sumber:** '.collect([
                'GCLID' => $lead->gclid,
                'utm_source' => $lead->utm_source,
                'utm_medium' => $lead->utm_medium,
                'utm_campaign' => $lead->utm_campaign,
            ])->filter()->map(fn ($v, $k) => $k.'='.$v)->implode(', ') ?: '—');

        if (filled($data['email'] ?? null)) {
            $mail->replyTo($data['email'], $data['name']);
        }

        return $mail
            ->action('Buka di Lead Management', route('admin.leads.show', $lead))
            ->salutation('— Website '.config('app.name'))
            ->line('Asal lead: '.LeadSource::label($lead->source).'. Status lead dikelola di menu Lead.');
    }
}
