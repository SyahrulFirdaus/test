<?php

namespace App\Services;

use App\Models\QuotationRequest;
use App\Models\User;
use App\Notifications\PaymentProofUploaded;
use App\Notifications\QuotationStatusUpdated;
use App\Support\ActivityAction;
use App\Support\ActorType;
use App\Support\QuotationStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Alur pembayaran penawaran.
 *
 * Satu tempat untuk seluruh perpindahan status seputar pembayaran — membuka
 * jendela 24 jam, menerima bukti transfer, keputusan admin, dan pembatalan
 * otomatis saat batas waktunya lewat — supaya riwayat dan notifikasinya selalu
 * ikut tercatat, dari mana pun perpindahan itu dipicu.
 */
class PaymentFlow
{
    /*
     * Seluruh perpindahan status pembayaran melewati kelas ini, jadi jejak
     * auditnya ikut dicatat di sini — bukan di masing-masing controller.
     * Dengan begitu pembatalan otomatis oleh perintah terjadwal pun tetap
     * meninggalkan jejak, sama seperti keputusan yang dipicu admin.
     */
    public function __construct(private readonly ActivityLogger $activity) {}

    /**
     * Buka jendela pembayaran.
     *
     * Dipanggil saat admin memindahkan status ke "Menunggu Pembayaran". Bukti
     * lama beserta jejak keputusannya dibersihkan supaya jendela baru tidak
     * mewarisi berkas dari percobaan pembayaran sebelumnya.
     */
    public function open(QuotationRequest $quotation): void
    {
        // Penawaran yang berjalan dengan pembayaran bertahap punya jadwal
        // terminnya sendiri; jendela 24 jam sekali bayar tidak berlaku dan
        // tidak boleh membatalkannya otomatis.
        if ($quotation->usesInstallments()) {
            return;
        }

        $this->deleteProofFile($quotation);

        $quotation->forceFill([
            'payment_due_at' => now()->addHours((int) config('payment.window_hours', 24)),
            'payment_proof_path' => null,
            'payment_proof_name' => null,
            'payment_proof_uploaded_at' => null,
            'payment_verified_at' => null,
            'payment_verified_by' => null,
            'payment_rejected_at' => null,
            'payment_rejected_by' => null,
            'payment_rejection_reason' => null,
        ])->save();

        $this->activity->log(
            action: ActivityAction::PAYMENT_AWAITING,
            description: 'Penawaran '.$quotation->tracking_number.' masuk tahap Menunggu Pembayaran.',
            subject: $quotation,
            new: [
                'status' => QuotationStatus::label(QuotationStatus::AWAITING_PAYMENT),
                'payment_due_at' => $quotation->payment_due_at?->format('d M Y H:i'),
            ],
        );
    }

    /**
     * Simpan bukti pembayaran dan pindahkan status ke "Pengecekan Pembayaran".
     *
     * Berkasnya disimpan pada disk privat `local`, sama seperti berkas model,
     * sehingga hanya dapat dibuka pemiliknya sendiri atau admin.
     */
    public function submitProof(QuotationRequest $quotation, UploadedFile $file, ?User $actor = null): void
    {
        $statusBefore = $quotation->status;

        $this->deleteProofFile($quotation);

        $path = $file->storeAs(
            trim((string) config('payment.proof.directory', 'payments'), '/').'/'.now()->format('Y-m'),
            Str::uuid().'.'.strtolower($file->getClientOriginalExtension()),
            'local'
        );

        $quotation->forceFill([
            'status' => QuotationStatus::PAYMENT_REVIEW,
            'payment_proof_path' => $path,
            'payment_proof_name' => Str::limit($file->getClientOriginalName(), 180, ''),
            'payment_proof_uploaded_at' => now(),
            'payment_rejection_reason' => null,
            'payment_verified_at' => null,
            'payment_verified_by' => null,
            'payment_rejected_at' => null,
            'payment_rejected_by' => null,
        ])->save();

        $quotation->recordHistory(
            QuotationStatus::PAYMENT_REVIEW,
            'Bukti pembayaran diunggah dan menunggu verifikasi admin.',
            $actor?->name,
        );

        // Nama berkasnya ikut dicatat — itu yang dicocokkan admin ketika
        // menelusuri bukti transfer di kemudian hari. Isi berkasnya sendiri
        // tetap hanya di disk privat.
        $this->activity->log(
            action: ActivityAction::PAYMENT_PROOF_UPLOAD,
            description: 'Mengunggah bukti pembayaran untuk penawaran '.$quotation->tracking_number.'.',
            subject: $quotation,
            old: ['status' => QuotationStatus::label($statusBefore)],
            new: [
                'status' => QuotationStatus::label(QuotationStatus::PAYMENT_REVIEW),
                'file' => $quotation->payment_proof_name,
            ],
            actor: $actor,
        );

        // Admin diberi tahu agar bukti yang masuk tidak menunggu terlalu lama.
        Notification::send(User::admins()->get(), new PaymentProofUploaded($quotation));
    }

