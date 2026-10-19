<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Log;

/**
 * Gateway pengembangan: pesan hanya dituliskan ke log aplikasi.
 *
 * Bawaan selama WHATSAPP_DRIVER belum diatur, sehingga lingkungan lokal dan
 * pengujian tidak pernah mengirim pesan nyata ke nomor pelanggan.
 */
class LogGateway implements WhatsAppGateway
{
    public function send(string $sender, ?string $senderId, string $recipient, WhatsAppMessage $message): SendResult
    {
        Log::info('[WhatsApp:log] '.$sender.' -> '.$recipient, ['message' => $message->text]);

        return SendResult::sent('log-'.uniqid());
    }

    public function check(string $sender): ConnectionResult
    {
        return ConnectionResult::connected(
            'Mode log aktif (WHATSAPP_DRIVER=log): pesan hanya dicatat di log aplikasi, belum benar-benar dikirim ke WhatsApp.'
        );
    }
}
