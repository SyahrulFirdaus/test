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
        'google_reviews_url',
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
    /**
     * Tautan "Lihat semua ulasan di Google": URL profil Google Business yang
     * diisi di admin (menu Ulasan Google), atau pencarian Google Maps atas
     * nama & kota perusahaan selama URL itu belum diisi.
     */
    public function getGoogleReviewsLinkAttribute(): string
    {
        if (filled($this->google_reviews_url)) {
            return $this->google_reviews_url;
        }

        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode(trim($this->name.' '.$this->city));
    }

    /**
     * Tautan telepon format internasional (tel:+6281292388805) agar dapat
     * dibuka dari ponsel di mana pun, termasuk nomor yang tersimpan dalam
     * format lokal (0812…).
     */
    public function getPhoneLinkAttribute(): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $this->phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '62')) {
            $digits = '62'.$digits;
        }

        return 'tel:+'.$digits;
    }

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
