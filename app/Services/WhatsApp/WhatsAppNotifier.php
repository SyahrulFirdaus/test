<?php

namespace App\Services\WhatsApp;

use App\Models\QuotationRequest;
use App\Models\User;
use App\Models\WhatsAppNotification;
use App\Models\WhatsAppSetting;
use App\Support\WhatsAppNumber;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Satu pintu pengiriman notifikasi WhatsApp.
 *
 * Nomor pengirim dibaca dari menu Pengaturan WhatsApp (App\Models\WhatsAppSetting),
 * credential dari config/whatsapp.php, dan jalur kirimnya dari WhatsAppGateway —
 * sehingga alur penawaran cukup menyebut "status penawaran ini berubah" tanpa
 * tahu apa pun tentang WhatsApp.
 *
 * Setiap percobaan dicatat pada `whatsapp_notifications`. Kegagalan TIDAK
 * PERNAH dilempar ke pemanggil: perubahan status penawaran harus tetap
 * tersimpan meski pesan WhatsApp-nya gagal terkirim.
 */
class WhatsAppNotifier
{
    /** Jumlah notifikasi status yang gagal selama request ini, untuk pesan ke admin. */
    private int $failures = 0;

    /** Dibaca saat dipakai, supaya WHATSAPP_DRIVER yang berlaku selalu yang terbaru. */
    private function gateway(): WhatsAppGateway
    {
        return app(WhatsAppGateway::class);
    }

    /**
     * Kabari pemilik penawaran bahwa statusnya berubah.
     *
     * Nomor tujuan hanya diambil dari akun pemilik penawaran itu sendiri
     * (atau nomor WhatsApp yang ia isi pada penawarannya), tidak pernah dari
     * sumber lain.
     */
    public function quotationStatusChanged(QuotationRequest $quotation, ?string $previousStatus, string $status, ?User $actor = null): ?WhatsAppNotification
    {
        if ($previousStatus === $status) {
            return null;
        }

        try {
            $owner = $quotation->user;
            $customerName = trim((string) ($owner?->name ?: $quotation->name)) ?: 'Pelanggan';
            $recipient = WhatsAppNumber::normalize($owner?->phone ?: $quotation->whatsapp);
            $setting = WhatsAppSetting::current();
            $changedAt = $quotation->updated_at ?? now();

            $message = WhatsAppMessage::quotationStatus($quotation, $status, $customerName, $changedAt);

            $log = WhatsAppNotification::create([
                'type' => WhatsAppNotification::TYPE_STATUS_UPDATE,
                'quotation_request_id' => $quotation->id,
                'user_id' => $owner?->id,
                'tracking_number' => $quotation->tracking_number,
                'customer_name' => $customerName,
                'recipient' => $recipient,
                'sender' => $setting->phone_number,
                'previous_status' => $previousStatus,
                'quotation_status' => $status,
                'message' => $message->text,
                'status' => WhatsAppNotification::PENDING,
                'triggered_by' => $actor?->id,
            ]);

            $result = match (true) {
                ! $setting->hasSender() => SendResult::failed('Nomor WhatsApp pengirim NUSAMA3D belum diatur pada menu Pengaturan WhatsApp.'),
                $recipient === null => SendResult::failed('Nomor WhatsApp pelanggan tidak tersedia atau tidak valid.'),
                default => $this->gateway()->send($setting->phone_number, $setting->phone_number_id, $recipient, $message),
            };

            return $this->finish($log, $result);
        } catch (Throwable $e) {
            $this->failures++;
            Log::error('Notifikasi WhatsApp status penawaran gagal diproses.', [
                'quotation_id' => $quotation->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /** Pesan percobaan dari tombol Uji Kirim. */
    public function sendTest(string $recipient, ?User $actor = null): WhatsAppNotification
    {
        $setting = WhatsAppSetting::current();
        $message = WhatsAppMessage::test(now());

        $log = WhatsAppNotification::create([
            'type' => WhatsAppNotification::TYPE_TEST,
            'recipient' => $recipient,
            'sender' => $setting->phone_number,
            'message' => $message->text,
            'status' => WhatsAppNotification::PENDING,
            'triggered_by' => $actor?->id,
        ]);

        try {
            $result = $setting->hasSender()
                ? $this->gateway()->send($setting->phone_number, $setting->phone_number_id, $recipient, $message)
                : SendResult::failed('Nomor WhatsApp pengirim NUSAMA3D belum diatur.');
        } catch (Throwable $e) {
            $result = SendResult::failed($e->getMessage());
        }

        return $this->finish($log, $result, countFailure: false);
    }

    /** Periksa koneksi nomor pengirim dan simpan hasilnya pada pengaturan. */
    public function checkConnection(WhatsAppSetting $setting): WhatsAppSetting
    {
        if (! $setting->hasSender()) {
            $result = ConnectionResult::disconnected('Nomor WhatsApp pengirim belum diatur.');
        } else {
            try {
                $result = $this->gateway()->check($setting->phone_number);
            } catch (Throwable $e) {
                $result = ConnectionResult::disconnected($e->getMessage());
            }
        }

        $setting->forceFill([
            'phone_number_id' => $result->senderId,
            'connection_status' => $result->connected ? WhatsAppSetting::CONNECTED : WhatsAppSetting::DISCONNECTED,
            'connection_message' => mb_substr($result->message, 0, 500),
            'connection_checked_at' => now(),
        ])->save();

        return $setting;
    }

    public function failures(): int
    {
        return $this->failures;
    }

    private function finish(WhatsAppNotification $log, SendResult $result, bool $countFailure = true): WhatsAppNotification
    {
        $log->forceFill($result->successful
            ? ['status' => WhatsAppNotification::SENT, 'provider_message_id' => $result->messageId, 'sent_at' => now(), 'error_message' => null]
            : ['status' => WhatsAppNotification::FAILED, 'error_message' => $result->error]
        )->save();

        if (! $result->successful) {
            if ($countFailure) {
                $this->failures++;
            }

            Log::warning('Notifikasi WhatsApp gagal dikirim.', [
                'notification_id' => $log->id,
                'quotation_id' => $log->quotation_request_id,
                'error' => $result->error,
            ]);
        }

        return $log;
    }
}
