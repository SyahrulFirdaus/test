<?php

namespace App\Services;

use App\Models\PaymentTerm;
use App\Models\PaymentTermSetting;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Support\CustomerType;
use Illuminate\Support\Collection;

/**
 * Aturan dan hitungan pembagian termin.
 *
 * Satu tempat untuk dua pertanyaan yang jawabannya tidak boleh berbeda antara
 * halaman pelanggan, dashboard admin, dan pemeriksaan di controller:
 * pilihan cicilan mana yang terbuka untuk sebuah penawaran, dan berapa nominal
 * tiap terminnya.
 *
 * Seluruh pembagian dihitung dalam satuan sen (bilangan bulat) lalu sisa
 * pembagiannya dibebankan ke termin terakhir, sehingga penjumlahan seluruh
 * termin selalu persis sama dengan total penawaran — tidak pernah meleset satu
 * rupiah pun karena pembulatan.
 */
class PaymentTermPlanner
{
    /**
     * Pilihan payment term yang terbuka untuk nominal sekian.
     *
     * @return Collection<int, PaymentTermSetting>
     */
    public function optionsFor(float $amount): Collection
    {
        return PaymentTermSetting::query()
            ->enabled()
            ->ordered()
            ->get()
            ->filter(fn (PaymentTermSetting $setting) => $setting->acceptsAmount($amount))
            ->values();
    }

