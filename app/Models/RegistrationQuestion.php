<?php

namespace App\Models;

use App\Support\CustomerType;
use App\Support\MaterialCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu pertanyaan pada formulir pendaftaran.
 *
 * Halaman registrasi tidak memuat daftar pertanyaan apa pun di dalam kodenya:
 * langkah, judul langkah, urutan, jenis input, dan pilihan jawabannya
 * seluruhnya dibaca dari tabel ini. Menambah pertanyaan cukup dilakukan lewat
 * seeder atau langsung di basis data.
 *
 * Dua pertanyaan mengambil pilihannya dari data sistem lewat `options_source`
 * agar tidak pernah bertentangan dengan katalog yang sebenarnya tersedia:
 * teknologi dibaca dari tabel `technologies`, material dari katalog printing.
 */
class RegistrationQuestion extends Model
{
    /** Pilihan tambahan bagi penjawab yang kebutuhannya belum terdaftar. */
    public const OTHER = 'Lainnya';

    protected $fillable = [
        'customer_type',
        'key',
        'question',
        'help',
        'type',
        'options',
        'options_source',
        'placeholder',
        'step',
        'step_label',
        'category',
        'category_order',
        'depends_on_key',
        'depends_on_value',
        'sort_order',
        'is_required',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'step' => 'integer',
            'category_order' => 'integer',
            'sort_order' => 'integer',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function answers(): HasMany
    {
        return $this->hasMany(CustomerAnswer::class, 'question_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('step')
            ->orderBy('category_order')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function scopeForType(Builder $query, string $customerType): Builder
    {
        return $query->where('customer_type', $customerType);
    }

    /** Nama field pada formulir, mis. "questions[12]". */
    public function getFieldNameAttribute(): string
    {
        return 'questions.'.$this->id;
    }

    public function isMultiple(): bool
    {
        return $this->type === 'checkbox';
    }

    public function isChoice(): bool
    {
        return in_array($this->type, ['radio', 'select', 'checkbox'], true);
    }

    /** Apakah pertanyaan ini hanya muncul setelah pertanyaan lain dijawab tertentu. */
    public function isConditional(): bool
    {
        return filled($this->depends_on_key);
    }

    /**
     * Pilihan jawaban yang benar-benar berlaku.
     *
     * Pertanyaan dengan `options_source` mengambil daftarnya dari data sistem,
     * jadi teknologi atau material yang ditambahkan kemudian langsung ikut
     * muncul tanpa perlu memperbarui pertanyaannya.
     *
     * @return array<int, string>
     */
    public function resolvedOptions(): array
    {
        if (! $this->isChoice()) {
            return [];
        }

        $options = match ($this->options_source) {
            'technologies' => self::technologyOptions(),
            'materials' => self::materialOptions(),
            default => array_values((array) $this->options),
        };

        return array_values(array_unique(array_filter(
            array_map(fn ($option) => (string) $option, $options),
            fn (string $option) => $option !== '',
        )));
    }

    /**
     * Kode teknologi yang tersedia, mis. FDM, SLA, MJF, SLM.
     *
     * Dibaca dari tabel `technologies` yang sudah dipakai halaman Teknologi,
     * bukan ditulis ulang di sini.
     *
     * @return array<int, string>
     */
    public static function technologyOptions(): array
    {
        return Technology::query()
            ->active()
            ->ordered()
            ->pluck('code')
            ->push(self::OTHER)
            ->all();
    }

    /**
     * Nama material yang tersedia pada seluruh teknologi.
     *
     * Katalog material adalah satu-satunya sumbernya, jadi daftar di formulir
     * pendaftaran tidak pernah menyebut material yang sebenarnya tidak dilayani.
     *
     * @return array<int, string>
     */
    public static function materialOptions(): array
    {
        return collect(MaterialCatalog::technologies())
            ->flatMap(fn (array $technology) => array_column($technology['materials'], 'name'))
            ->unique()
            ->sort()
            ->push(self::OTHER)
            ->values()
            ->all();
    }

    /** Label tipe pelanggan yang ditampilkan kepada pengguna. */
    public function getCustomerTypeLabelAttribute(): string
    {
        return CustomerType::label($this->customer_type);
    }
}
