<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu alamat pengiriman milik pelanggan.
 *
 * Akun boleh menyimpan beberapa alamat dan menandai satu sebagai alamat utama.
 * Yang utama itulah yang terpilih lebih dulu pada formulir "Minta Penawaran",
 * dan yang dicerminkan kembali ke kolom `city`, `postal_code`, dan `address`
 * pada tabel `users` — lihat App\Services\AddressBook.
 *
 * Alamat hasil pemindahan akun lama bisa saja belum lengkap: wilayahnya dulu
 * ditulis bebas sehingga tidak selalu cocok dengan daftar resmi. `isComplete()`
 * membedakan keduanya agar pemiliknya dapat diingatkan untuk melengkapinya.
 */
class Address extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'recipient_phone',
        'province_id',
        'regency_id',
        'district_id',
        'village_id',
        'postal_code',
        'detail',
        'note',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

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

    /** Relasi wilayah yang selalu ikut dimuat saat alamat ditampilkan. */
    public const REGION_RELATIONS = ['province', 'regency', 'district', 'village'];

    public function scopeDefaultFirst(Builder $query): Builder
    {
        return $query->orderByDesc('is_default')->orderBy('label')->orderBy('id');
    }

    /**
     * Apakah seluruh bagian alamat sudah terisi.
     *
     * Alamat yang belum lengkap tetap tersimpan dan tetap boleh dipakai; yang
     * membedakannya hanya penanda di menu Alamat yang meminta pemiliknya
     * melengkapi wilayahnya.
     */
    public function isComplete(): bool
    {
        foreach (['recipient_name', 'recipient_phone', 'province_id', 'regency_id', 'district_id', 'village_id', 'postal_code', 'detail'] as $field) {
            if (blank($this->{$field})) {
                return false;
            }
        }

        return true;
    }

    /** Bagian wilayah saja, mis. "Coblong, Kota Bandung, Jawa Barat 40132". */
    public function getRegionLineAttribute(): string
    {
        $parts = array_filter([
            $this->village?->name,
            $this->district?->name,
            $this->regency?->name,
            $this->province?->name,
        ]);

        $line = implode(', ', $parts);

        return trim($line.' '.(string) $this->postal_code);
    }

    /** Alamat utuh dalam satu baris, dipakai ringkasan dan dokumen penawaran. */
    public function getFullAddressAttribute(): string
    {
        return trim(implode(', ', array_filter([
            trim((string) $this->detail),
            $this->region_line,
        ])));
    }

    /** Label beserta nama penerimanya, mis. "Kantor · Budi Hartono". */
    public function getDisplayLabelAttribute(): string
    {
        return implode(' · ', array_filter([trim((string) $this->label), trim((string) $this->recipient_name)]));
    }

    /**
     * Salinan alamat untuk disimpan pada penawaran.
     *
     * Penawaran menyimpan salinan ini, bukan sekadar menunjuk barisnya, supaya
     * tujuan pengiriman yang sudah tercatat tidak ikut berubah ketika
     * pemiliknya menyunting atau menghapus alamatnya di kemudian hari.
     *
     * @return array<string, mixed>
     */
    public function toSnapshot(): array
    {
        return [
            'label' => $this->label,
            'recipient_name' => $this->recipient_name,
            'recipient_phone' => $this->recipient_phone,
            'province' => $this->province?->name,
            'city' => $this->regency?->name,
            'district' => $this->district?->name,
            'village' => $this->village?->name,
            'postal_code' => $this->postal_code,
            'detail' => $this->detail,
            'note' => $this->note,
            'full' => $this->full_address,
        ];
    }
}
