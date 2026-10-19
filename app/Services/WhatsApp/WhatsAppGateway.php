<?php

namespace App\Services\WhatsApp;

/**
 * Jalur pengiriman WhatsApp.
 *
 * Logika penawaran tidak pernah memanggil API WhatsApp secara langsung; ia
 * hanya berbicara dengan App\Services\WhatsApp\WhatsAppNotifier, dan notifier
 * memakai gateway ini. Mengganti penyedia berarti menambah implementasi baru
 * dan memilihnya lewat WHATSAPP_DRIVER — bukan menyentuh alur penawaran.
 */
interface WhatsAppGateway
{
    /**
     * Kirim satu pesan dari nomor pengirim ke `$recipient`.
     *
     * `$senderId` adalah pengenal nomor pengirim pada penyedia (Phone Number
     * ID pada Cloud API) bila sudah diketahui; gateway mencarinya sendiri bila
     * kosong. Tidak pernah melempar exception karena kegagalan pengiriman —
     * hasilnya selalu dikembalikan sebagai SendResult.
     */
    public function send(string $sender, ?string $senderId, string $recipient, WhatsAppMessage $message): SendResult;

    /**
     * Periksa apakah nomor pengirim siap dipakai, sekaligus mengembalikan
     * pengenalnya pada penyedia supaya dapat disimpan.
     */
    public function check(string $sender): ConnectionResult;
}
