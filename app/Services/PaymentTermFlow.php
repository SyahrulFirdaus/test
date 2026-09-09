<?php

namespace App\Services;

use App\Models\PaymentInstallment;
use App\Models\PaymentProof;
use App\Models\PaymentTerm;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Notifications\InstallmentProofUploaded;
use App\Notifications\InstallmentUpdated;
use App\Notifications\PaymentTermDecided;
use App\Notifications\PaymentTermRequested;
use App\Support\ActivityAction;
use App\Support\InstallmentStatus;
use App\Support\PaymentTermStatus;
use App\Support\QuotationStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Alur pembayaran bertahap untuk pelanggan Business.
 *
 * Berdampingan dengan App\Services\PaymentFlow yang mengurus pembayaran sekali
 * bayar: alur lama tidak disentuh sama sekali, dan pelanggan Personal tidak
 * pernah melewati kelas ini.
 *
 * Seluruh perpindahan keadaan payment term maupun termin berkumpul di sini —
 * pengajuan, keputusan admin, aktivasi termin, unggah bukti, verifikasi,
 * keterlambatan, dan pengingat — supaya riwayat penawaran serta notifikasinya
 * selalu ikut tercatat dari mana pun perpindahan itu dipicu.
 */
class PaymentTermFlow
{
    public function __construct(
        private readonly PaymentTermPlanner $planner,
        private readonly ActivityLogger $activity,
    ) {}

    /* ------------------------------------------------------- pengajuan --- */

    /**
     * Pelanggan Business memilih skema pembayarannya.
     *
     * Pengajuan tidak pernah langsung berlaku: statusnya "Menunggu Persetujuan
     * Payment Term" sampai admin memutuskan. Pengajuan yang pernah ditolak
     * boleh diganti — barisnya dipakai ulang agar satu penawaran tetap punya
     * satu skema saja.
     */
    public function request(QuotationRequest $quotation, int $installmentCount, ?User $actor = null): PaymentTerm
    {
        $total = $this->planner->quotationTotal($quotation);

        $term = $quotation->paymentTerm;

        $attributes = [
            'total_amount' => $total,
            'installment_count' => $installmentCount,
            'status' => PaymentTermStatus::PENDING,
            'rejection_reason' => null,
            'requested_at' => now(),
            'approved_by' => null,
            'approved_at' => null,
            'completed_at' => null,
        ];

        if ($term === null) {
            $term = $quotation->paymentTerm()->create($attributes);
        } else {
            // Jadwal lama tidak boleh ikut terbawa ke pengajuan baru.
            $term->installments()->delete();
            $term->forceFill($attributes)->save();
        }

        $quotation->recordHistory(
            $quotation->status,
            'Pengajuan skema pembayaran '.$term->scheme_label.' menunggu persetujuan admin.',
            $actor?->name,
        );

        $this->activity->log(
            action: ActivityAction::PAYMENT_TERM_REQUEST,
            description: 'Mengajukan skema pembayaran '.$term->scheme_label
                .' untuk penawaran '.$quotation->tracking_number.'.',
            subject: $term,
            new: [
                'scheme' => $term->scheme_label,
                'installment_count' => $installmentCount,
                'total_amount' => $total,
                'status' => PaymentTermStatus::label(PaymentTermStatus::PENDING),
            ],
            actor: $actor,
            subjectLabel: $quotation->tracking_number,
        );

        Notification::send(User::admins()->get(), new PaymentTermRequested($term));

        return $term->refresh();
    }

    /* -------------------------------------------------- keputusan admin --- */

