<?php

namespace App\Models;

use App\Support\CustomerType;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    /**
     * Pemegang akses tertinggi: mengelola Admin, User, Price List, dan
     * Activity Log, serta melihat statistik keseluruhan sistem.
     *
     * Superadmin juga menjalankan seluruh tugas operasional Admin, jadi
     * `isAdmin()` ikut bernilai true baginya — lihat catatan di sana.
     */
    public const ROLE_SUPERADMIN = 'superadmin';

    /** Pengelola dashboard admin. */
    public const ROLE_ADMIN = 'admin';

    /** Pelanggan yang membuat penawaran. */
    public const ROLE_USER = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * `role` dan `is_active` sengaja TIDAK ada di sini: keduanya menentukan
     * hak akses, jadi hanya boleh dipasang eksplisit lewat forceFill() di
     * tempat yang memang berwenang (pendaftaran, menu Akun Admin, seeder) —
     * tidak pernah ikut terisi dari kiriman formulir.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
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
            'is_active' => 'boolean',
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

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    /**
     * Berhak atas area pengelola.
     *
     * Superadmin ikut di dalamnya dengan sengaja: seluruh tugas operasional
     * Admin — penawaran, verifikasi pembayaran, payment term — memang menjadi
     * haknya juga, jadi satu penjaga yang sama cukup untuk keduanya. Yang
     * membedakan keduanya adalah menu milik Superadmin sendiri (Price List,
     * User, Activity Log, Akun Admin), yang dijaga `EnsureUserIsSuperAdmin`.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_SUPERADMIN], true);
    }

    /**
     * Hak akses yang diberikan Superadmin kepada akun Admin ini.
     *
     * Hanya bermakna bagi role Admin; lihat hasAdminPermission().
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'admin_permissions')->withTimestamps();
    }

    /**
     * Boleh melakukan `$key`, mis. `quotation.view` atau `quotation.delete`.
     *
     *  - Superadmin: selalu boleh (akses penuh, tidak dibatasi hak akses Admin).
     *  - Admin aktif: hanya bila Superadmin menyalakan hak aksesnya.
     *  - Admin nonaktif dan pelanggan: tidak pernah.
     *
     * Pemeriksaan di route, controller, dan Blade memakai Gate yang
     * didaftarkan AppServiceProvider untuk setiap kunci — `can:`, `@can`,
     * `Gate::allows()` — dan semuanya berujung di sini.
     *
     * Daftarnya dibaca dari basis data sekali per permintaan (model User dimuat
     * ulang pada tiap permintaan), sehingga perubahan dari Superadmin langsung
     * berlaku dan tidak ada salinan lama yang tertinggal di session.
     */
    public function hasAdminPermission(string $key): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->isPlainAdmin() || ! $this->isActive()) {
            return false;
        }

        return in_array($key, $this->adminPermissionKeys(), true);
    }

    /** @return array<int, string> */
    public function adminPermissionKeys(): array
    {
        if (! $this->relationLoaded('permissions')) {
            $this->load('permissions');
        }

        return $this->permissions->pluck('key')->all();
    }

    /** Admin biasa, bukan Superadmin — dipakai menu Akun Admin. */
    public function isPlainAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isCustomer(): bool
    {
        return ! $this->isAdmin();
    }

    /** Akun nonaktif tetap tersimpan beserta jejaknya, tetapi tidak dapat masuk. */
    public function isActive(): bool
    {
        return (bool) ($this->is_active ?? true);
    }

    /** Seluruh pengelola: Admin maupun Superadmin. */
    public function scopeAdmins(Builder $query): Builder
    {
        return $query->whereIn('role', [self::ROLE_ADMIN, self::ROLE_SUPERADMIN]);
    }

    /** Hanya Admin biasa — daftar pada menu Akun Admin. */
    public function scopePlainAdmins(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_ADMIN);
    }

    public function scopeSuperAdmins(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_SUPERADMIN);
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
