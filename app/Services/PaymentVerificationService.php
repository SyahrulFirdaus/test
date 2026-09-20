<?php

namespace App\Services;

use App\Models\PaymentInstallment;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Support\AdminPermission;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Satu pintu keputusan pembayaran: diterima atau ditolak.
 *
 * Sebelum ini keputusannya tersebar — controller menu Pembayaran memanggil
 * PaymentFlow, dropdown status Detail Penawaran memanggil jalurnya sendiri, dan
 * masing-masing memeriksa haknya dengan caranya sendiri. Satu pembayaran karena
 * itu dapat diputuskan dari dua tempat dengan pemeriksaan yang tidak persis
 * sama.
 *
 * Kelas ini menutup celah itu: SELURUH keputusan pembayaran — sekali bayar
 * maupun per termin — melewati sini lebih dulu. Yang dikerjakannya sama untuk
 * keduanya:
 *
 *   1. memeriksa hak akses pelakunya;
 *   2. memeriksa keadaan pembayarannya (ada bukti, dan memang menunggu);
 *   3. memeriksa bahwa pembayaran itu memang diverifikasi dari tempat ini;
 *   4. menjalankan perpindahannya dalam SATU transaksi.
 *
 * Perpindahan statusnya sendiri tetap milik App\Services\PaymentFlow dan
 * App\Services\PaymentTermFlow — keduanya sudah memegang riwayat, Activity Log,
 * dan notifikasi, dan tidak disalin ulang ke sini. Yang ditambahkan hanyalah
 * lapisan pemeriksaan dan pencatatan siapa yang memutuskan.
 */
class PaymentVerificationService
{
    public function __construct(
        private readonly PaymentFlow $payments,
        private readonly PaymentTermFlow $terms,
    ) {}

    /* ============================================ sekali bayar (Detail) === */

    /**
     * Terima pembayaran sekali bayar dari Detail Penawaran.
     *
     * Statusnya berpindah ke "Pembayaran Diterima" dan berhenti di situ:
     * tahap sesudahnya (Diproses) adalah keputusan produksi, bukan keputusan
     * pembayaran, dan tetap dipasang admin lewat Tindak Lanjut seperti biasa.
     */
    public function acceptQuotationPayment(QuotationRequest $quotation, User $actor, ?string $note = null): void
    {
        $this->authorize($actor);
        $this->guardQuotation($quotation);

        DB::transaction(fn () => $this->payments->approve($quotation, $actor, $note));
    }

    /**
     * Tolak pembayaran sekali bayar beserta alasannya.
     *
     * Penawaran tidak dibatalkan: selama batas waktunya belum lewat, pemiliknya
     * masih dapat mengunggah bukti yang benar — aturan yang sudah berlaku
     * sebelumnya dan tidak diubah di sini.
     */
    public function rejectQuotationPayment(QuotationRequest $quotation, string $reason, User $actor): void
    {
        $this->authorize($actor);
        $this->guardQuotation($quotation);

        DB::transaction(fn () => $this->payments->reject($quotation, $reason, $actor));
    }

    /* ================================================ termin (Pembayaran) === */

    /** Terima bukti pembayaran satu termin; termin berikutnya ikut diaktifkan. */
    public function acceptInstallment(PaymentInstallment $installment, User $actor): void
    {
        $this->authorize($actor);
        $this->guardInstallment($installment);

        DB::transaction(fn () => $this->terms->approveProof($installment, $actor));
    }

    /** Tolak bukti pembayaran satu termin beserta alasannya. */
    public function rejectInstallment(PaymentInstallment $installment, string $reason, User $actor): void
    {
        $this->authorize($actor);
        $this->guardInstallment($installment);

        DB::transaction(fn () => $this->terms->rejectProof($installment, $reason, $actor));
    }

    /* ============================================================ penjaga === */

    /**
     * Hak memutuskan pembayaran.
     *
     * Diperiksa di sini, bukan hanya di route: tombolnya memang disembunyikan
     * bagi yang tidak berhak, tetapi endpoint-nya tetap dapat dipanggil
     * sendiri. Superadmin selalu lolos — lihat User::hasAdminPermission().
     */
    private function authorize(User $actor): void
    {
        if (! $actor->isAdmin() || ! $actor->can(AdminPermission::PAYMENT_VERIFY)) {
            throw new AuthorizationException('Anda tidak berhak memverifikasi pembayaran.');
        }
    }

    /**
     * Pembayaran sekali bayar yang memang menunggu keputusan DI SINI.
     *
     * Penawaran yang berjalan dengan termin sengaja ditolak walaupun statusnya
     * kebetulan sama: pembayarannya punya antreannya sendiri, dan menerimanya
     * dari sini akan melompati jadwal terminnya.
     */
    private function guardQuotation(QuotationRequest $quotation): void
    {
        if (! $quotation->verifiedFromDetail()) {
            throw new RuntimeException(
                'Penawaran ini memakai pembayaran bertahap. Verifikasi tiap terminnya dari menu Pembayaran › Verifikasi Pembayaran.'
            );
        }

        if (! $quotation->awaitsPaymentDecision()) {
            throw new RuntimeException('Tidak ada bukti pembayaran yang menunggu verifikasi pada penawaran ini.');
        }

        /*
         * Harus ada bukti yang benar-benar dikirim pelanggan.
         *
         * Yang diperiksa keberadaan KIRIMANNYA, bukan berkasnya masih terbaca
         * di disk: berkas yang hilang tidak boleh membuat penawaran terkunci
         * selamanya — admin masih dapat memutuskannya dari mutasi rekening,
         * dan hilangnya berkas sudah terlihat sendiri saat tombol Lihat Bukti
         * ditekan.
         */
        if (blank($quotation->payment_proof_path)) {
            throw new RuntimeException('Pelanggan belum mengunggah bukti pembayaran, jadi belum ada yang dapat diverifikasi.');
        }
    }

    private function guardInstallment(PaymentInstallment $installment): void
    {
        if (! $installment->isAwaitingVerification()) {
            throw new RuntimeException('Tidak ada bukti pembayaran yang menunggu verifikasi pada termin ini.');
        }
    }
}
