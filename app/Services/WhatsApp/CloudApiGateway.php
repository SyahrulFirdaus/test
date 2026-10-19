<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * WhatsApp Business Platform — Cloud API resmi dari Meta.
 *
 * Pengirim pada Cloud API dikenali lewat Phone Number ID, bukan nomor
 * teleponnya. Supaya admin cukup mengisi nomor pada halaman Pengaturan
 * WhatsApp, Phone Number ID dicari otomatis dari daftar nomor milik WhatsApp
 * Business Account (WHATSAPP_BUSINESS_ACCOUNT_ID).
 *
 * Access token hanya dibaca dari config (bersumber dari .env) dan tidak pernah
 * ikut dalam pesan error yang disimpan maupun ditampilkan.
 */
class CloudApiGateway implements WhatsAppGateway
{
    public function send(string $sender, ?string $senderId, string $recipient, WhatsAppMessage $message): SendResult
    {
        if (($missing = $this->missingCredential(requireAccount: blank($senderId))) !== null) {
            return SendResult::failed($missing);
        }

        try {
            if (blank($senderId)) {
                $connection = $this->check($sender);

                if (! $connection->connected || blank($connection->senderId)) {
                    return SendResult::failed($connection->message);
                }

                $senderId = $connection->senderId;
            }

            $response = $this->client()->post($senderId.'/messages', $this->payload($recipient, $message));

            if ($response->successful()) {
                return SendResult::sent($response->json('messages.0.id'));
            }

            return SendResult::failed($this->errorFrom($response));
        } catch (Throwable $e) {
            return SendResult::failed('Tidak dapat menghubungi WhatsApp Cloud API: '.$e->getMessage());
        }
    }

    public function check(string $sender): ConnectionResult
    {
        if (($missing = $this->missingCredential(requireAccount: true)) !== null) {
            return ConnectionResult::disconnected($missing);
        }

        try {
            $response = $this->client()->get(config('whatsapp.cloud_api.business_account_id').'/phone_numbers', [
                'fields' => 'id,display_phone_number,verified_name,quality_rating',
            ]);

            if (! $response->successful()) {
                return ConnectionResult::disconnected($this->errorFrom($response));
            }

            foreach ((array) $response->json('data', []) as $number) {
                $digits = preg_replace('/\D/', '', (string) ($number['display_phone_number'] ?? ''));

                if ($digits === $sender && filled($number['id'] ?? null)) {
                    return ConnectionResult::connected(
                        'Terhubung sebagai '.($number['verified_name'] ?? $number['display_phone_number'])
                            .(isset($number['quality_rating']) ? ' (kualitas: '.$number['quality_rating'].')' : '').'.',
                        (string) $number['id'],
                    );
                }
            }

            return ConnectionResult::disconnected('Nomor '.$sender.' belum terdaftar pada WhatsApp Business Account NUSAMA3D.');
        } catch (Throwable $e) {
            return ConnectionResult::disconnected('Tidak dapat menghubungi WhatsApp Cloud API: '.$e->getMessage());
        }
    }

    /** @return array<string, mixed> */
    private function payload(string $recipient, WhatsAppMessage $message): array
    {
        if ($message->template !== null) {
            $template = [
                'name' => $message->template,
                'language' => ['code' => config('whatsapp.templates.language', 'id')],
            ];

            if ($message->templateParameters !== []) {
                $template['components'] = [[
                    'type' => 'body',
                    'parameters' => array_map(
                        fn (string $value) => ['type' => 'text', 'text' => $value],
                        $message->templateParameters,
                    ),
                ]];
            }

            return [
                'messaging_product' => 'whatsapp',
                'to' => $recipient,
                'type' => 'template',
                'template' => $template,
            ];
        }

        return [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $recipient,
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $message->text],
        ];
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('whatsapp.cloud_api.base_url'), '/').'/'.config('whatsapp.cloud_api.version'))
            ->withToken((string) config('whatsapp.cloud_api.access_token'))
            ->acceptJson()
            ->timeout(max(1, (int) config('whatsapp.timeout', 10)));
    }

    private function missingCredential(bool $requireAccount = false): ?string
    {
        if (blank(config('whatsapp.cloud_api.access_token'))) {
            return 'WHATSAPP_ACCESS_TOKEN belum diatur pada konfigurasi server.';
        }

        if ($requireAccount && blank(config('whatsapp.cloud_api.business_account_id'))) {
            return 'WHATSAPP_BUSINESS_ACCOUNT_ID belum diatur pada konfigurasi server.';
        }

        return null;
    }

    /** Pesan error dari API, tanpa pernah menyertakan token. */
    private function errorFrom(Response $response): string
    {
        $error = $response->json('error');

        if (is_array($error)) {
            $code = isset($error['code']) ? ' / kode '.$error['code'] : '';
            $detail = $error['error_data']['details'] ?? null;

            return '[HTTP '.$response->status().$code.'] '
                .($error['message'] ?? 'Permintaan ditolak WhatsApp Cloud API.')
                .($detail ? ' — '.$detail : '');
        }

        return 'WhatsApp Cloud API menjawab dengan status HTTP '.$response->status().'.';
    }
}
