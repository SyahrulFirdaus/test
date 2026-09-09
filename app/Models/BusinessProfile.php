<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Profil perusahaan milik satu akun Business.
 *
 * Dikumpulkan sekali saat pendaftaran, lalu dibaca ulang setiap kali admin
 * memproses penawaran dari akun tersebut.
 */
class BusinessProfile extends Model
{
    /**
     * Bidang usaha yang dapat dipilih saat mendaftar.
     *
     * Ikut menentukan penggolongan pelanggan: bidang yang memang memproduksi
     * barang menandakan pelanggan industri. Lihat App\Services\CustomerSegmenter.
     *
     * @var array<int, string>
     */
    public const INDUSTRIES = [
        'Manufacturing',
        'Automotive',
        'Engineering',
        'Architecture',
        'Education',
        'Medical',
        'Consumer Product',
        'Research & Development',
        'Energy & Mining',
        'Aerospace',
        'Retail',
        'Other',
    ];

    protected $fillable = [
        'user_id',
        'company_name',
        'pic_name',
        'position',
        'phone',
        'email',
        'website',
        'industry',
        'address',
        'province_id',
        'regency_id',
        'district_id',
        'village_id',
        'postal_code',
        'segment',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function regency(): BelongsTo
    {
        return $this->belongsTo(Regency::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    /** Bagian wilayah saja, mis. "Melong, Cimahi Selatan, Kota Cimahi, Jawa Barat 40534". */
    public function getRegionLineAttribute(): string
    {
        $parts = array_filter([
            $this->village?->name,
            $this->district?->name,
            $this->regency?->name,
            $this->province?->name,
        ]);

        return trim(implode(', ', $parts).' '.(string) $this->postal_code);
    }

    /** Alamat perusahaan utuh dalam satu baris. */
    public function getFullAddressAttribute(): string
    {
        return trim(implode(', ', array_filter([
            trim((string) $this->address),
            $this->region_line,
        ])));
    }
}
