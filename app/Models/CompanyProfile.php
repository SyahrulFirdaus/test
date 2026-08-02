<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    protected $fillable = [
        'name',
        'tagline',
        'legal_name',
        'founded_year',
        'short_description',
        'about',
        'vision',
        'missions',
        'advantages',
        'stats',
        'address',
        'city',
        'phone',
        'whatsapp',
        'email',
        'operational_hours',
        'maps_url',
        'socials',
    ];

    protected function casts(): array
    {
        return [
            'missions' => 'array',
            'advantages' => 'array',
            'stats' => 'array',
            'socials' => 'array',
            'founded_year' => 'integer',
        ];
    }

    /**
     * Profil perusahaan bersifat singleton — hanya ada satu baris.
     * Dipakai oleh view composer supaya navbar & footer selalu punya datanya.
     */
    public static function current(): ?self
    {
        return static::query()->first();
    }

    /**
     * Nomor WhatsApp siap pakai untuk link wa.me.
     *
     * wa.me menuntut format internasional tanpa tanda plus, sedangkan nomor
     * biasanya ditulis dalam format lokal (0812…), jadi awalan 0 diganti 62.
     */
    public function getWhatsappLinkAttribute(): ?string
    {
        if (blank($this->whatsapp)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $this->whatsapp);

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '62')) {
            $digits = '62'.$digits;
        }

        return 'https://wa.me/'.$digits;
    }

    /** Lama perusahaan beroperasi dalam tahun. */
    public function getYearsOfExperienceAttribute(): ?int
    {
        return $this->founded_year ? max(0, now()->year - $this->founded_year) : null;
    }
}
