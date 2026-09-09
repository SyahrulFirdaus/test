<?php

namespace App\Models;

use App\Support\CustomerType;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Pengelola dashboard admin. */
    public const ROLE_ADMIN = 'admin';

    /** Pelanggan yang membuat penawaran. */
    public const ROLE_USER = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'customer_type',
        'phone',
        'city',
        'postal_code',
        'address',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Seluruh penawaran milik akun ini, terbaru di atas. */
    public function quotationRequests(): HasMany
    {
        return $this->hasMany(QuotationRequest::class)->orderByDesc('created_at');
    }

    /** Buku alamat pengiriman, alamat utama di urutan pertama. */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class)->defaultFirst();
    }

    /** Alamat yang terpilih lebih dulu saat meminta penawaran. */
    public function defaultAddress(): HasOne
    {
        return $this->hasOne(Address::class)->where('is_default', true);
    }

    /**
     * Profil perusahaan; hanya dimiliki akun Business.
     *
     * Dibaca admin sebelum memproses penawaran sehingga pelanggan tidak perlu
     * mengisi ulang data perusahaannya pada setiap permintaan.
     */
    public function businessProfile(): HasOne
    {
        return $this->hasOne(BusinessProfile::class);
    }

    /**
     * Jawaban yang diisi pemiliknya saat mendaftar.
     *
     * Diurutkan mengikuti urutan pertanyaannya supaya halaman detail pelanggan
     * di dashboard admin membacanya persis seperti urutan formulir.
     */
    public function customerAnswers(): HasMany
    {
        return $this->hasMany(CustomerAnswer::class);
    }

    public function isBusiness(): bool
    {
        return $this->customer_type === CustomerType::BUSINESS;
    }

    /** Label tipe pelanggan siap tampil, mis. "Business". */
    public function getCustomerTypeLabelAttribute(): string
    {
        return CustomerType::label($this->customer_type);
    }

    public function scopeOfCustomerType(Builder $query, ?string $type): Builder
    {
        return $query->when(
            CustomerType::exists($type),
            fn (Builder $q) => $q->where('customer_type', $type),
        );
    }

    /**
     * Jawaban pendaftaran yang sudah dirangkum: satu baris per pertanyaan,
     * jawaban ganda digabung menjadi satu tulisan.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function registrationSummary(): Collection
    {
        return $this->customerAnswers()
            ->with('question')
            ->get()
            ->filter(fn (CustomerAnswer $answer) => $answer->question !== null)
            ->sortBy([
                fn (CustomerAnswer $answer) => $answer->question->step,
                fn (CustomerAnswer $answer) => $answer->question->sort_order,
                fn (CustomerAnswer $answer) => $answer->question->id,
            ])
            ->groupBy(fn (CustomerAnswer $answer) => $answer->question_id)
            ->map(fn ($answers) => [
                'question' => $answers->first()->question->question,
                'step_label' => $answers->first()->question->step_label,
                'answer' => $answers->pluck('answer')->implode(', '),
            ])
            ->values();
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isCustomer(): bool
    {
        return ! $this->isAdmin();
    }

    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_ADMIN);
    }

    public function scopeCustomers(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_USER);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn (Builder $q) => $q->where(function (Builder $inner) use ($term) {
            $inner->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('city', 'like', "%{$term}%");
        }));
    }

    /**
     * Nomor telepon dalam format yang diterima wa.me.
     * Pelanggan umumnya mengisi format lokal (0812…), jadi awalan 0 diganti 62.
     */
    public function getWhatsappLinkAttribute(): ?string
    {
        if (blank($this->phone)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $this->phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '62')) {
            $digits = '62'.$digits;
        }

        return 'https://wa.me/'.$digits;
    }

    /** Inisial untuk avatar sederhana pada dashboard. */
    public function getInitialsAttribute(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }
}
