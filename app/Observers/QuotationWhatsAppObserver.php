<?php

namespace App\Observers;

use App\Models\QuotationRequest;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppNotifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Kirim notifikasi WhatsApp setiap kali Admin/Superadmin mengubah status penawaran.
 *
 * Dipasang sebagai observer — bukan disisipkan ke tiap controller — supaya
 * SELURUH jalur perubahan status yang sudah ada (dropdown status, keputusan
 * pembatalan, pembatalan massal, verifikasi pembayaran, termin) ikut memicu
 * notifikasi tanpa satu pun alur penawaran yang diubah.
 *
 * Aturannya:
 *  - hanya bila kolom `status` BENAR-BENAR berubah; menyimpan status yang
 *    sama (mis. hanya menambah catatan) tidak mengirim apa pun;
 *  - hanya bila perubahan dilakukan akun Admin/Superadmin; tindakan
 *    pelanggan sendiri tidak perlu dikabarkan balik kepadanya;
 *  - dikirim setelah transaksi basis data selesai, sehingga pelanggan tidak
 *    pernah menerima kabar atas perubahan yang ternyata dibatalkan;
 *  - kegagalan WhatsApp tidak pernah menggagalkan perubahan status.
 */
class QuotationWhatsAppObserver
{
    public function __construct(private readonly WhatsAppNotifier $notifier) {}

    public function updated(QuotationRequest $quotation): void
    {
        if (! $quotation->wasChanged('status')) {
            return;
        }

        $actor = Auth::user();

        if (! $actor instanceof User || ! $actor->isAdmin()) {
            return;
        }

        // Ditangkap sekarang: nilai asli tersinkron ulang begitu model selesai disimpan.
        $previous = $quotation->getOriginal('status');
        $status = (string) $quotation->status;

        DB::afterCommit(function () use ($quotation, $previous, $status, $actor) {
            $failuresBefore = $this->notifier->failures();

            $this->notifier->quotationStatusChanged($quotation, $previous, $status, $actor);

            if ($this->notifier->failures() > $failuresBefore) {
                $this->warnAdmin();
            }
        });
    }

    /** Beri tahu admin di halaman berikutnya, di samping pesan sukses yang sudah ada. */
    private function warnAdmin(): void
    {
        if (! app()->bound('request') || ! request()->hasSession()) {
            return;
        }

        $count = $this->notifier->failures();

        request()->session()->flash('whatsapp_warning', $count > 1
            ? $count.' notifikasi WhatsApp gagal dikirim ke pelanggan. Status penawaran tetap tersimpan; rinciannya ada di Pengaturan WhatsApp › Riwayat Pengiriman.'
            : 'Notifikasi WhatsApp gagal dikirim ke pelanggan. Status penawaran tetap tersimpan; rinciannya ada di Pengaturan WhatsApp › Riwayat Pengiriman.');
    }
}