    /**
     * Admin menyetujui skema pembayaran.
     *
     * Jadwal terminnya dibentuk di sini — sebelum disetujui, terminnya memang
     * belum ada. Termin pertama langsung aktif supaya pelanggan dapat mulai
     * membayar, sisanya menunggu giliran.
     */
    public function approve(PaymentTerm $term, ?User $actor = null): PaymentTerm
    {
        DB::transaction(function () use ($term, $actor) {
            $term->forceFill([
                'status' => PaymentTermStatus::APPROVED,
                'rejection_reason' => null,
                'approved_by' => $actor?->id,
                'approved_at' => now(),
            ])->save();

            // Pilihan 1x tidak memerlukan jadwal termin: pembayarannya mengikuti
            // alur sekali bayar yang sudah ada.
            if ($term->isInstalment() && $term->installments()->count() === 0) {
                $this->buildSchedule($term);
            }
        });

        $term->refresh()->load('installments');

        if ($term->isInstalment()) {
            $this->activateFirstInstallment($term, $actor);
        }

        $term->quotation->recordHistory(
            $term->quotation->status,
            'Skema pembayaran '.$term->scheme_label.' disetujui admin.',
            $actor?->name,
        );

        $this->activity->log(
            action: ActivityAction::PAYMENT_TERM_APPROVE,
            description: 'Menyetujui skema pembayaran '.$term->scheme_label
                .' untuk penawaran '.$term->quotation->tracking_number.'.',
            subject: $term,
            old: ['status' => PaymentTermStatus::label(PaymentTermStatus::PENDING)],
            new: [
                'status' => PaymentTermStatus::label(PaymentTermStatus::APPROVED),
                'scheme' => $term->scheme_label,
            ],
            actor: $actor,
            subjectLabel: $term->quotation->tracking_number,
        );

        $term->quotation->user?->notify(new PaymentTermDecided($term, true));

        return $term;
    }

    /** Admin menolak skema pembayaran; pelanggan dapat mengajukan skema lain. */
    public function reject(PaymentTerm $term, ?string $reason = null, ?User $actor = null): PaymentTerm
    {
        $term->forceFill([
            'status' => PaymentTermStatus::REJECTED,
            'rejection_reason' => $reason,
            'approved_by' => $actor?->id,
            'approved_at' => now(),
        ])->save();

        // Jadwal yang mungkin sempat terbentuk dibersihkan agar tidak ada termin
        // menggantung tanpa skema yang berlaku.
        $term->installments()->each(fn (PaymentInstallment $installment) => $installment->delete());

        $term->quotation->recordHistory(
            $term->quotation->status,
            'Skema pembayaran '.$term->scheme_label.' ditolak admin.'.($reason ? ' Alasan: '.$reason : ''),
            $actor?->name,
        );

        $this->activity->log(
            action: ActivityAction::PAYMENT_TERM_REJECT,
            description: 'Menolak skema pembayaran '.$term->scheme_label
                .' untuk penawaran '.$term->quotation->tracking_number
                .($reason ? ' dengan alasan: '.$reason : '.'),
            subject: $term,
            old: ['status' => PaymentTermStatus::label(PaymentTermStatus::PENDING)],
            new: [
                'status' => PaymentTermStatus::label(PaymentTermStatus::REJECTED),
                'rejection_reason' => $reason,
            ],
            actor: $actor,
            subjectLabel: $term->quotation->tracking_number,
        );

        $term->quotation->user?->notify(new PaymentTermDecided($term, false, $reason));

        return $term->refresh();
    }

    /* --------------------------------------------------------- jadwal --- */

    /** Susun jadwal termin bawaan dari pembagian rata. */
    public function buildSchedule(PaymentTerm $term): void
    {
        foreach ($this->planner->buildSchedule((float) $term->total_amount, $term->installment_count) as $row) {
            $term->installments()->create([
                ...$row,
                'status' => InstallmentStatus::INACTIVE,
            ]);
        }
    }