    /**
     * Jumlah termin yang boleh dipilih untuk nominal sekian.
     *
     * @return array<int, int>
     */
    public function allowedCounts(float $amount): array
    {
        return $this->optionsFor($amount)
            ->pluck('installment_count')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    public function allows(float $amount, int $installmentCount): bool
    {
        return in_array($installmentCount, $this->allowedCounts($amount), true);
    }

    /**
     * Penawaran ini boleh memakai fitur payment term.
     *
     * Terbatas pada pelanggan Business dengan total penawaran yang sudah
     * terbentuk. Pelanggan Personal sengaja tidak pernah lolos di sini — alur
     * pembayarannya tetap yang lama.
     */
    public function availableFor(QuotationRequest $quotation, ?User $user = null): bool
    {
        $owner = $user ?? $quotation->user;

        return $owner !== null
            && $owner->customer_type === CustomerType::BUSINESS
            && $this->quotationTotal($quotation) > 0;
    }

    /** Total penawaran yang menjadi dasar pembagian termin. */
    public function quotationTotal(QuotationRequest $quotation): float
    {
        return round((float) $quotation->payment_amount, 2);
    }

    /* ------------------------------------------------------- pembagian --- */

    /**
     * Bagi total menjadi sekian termin sama rata.
     *
     * Sisa pembagian yang tidak habis dibebankan ke termin terakhir, jadi
     * termin-termin awal tetap berupa angka bulat dan pelunasannya yang
     * menyesuaikan.
     *
     * @return array<int, float> nominal tiap termin, urut dari termin pertama
     */
    public function splitEvenly(float $total, int $installmentCount): array
    {
        $totalCents = (int) round($total * 100);
        $baseCents = intdiv($totalCents, $installmentCount);

        $amounts = array_fill(0, $installmentCount, $baseCents);
        $amounts[$installmentCount - 1] += $totalCents - ($baseCents * $installmentCount);

        return array_map(fn (int $cents) => round($cents / 100, 2), $amounts);
    }

    /**
     * Bagi total mengikuti persentase yang ditetapkan admin.
     *
     * Termin terakhir menyerap selisih pembulatan agar totalnya tetap utuh —
     * pembagian seperti 33,33% + 33,33% + 33,34% karenanya tidak menyisakan
     * kekurangan bayar.
     *
     * @param  array<int, float>  $percentages
     * @return array<int, float>
     */
    public function splitByPercentages(float $total, array $percentages): array
    {
        $percentages = array_values($percentages);
        $totalCents = (int) round($total * 100);
        $last = count($percentages) - 1;

        $amounts = [];
        $assigned = 0;

        foreach ($percentages as $index => $percentage) {
            if ($index === $last) {
                $amounts[] = round(($totalCents - $assigned) / 100, 2);

                break;
            }

            $cents = (int) round($totalCents * ((float) $percentage) / 100);
            $assigned += $cents;
            $amounts[] = round($cents / 100, 2);
        }

        return $amounts;
    }

    /**
     * Persentase yang mewakili nominal-nominal ini terhadap totalnya.
     *
     * @param  array<int, float>  $amounts
     * @return array<int, float>
     */
    public function percentagesFor(float $total, array $amounts): array
    {
        if ($total <= 0) {
            return array_map(fn () => 0.0, $amounts);
        }

        return array_map(fn (float $amount) => round($amount / $total * 100, 2), $amounts);
    }

    /**
     * Jadwal awal sebuah skema: nominal, persentase, milestone, dan jatuh tempo.
     *
     * Milestone bawaan mengikuti tahap pekerjaan yang lazim — DP di muka,
     * pelunasan sebelum pengiriman, dan sisanya mengikuti jalannya produksi.
     * Admin tetap dapat menggantinya sesuai kebutuhan tiap penawaran.
     *
     * @return array<int, array{installment_number: int, percentage: float, amount: float, milestone: string, due_date: \Illuminate\Support\Carbon}>
     */
    public function buildSchedule(float $total, int $installmentCount): array
    {
        $amounts = $this->splitEvenly($total, $installmentCount);
        $percentages = $this->percentagesFor($total, $amounts);
        $milestones = $this->defaultMilestones($installmentCount);
        $intervalDays = (int) config('payment.terms.interval_days', 7);

        $schedule = [];

        foreach ($amounts as $index => $amount) {
            $schedule[] = [
                'installment_number' => $index + 1,
                'percentage' => $percentages[$index],
                'amount' => $amount,
                'milestone' => $milestones[$index],
                // Termin pertama jatuh tempo lebih dulu, sisanya berjarak tetap
                // supaya jadwalnya langsung terbaca sejak disetujui.
                'due_date' => now()->addDays($intervalDays * ($index + 1))->startOfDay(),
            ];
        }

        return $schedule;
    }

    /**
     * Milestone bawaan untuk sekian termin.
     *
     * @return array<int, string>
     */
    public function defaultMilestones(int $installmentCount): array
    {
        $configured = (array) config('payment.terms.milestones', []);

        if (isset($configured[$installmentCount])) {
            return array_values((array) $configured[$installmentCount]);
        }

        // Skema di luar daftar tetap mendapat milestone yang masuk akal:
        // DP di awal, pelunasan sebelum pengiriman, produksi di antaranya.
        $milestones = ['DP / Order Confirmed'];

        for ($number = 2; $number < $installmentCount; $number++) {
            $milestones[] = 'Progres Produksi '.($number - 1);
        }

        if ($installmentCount > 1) {
            $milestones[] = 'Pelunasan Sebelum Pengiriman';
        }

        return $milestones;
    }

    /* -------------------------------------------------------- validasi --- */

    /**
     * Total persentase sudah tepat 100%.
     *
     * Toleransinya setipis 0,01 semata-mata untuk menampung penulisan desimal
     * seperti 33,33 + 33,33 + 33,34.
     *
     * @param  array<int, float>  $percentages
     */
    public function percentagesAddUp(array $percentages): bool
    {
        return abs(array_sum(array_map('floatval', $percentages)) - 100) < 0.011;
    }

    /**
     * Penjumlahan nominal termin sama persis dengan total penawaran.
     *
     * @param  array<int, float>  $amounts
     */
    public function amountsMatchTotal(float $total, array $amounts): bool
    {
        return (int) round(array_sum(array_map('floatval', $amounts)) * 100) === (int) round($total * 100);
    }

    /** Selisih antara jumlah seluruh termin dan total penawaran. */
    public function difference(PaymentTerm $term): float
    {
        $sum = (float) $term->installments->sum(fn ($installment) => (float) $installment->amount);

        return round($sum - (float) $term->total_amount, 2);
    }
}