    /** Admin menerima pembayaran. */
    public function approve(QuotationRequest $quotation, ?User $actor = null, ?string $note = null): void
    {
        $statusBefore = $quotation->status;

        $quotation->forceFill([
            'status' => QuotationStatus::PAYMENT_RECEIVED,
            'payment_verified_at' => now(),
            // Siapa yang menerimanya ikut tercatat, bukan hanya kapan: sejak
            // keputusannya diambil dari Detail Penawaran, lebih banyak orang
            // melewati tombol ini.
            'payment_verified_by' => $actor?->getKey(),
            'payment_rejected_at' => null,
            'payment_rejected_by' => null,
            'payment_rejection_reason' => null,
        ])->save();

        $quotation->recordHistory(
            QuotationStatus::PAYMENT_RECEIVED,
            $note ?? 'Pembayaran diverifikasi dan diterima.',
            $actor?->name,
        );

        $this->activity->log(
            action: ActivityAction::PAYMENT_APPROVE,
            description: 'Menerima pembayaran penawaran '.$quotation->tracking_number.'.',
            subject: $quotation,
            old: ['status' => QuotationStatus::label($statusBefore)],
            new: ['status' => QuotationStatus::label(QuotationStatus::PAYMENT_RECEIVED)],
            actor: $actor,
        );

        $quotation->user?->notify(new QuotationStatusUpdated($quotation, QuotationStatus::PAYMENT_RECEIVED, $note));
    }

    /**
     * Admin menolak pembayaran.
     *
     * Penawaran tidak dibatalkan: pemiliknya masih dapat mengunggah ulang bukti
     * yang benar selama batas waktunya belum lewat.
     */
    public function reject(QuotationRequest $quotation, string $reason, ?User $actor = null): void
    {
        $statusBefore = $quotation->status;

        $quotation->forceFill([
            'status' => QuotationStatus::PAYMENT_REJECTED,
            // Penolakan BUKAN verifikasi. Sebelumnya keduanya memakai kolom
            // yang sama, sehingga penawaran yang buktinya ditolak ikut terbaca
            // "Paid" pada dashboard Business.
            'payment_verified_at' => null,
            'payment_verified_by' => null,
            'payment_rejected_at' => now(),
            'payment_rejected_by' => $actor?->getKey(),
            'payment_rejection_reason' => $reason,
        ])->save();

        $quotation->recordHistory(
            QuotationStatus::PAYMENT_REJECTED,
            'Pembayaran ditolak: '.$reason,
            $actor?->name,
        );

        // Alasan penolakan ikut tercatat: itulah yang perlu dibaca ulang bila
        // pelanggan mempertanyakan keputusannya di kemudian hari.
        $this->activity->log(
            action: ActivityAction::PAYMENT_REJECT,
            description: 'Menolak pembayaran penawaran '.$quotation->tracking_number.' dengan alasan: '.$reason,
            subject: $quotation,
            old: ['status' => QuotationStatus::label($statusBefore)],
            new: [
                'status' => QuotationStatus::label(QuotationStatus::PAYMENT_REJECTED),
                'rejection_reason' => $reason,
            ],
            actor: $actor,
        );

        $quotation->user?->notify(new QuotationStatusUpdated($quotation, QuotationStatus::PAYMENT_REJECTED, $reason));
    }

    /**
     * Batalkan penawaran yang melewati batas waktu pembayaran.
     *
     * @return bool apakah penawaran ini benar-benar dibatalkan
     */
    public function expireIfDue(QuotationRequest $quotation): bool
    {
        if (! $quotation->paymentExpired()) {
            return false;
        }

        $statusBefore = $quotation->status;

        $quotation->forceFill([
            'status' => QuotationStatus::PAYMENT_EXPIRED,
            'status_before_cancellation' => $quotation->status,
            'cancellation_resolved_at' => now(),
        ])->save();

        $quotation->recordHistory(
            QuotationStatus::PAYMENT_EXPIRED,
            'Batas waktu pembayaran '.config('payment.window_hours', 24).' jam terlewati, penawaran dibatalkan otomatis oleh sistem.',
            'Sistem',
        );

        // Pelakunya sistem, bukan orang: pembatalan otomatis ini kerap berjalan
        // dari perintah terjadwal tanpa sesi siapa pun.
        $this->activity->log(
            action: ActivityAction::PAYMENT_EXPIRED,
            description: 'Batas waktu pembayaran penawaran '.$quotation->tracking_number
                .' terlewati, penawaran dibatalkan otomatis.',
            subject: $quotation,
            old: ['status' => QuotationStatus::label($statusBefore)],
            new: ['status' => QuotationStatus::label(QuotationStatus::PAYMENT_EXPIRED)],
            actor: null,
            userName: 'Sistem',
            userType: ActorType::ADMIN,
        );

        $quotation->user?->notify(new QuotationStatusUpdated($quotation, QuotationStatus::PAYMENT_EXPIRED));

        return true;
    }

    /**
     * Sapu seluruh penawaran yang batas pembayarannya lewat.
     *
     * @return int jumlah penawaran yang dibatalkan
     */
    public function expireOverdue(): int
    {
        return QuotationRequest::query()
            ->whereIn('status', [QuotationStatus::AWAITING_PAYMENT, QuotationStatus::PAYMENT_REJECTED])
            ->whereNotNull('payment_due_at')
            ->where('payment_due_at', '<', now())
            ->get()
            ->filter(fn (QuotationRequest $quotation) => $this->expireIfDue($quotation))
            ->count();
    }

    private function deleteProofFile(QuotationRequest $quotation): void
    {
        if ($quotation->paymentProofExists()) {
            Storage::disk('local')->delete($quotation->payment_proof_path);
        }
    }
}