    /**
     * Admin mengubah pembagian termin.
     *
     * Nominalnya dihitung ulang dari persentase yang ditetapkan admin memakai
     * App\Services\PaymentTermPlanner, jadi penjumlahannya tetap dijamin sama
     * dengan total penawaran. Termin yang sudah lunas tidak ikut berubah
     * nominalnya — uangnya sudah masuk — sehingga hanya milestone dan jatuh
     * temponya yang disesuaikan.
     *
     * @param  array<int, array{percentage: float, milestone: ?string, due_date: ?string}>  $rows
     */
    public function updateSchedule(PaymentTerm $term, array $rows, ?User $actor = null): void
    {
        $installments = $term->installments()->ordered()->get();
        $amounts = $this->planner->splitByPercentages((float) $term->total_amount, array_column($rows, 'percentage'));

        DB::transaction(function () use ($installments, $rows, $amounts) {
            foreach ($installments as $index => $installment) {
                $row = $rows[$index] ?? null;

                if ($row === null) {
                    continue;
                }

                $attributes = [
                    'milestone' => filled($row['milestone'] ?? null) ? trim((string) $row['milestone']) : null,
                    'due_date' => filled($row['due_date'] ?? null) ? $row['due_date'] : null,
                ];

                // Termin yang sudah dibayar dikunci nominalnya.
                if (! $installment->isPaid()) {
                    $attributes['percentage'] = (float) $row['percentage'];
                    $attributes['amount'] = $amounts[$index];
                }

                $installment->forceFill($attributes)->save();
            }
        });

        $term->quotation->recordHistory(
            $term->quotation->status,
            'Pembagian termin pembayaran disesuaikan admin.',
            $actor?->name,
        );
    }

    /* ------------------------------------------------ aktivasi termin --- */

    /** Aktifkan termin pertama begitu skemanya disetujui. */
    private function activateFirstInstallment(PaymentTerm $term, ?User $actor = null): void
    {
        $first = $term->installments->first();

        if ($first !== null && $first->status === InstallmentStatus::INACTIVE) {
            $this->activate($first, $actor);
        }
    }

    /**
     * Aktifkan satu termin sehingga dapat dibayar pelanggan.
     *
     * Dipanggil sistem saat termin sebelumnya lunas, atau manual oleh admin
     * bila milestone pekerjaannya sudah tercapai lebih awal.
     */
    public function activate(PaymentInstallment $installment, ?User $actor = null): void
    {
        if ($installment->status !== InstallmentStatus::INACTIVE) {
            return;
        }

        $installment->forceFill([
            'status' => InstallmentStatus::PENDING,
            'activated_at' => now(),
        ])->save();

        $quotation = $installment->term->quotation;

        $quotation->recordHistory(
            $quotation->status,
            $installment->title.' aktif dan menunggu pembayaran sebesar '.$this->rupiah($installment->amount).'.',
            $actor?->name ?? 'Sistem',
        );

        $quotation->user?->notify(new InstallmentUpdated(
            $installment,
            $installment->title.' Sudah Aktif',
            $installment->title.' sebesar '.$this->rupiah($installment->amount).' sudah aktif dan dapat dibayar'
                .($installment->due_date ? ', jatuh tempo '.$installment->due_date->translatedFormat('d F Y').'.' : '.'),
        ));
    }

    /* ---------------------------------------------------- bukti bayar --- */

