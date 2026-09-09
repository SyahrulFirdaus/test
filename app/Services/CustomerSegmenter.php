<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Pengelompokan pelanggan Business dari jawaban pendaftarannya.
 *
 * Tujuannya sederhana: admin dapat mengenali jenis pelanggan dalam sekali baca,
 * tanpa menelusuri lima belas jawaban satu per satu. Hasilnya disimpan pada
 * profil perusahaan dan ikut tampil di dashboard admin.
 *
 * Urutan penilaiannya dari yang paling menentukan ke yang paling umum:
 *
 *   Industrial Customer     industri manufaktur/otomotif dan memang berproduksi
 *   Manufacturing Customer  kebutuhannya produksi, berapa pun industrinya
 *   Prototype Customer      kebutuhannya prototype atau pengembangan produk
 *   Spare Part Customer     kebutuhannya penggantian komponen
 *   General Customer        belum cukup jawaban untuk menyimpulkan
 *
 * Penggolongan ini hanya alat bantu, bukan penentu harga atau layanan — jadi
 * salah golong tidak merugikan pelanggan, dan admin tetap dapat membaca
 * jawaban aslinya.
 */
class CustomerSegmenter
{
    public const GENERAL = 'General Customer';

    /** Industri yang bersifat produksi barang. */
    private const INDUSTRIAL = ['manufacturing', 'automotive', 'engineering'];

    /**
     * Tentukan segmen dari industri dan jawaban kebutuhan bisnisnya.
     *
     * @param  array<string, array<int, string>>  $answers  jawaban dikunci `key` pertanyaan
     */
    public function segment(?string $industry, array $answers): string
    {
        $mainNeed = Str::lower($this->first($answers, 'b_main_need'));
        $productionType = Str::lower($this->first($answers, 'b_production_type'));
        $industry = Str::lower((string) $industry);

        $isProduction = str_contains($productionType, 'production')
            || str_contains($mainNeed, 'production')
            || str_contains($mainNeed, 'functional');

        if ($isProduction && Str::contains($industry, self::INDUSTRIAL)) {
            return 'Industrial Customer';
        }

        if ($isProduction) {
            return 'Manufacturing Customer';
        }

        if (str_contains($mainNeed, 'prototype')
            || str_contains($productionType, 'prototype')
            || str_contains($mainNeed, 'product development')) {
            return 'Prototype Customer';
        }

        if (str_contains($mainNeed, 'replacement')) {
            return 'Spare Part Customer';
        }

        return self::GENERAL;
    }

    /**
     * Ringkasan jawaban untuk panel "Business Insights" di dashboard admin.
     *
     * Hanya menyebut hal yang benar-benar dijawab: pertanyaan opsional yang
     * dilewati tidak ditampilkan sebagai baris kosong.
     *
     * @param  array<string, array<int, string>>  $answers
     * @return array<string, string>
     */
    public function insights(array $answers): array
    {
        $summary = [
            'Kebutuhan Utama' => $this->join($answers, 'b_main_need'),
            'Jenis Produksi' => $this->join($answers, 'b_production_type'),
            'Frekuensi Order' => $this->join($answers, 'b_frequency'),
            'Jumlah per Produksi' => $this->join($answers, 'b_quantity'),
            'Teknologi' => $this->join($answers, 'b_technologies'),
            'Material' => $this->join($answers, 'b_materials'),
            'Finishing' => $this->join($answers, 'b_finishing'),
            'Timeline' => $this->join($answers, 'b_deadline'),
            'Prioritas Timeline' => $this->join($answers, 'b_timeline_priority'),
            'Estimasi Budget' => $this->join($answers, 'b_budget'),
            'Proses Procurement' => $this->join($answers, 'b_procurement'),
        ];

        return array_filter($summary, fn (string $value) => $value !== '');
    }

    /**
     * Apakah pelanggan ini meminta dokumen resmi.
     *
     * Ditampilkan terpisah karena langsung memengaruhi cara admin menyiapkan
     * penawarannya.
     *
     * @param  array<string, array<int, string>>  $answers
     * @return array<int, string>
     */
    public function documentNeeds(array $answers): array
    {
        return array_values(array_filter([
            $this->first($answers, 'b_need_quotation') === 'Ya' ? 'Quotation resmi' : null,
            $this->first($answers, 'b_need_invoice') === 'Ya' ? 'Invoice perusahaan' : null,
        ]));
    }

    /** @param  array<string, array<int, string>>  $answers */
    private function first(array $answers, string $key): string
    {
        return (string) ($answers[$key][0] ?? '');
    }

    /** @param  array<string, array<int, string>>  $answers */
    private function join(array $answers, string $key): string
    {
        return implode(', ', (array) ($answers[$key] ?? []));
    }
}