    /**
     * Pelanggan mengunggah bukti pembayaran satu termin.
     *
     * Bukti lama tidak ditimpa: tiap unggahan menjadi baris baru pada
     * `payment_proofs`, sehingga percobaan yang pernah ditolak tetap terbaca
     * beserta alasannya.
     */
    public function submitProof(PaymentInstallment $installment, UploadedFile $file, ?User $actor = null): PaymentProof
    {
        $path = $file->storeAs(
            trim((string) config('payment.proof.directory', 'payments'), '/').'/termin/'.now()->format('Y-m'),
            Str::uuid().'.'.strtolower($file->getClientOriginalExtension()),
            'local'
        );

        $proof = $installment->proofs()->create([
            'file_path' => $path,
            'file_name' => Str::limit($file->getClientOriginalName(), 180, ''),
            'status' => InstallmentStatus::VERIFICATION,
            'uploaded_at' => now(),
        ]);

        $installment->forceFill(['status' => InstallmentStatus::VERIFICATION])->save();

        $quotation = $installment->term->quotation;

        $quotation->recordHistory(
            $quotation->status,
            'Bukti pembayaran '.$installment->title.' diunggah dan menunggu verifikasi admin.',
            $actor?->name,
        );

        $this->activity->log(
            action: ActivityAction::INSTALLMENT_PROOF_UPLOAD,
            description: 'Mengunggah bukti pembayaran '.$installment->title
                .' untuk penawaran '.$quotation->tracking_number.'.',
            subject: $installment,
            new: [
                'installment' => $installment->title,
                'amount' => $installment->amount,
                'file' => $proof->file_name,
                'status' => InstallmentStatus::label(InstallmentStatus::VERIFICATION),
            ],
            actor: $actor,
            subjectLabel: $quotation->tracking_number.' · '.$installment->title,
        );

        Notification::send(User::admins()->get(), new InstallmentProofUploaded($installment));

        return $proof;
    }

    /**
     * Admin menerima pembayaran satu termin.
     *
     * Termin berikutnya langsung diaktifkan, dan begitu seluruh termin lunas
     * skemanya ditandai selesai.
     */
    public function approveProof(PaymentInstallment $installment, ?User $actor = null): void
    {
        $proof = $installment->latestProof;

        DB::transaction(function () use ($installment, $proof, $actor) {
            $proof?->forceFill([
                'status' => InstallmentStatus::RECEIVED,
                'verified_by' => $actor?->id,
                'verified_at' => now(),
                'rejection_reason' => null,
            ])->save();

            $installment->forceFill([
                'status' => InstallmentStatus::RECEIVED,
                'paid_at' => now(),
            ])->save();
        });

        $term = $installment->term->fresh()->load('installments');
        $quotation = $term->quotation;

        $quotation->recordHistory(
            $quotation->status,
            'Pembayaran '.$installment->title.' sebesar '.$this->rupiah($installment->amount).' diterima.',
            $actor?->name,
        );

        $this->activity->log(
            action: ActivityAction::INSTALLMENT_APPROVE,
            description: 'Menerima pembayaran '.$installment->title
                .' penawaran '.$quotation->tracking_number.'.',
            subject: $installment,
            old: ['status' => InstallmentStatus::label(InstallmentStatus::VERIFICATION)],
            new: [
                'status' => InstallmentStatus::label(InstallmentStatus::RECEIVED),
                'amount' => $installment->amount,
            ],
            actor: $actor,
            subjectLabel: $quotation->tracking_number.' · '.$installment->title,
        );

        $quotation->user?->notify(new InstallmentUpdated(
            $installment,
            'Pembayaran '.$installment->title.' Diterima',
            'Bukti pembayaran '.$installment->title.' sebesar '.$this->rupiah($installment->amount)
                .' sudah diverifikasi dan diterima.',
        ));

        // Termin pertama yang lunas menandai pembayaran penawaran sudah berjalan,
        // sehingga pekerjaannya boleh masuk antrean produksi. Perpindahan status
        // penawaran selanjutnya tetap di tangan admin seperti biasa.
        if ($quotation->isPaymentStage()) {
            $this->markQuotationPaymentReceived($quotation, $actor);
        }

        if ($term->allInstallmentsPaid()) {
            $this->complete($term, $actor);

            return;
        }

        $next = $term->nextInactiveInstallment();

        if ($next !== null) {
            $this->activate($next);
        }
    }

    /**
     * Admin menolak bukti pembayaran satu termin.
     *
     * Terminnya tetap aktif — pelanggan mengunggah ulang bukti yang benar.
     */
    public function rejectProof(PaymentInstallment $installment, string $reason, ?User $actor = null): void
    {
        $proof = $installment->latestProof;

        DB::transaction(function () use ($installment, $proof, $reason, $actor) {
            $proof?->forceFill([
                'status' => InstallmentStatus::REJECTED,
                'verified_by' => $actor?->id,
                'verified_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            $installment->forceFill([
                'status' => $installment->isPastDue() ? InstallmentStatus::OVERDUE : InstallmentStatus::REJECTED,
            ])->save();
        });

        $quotation = $installment->term->quotation;

        $quotation->recordHistory(
            $quotation->status,
            'Bukti pembayaran '.$installment->title.' ditolak: '.$reason,
            $actor?->name,
        );

        $this->activity->log(
            action: ActivityAction::INSTALLMENT_REJECT,
            description: 'Menolak bukti pembayaran '.$installment->title
                .' penawaran '.$quotation->tracking_number.' dengan alasan: '.$reason,
            subject: $installment,
            old: ['status' => InstallmentStatus::label(InstallmentStatus::VERIFICATION)],
            new: [
                'status' => InstallmentStatus::label($installment->status),
                'rejection_reason' => $reason,
            ],
            actor: $actor,
            subjectLabel: $quotation->tracking_number.' · '.$installment->title,
        );

        $quotation->user?->notify(new InstallmentUpdated(
            $installment,
            'Pembayaran '.$installment->title.' Ditolak',
            'Bukti pembayaran '.$installment->title.' ditolak. Alasan: '.$reason
                .' Silakan unggah ulang bukti yang sesuai.',
        ));
    }

    /* --------------------------------------------------- penyelesaian --- */

    /** Seluruh termin lunas: skemanya selesai dan penawarannya ditandai lunas. */
    private function complete(PaymentTerm $term, ?User $actor = null): void
    {
        $term->forceFill([
            'status' => PaymentTermStatus::COMPLETED,
            'completed_at' => now(),
        ])->save();

        $quotation = $term->quotation;

        $quotation->recordHistory(
            $quotation->status,
            'Seluruh '.$term->installment_count.' termin pembayaran lunas. Total '
                .$this->rupiah($term->total_amount).' diterima.',
            $actor?->name,
        );

        $quotation->user?->notify(new InstallmentUpdated(
            $term->installments->last(),
            'Pembayaran Lunas',
            'Seluruh termin pembayaran penawaran '.$quotation->tracking_number.' sudah lunas. Terima kasih.',
        ));
    }

    /**
     * Pindahkan penawaran ke "Pembayaran Diterima".
     *
     * Dipakai saat termin pertama lunas: pembayaran sudah benar-benar berjalan,
     * jadi penawarannya tidak lagi tertahan di tahap pembayaran meski termin
     * berikutnya masih menunggu jadwalnya.
     */
    private function markQuotationPaymentReceived(QuotationRequest $quotation, ?User $actor = null): void
    {
        if ($quotation->status === QuotationStatus::PAYMENT_RECEIVED) {
            return;
        }

        $quotation->forceFill([
            'status' => QuotationStatus::PAYMENT_RECEIVED,
            'payment_verified_at' => now(),
            'payment_rejection_reason' => null,
        ])->save();

        $quotation->recordHistory(
            QuotationStatus::PAYMENT_RECEIVED,
            'Pembayaran termin pertama diterima, penawaran dilanjutkan.',
            $actor?->name,
        );
    }

    /* ------------------------------------------ keterlambatan & pengingat --- */

    /**
     * Tandai termin aktif yang batas waktunya sudah lewat.
     *
     * Berbeda dari pembayaran sekali bayar, terlambat membayar termin tidak
     * membatalkan penawaran — pekerjaannya sudah berjalan. Terminnya hanya
     * ditandai terlambat agar tertagih dan terpantau admin.
     *
     * @return int jumlah termin yang baru ditandai
     */
    public function markOverdue(): int
    {
        $installments = PaymentInstallment::query()
            ->whereIn('status', [InstallmentStatus::PENDING, InstallmentStatus::REJECTED])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->with('term.quotation.user')
            ->get();

        foreach ($installments as $installment) {
            $installment->forceFill(['status' => InstallmentStatus::OVERDUE])->save();

            $quotation = $installment->term->quotation;

            $quotation->recordHistory(
                $quotation->status,
                $installment->title.' melewati batas waktu pembayaran '
                    .$installment->due_date->translatedFormat('d F Y').'.',
                'Sistem',
            );

            $quotation->user?->notify(new InstallmentUpdated(
                $installment,
                'Pembayaran '.$installment->title.' Terlambat',
                $installment->title.' sebesar '.$this->rupiah($installment->amount)
                    .' melewati batas waktu '.$installment->due_date->translatedFormat('d F Y')
                    .'. Mohon segera diselesaikan.',
            ));
        }

        return $installments->count();
    }

    /**
     * Kirim pengingat jatuh tempo untuk termin yang sedang aktif.
     *
     * Tahap pengingatnya dibaca dari config/payment.php. Tiap tahap hanya
     * dikirim sekali per termin — yang terakhir terkirim dicatat pada kolom
     * `last_reminder_days` agar perintah terjadwal yang berjalan berulang kali
     * dalam sehari tidak membanjiri pelanggan dengan pesan yang sama.
     *
     * @return int jumlah pengingat yang dikirim
     */
    public function sendReminders(): int
    {
        // Diurut menaik supaya tahap yang terpilih adalah ambang terdekat dengan
        // jatuh tempo — tiga hari lagi mengirim pengingat "3 hari", bukan "7".
        $stages = collect((array) config('payment.terms.reminder_days', [7, 3, 1, 0]))
            ->map(fn ($days) => (int) $days)
            ->sort()
            ->values();

        if ($stages->isEmpty()) {
            return 0;
        }

        $installments = PaymentInstallment::query()
            ->whereIn('status', [InstallmentStatus::PENDING, InstallmentStatus::REJECTED])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', now()->toDateString())
            ->whereDate('due_date', '<=', now()->addDays($stages->max())->toDateString())
            ->with('term.quotation.user')
            ->get();

        $sent = 0;

        foreach ($installments as $installment) {
            $daysLeft = $installment->daysUntilDue();

            // Tahap yang berlaku adalah ambang terkecil yang sudah terlampaui,
            // jadi pengingat tetap terkirim meski perintahnya sempat tidak
            // berjalan pada hari persisnya.
            $stage = $stages->first(fn (int $threshold) => $daysLeft <= $threshold);

            if ($stage === null) {
                continue;
            }

            // Ambang tersimpan lebih kecil berarti pengingat tahap ini — atau
            // tahap yang lebih dekat — sudah pernah dikirim.
            if ($installment->last_reminder_days !== null && $installment->last_reminder_days <= $stage) {
                continue;
            }

            $installment->forceFill(['last_reminder_days' => $stage])->save();

            $installment->term->quotation->user?->notify(new InstallmentUpdated(
                $installment,
                'Pengingat Pembayaran '.$installment->title,
                $this->reminderMessage($installment, $stage),
            ));

            $sent++;
        }

        return $sent;
    }

    /** Kalimat pengingat sesuai jarak hari menuju jatuh tempo. */
    private function reminderMessage(PaymentInstallment $installment, int $stage): string
    {
        $amount = $this->rupiah($installment->amount);

        return match (true) {
            $stage <= 0 => 'Pembayaran '.$installment->title.' sebesar '.$amount.' jatuh tempo hari ini.',
            $stage === 1 => 'Pengingat: Pembayaran '.$installment->title.' sebesar '.$amount.' akan jatuh tempo besok.',
            default => 'Pengingat: Pembayaran '.$installment->title.' sebesar '.$amount
                .' akan jatuh tempo dalam '.$stage.' hari.',
        };
    }

    private function rupiah(float|string|null $value): string
    {
        return 'Rp'.number_format((float) $value, 0, ',', '.');
    }
}
