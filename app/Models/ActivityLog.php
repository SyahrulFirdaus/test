<?php

namespace App\Models;

use App\Support\ActivityAction;
use App\Support\ActivityModule;
use App\Support\ActivityStatus;
use App\Support\ActorType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Satu baris jejak audit.
 *
 * Baris log tidak pernah diubah setelah tersimpan — halaman Activity Logs
 * hanya membacanya. Karena itu nama pelaku dan penanda datanya ikut dibekukan
 * di sini, bukan selalu dibaca ulang dari relasinya: riwayat harus tetap
 * menceritakan keadaan saat aktivitasnya terjadi.
 *
 * Pencatatannya sendiri berada di App\Services\ActivityLogger.
 */
class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'user_type',
        'action',
        'module',
        'description',
        'subject_type',
        'subject_id',
        'subject_label',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    /** Akun pelakunya; kosong bila akunnya sudah dihapus. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Data yang disentuh aktivitas ini.
     *
     * Relasinya dibiarkan longgar: data yang dirujuk boleh saja sudah dihapus,
     * dan lognya tetap bermakna berkat `subject_label` yang dibekukan.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /* ------------------------------------------------------------- label */

    public function getActionLabelAttribute(): string
    {
        return ActivityAction::label($this->action);
    }

    public function getModuleLabelAttribute(): string
    {
        return ActivityModule::label($this->module);
    }

    public function getStatusLabelAttribute(): string
    {
        return ActivityStatus::label($this->status);
    }

    /** Singkatan tipe akun untuk kolom tabel, mis. "B2B". */
    public function getUserTypeLabelAttribute(): string
    {
        return ActorType::label($this->user_type);
    }

    public function getUserTypeLongLabelAttribute(): string
    {
        return ActorType::longLabel($this->user_type);
    }

    /** Nama pelaku; memakai nama yang dibekukan agar akun terhapus tetap terbaca. */
    public function getActorNameAttribute(): string
    {
        return $this->user_name ?: ($this->user?->name ?? 'Sistem');
    }

    public function isSuccess(): bool
    {
        return $this->status === ActivityStatus::SUCCESS;
    }

    /**
     * Perangkat pelaku dalam bentuk singkat, mis. "Chrome / Windows".
     *
     * User agent lengkap tetap tersimpan; yang ini hanya ringkasan agar
     * halaman detail terbaca manusia.
     */
    public function getDeviceAttribute(): string
    {
        $agent = (string) $this->user_agent;

        if ($agent === '') {
            return '-';
        }

        $browser = match (true) {
            Str::contains($agent, 'Edg/') => 'Edge',
            Str::contains($agent, 'OPR/') || Str::contains($agent, 'Opera') => 'Opera',
            Str::contains($agent, 'Chrome') => 'Chrome',
            Str::contains($agent, 'Firefox') => 'Firefox',
            Str::contains($agent, 'Safari') => 'Safari',
            default => 'Browser lain',
        };

        $platform = match (true) {
            Str::contains($agent, 'Windows') => 'Windows',
            Str::contains($agent, 'Android') => 'Android',
            Str::contains($agent, ['iPhone', 'iPad']) => 'iOS',
            Str::contains($agent, 'Mac OS X') => 'macOS',
            Str::contains($agent, 'Linux') => 'Linux',
            default => 'Perangkat lain',
        };

        return $browser.' / '.$platform;
    }

    /**
     * Perbandingan nilai sebelum dan sesudah, siap ditampilkan berdampingan.
     *
     * Kunci dari kedua sisi digabung supaya kolom yang hanya muncul di salah
     * satunya — misalnya nilai baru yang sebelumnya kosong — tetap terlihat.
     *
     * @return Collection<int, array{key: string, label: string, old: ?string, new: ?string, changed: bool}>
     */
    public function recordedChanges(): Collection
    {
        $old = is_array($this->old_values) ? $this->old_values : [];
        $new = is_array($this->new_values) ? $this->new_values : [];

        $keys = array_values(array_unique([...array_keys($old), ...array_keys($new)]));

        return collect($keys)->map(function (string $key) use ($old, $new) {
            $before = $this->readable($old[$key] ?? null);
            $after = $this->readable($new[$key] ?? null);

            return [
                'key' => $key,
                'label' => Str::title(str_replace('_', ' ', $key)),
                'old' => $before,
                'new' => $after,
                // Hanya ditandai berubah bila keduanya memang ada dan berbeda,
                // sehingga log yang cuma memuat satu sisi tidak ikut disorot.
                'changed' => $old !== [] && $new !== [] && $before !== $after,
            ];
        });
    }

    /**
     * Apakah aktivitas ini menyimpan perbandingan data.
     *
     * Namanya sengaja tidak `hasChanges()` — Eloquent sudah memakai nama itu
     * untuk membandingkan atribut model yang baru saja disimpan, dan yang
     * dimaksud di sini adalah isi kolom `old_values` / `new_values`.
     */
    public function hasRecordedChanges(): bool
    {
        return filled($this->old_values) || filled($this->new_values);
    }

    /** Ubah nilai apa pun menjadi satu baris teks yang enak dibaca. */
    private function readable(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            is_bool($value) => $value ? 'Ya' : 'Tidak',
            is_array($value) => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            default => (string) $value,
        };
    }

    /* ------------------------------------------------------------ scopes */

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    /** Cari berdasarkan pelakunya: nama akun maupun emailnya. */
    public function scopeSearchUser(Builder $query, ?string $term): Builder
    {
        return $query->when(filled($term), fn (Builder $q) => $q->where(function (Builder $inner) use ($term) {
            $inner->where('user_name', 'like', "%{$term}%")
                ->orWhereHas('user', fn (Builder $user) => $user
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%"));
        }));
    }

    /**
     * Cari berdasarkan aktivitasnya.
     *
     * Yang tersimpan adalah kunci aktivitas (`spec_update`), sedangkan admin
     * mengetik labelnya ("Edit Specification"). Karena itu kunci yang labelnya
     * cocok ikut disertakan, bukan hanya pencocokan langsung ke kolomnya.
     */
    public function scopeSearchActivity(Builder $query, ?string $term): Builder
    {
        return $query->when(filled($term), function (Builder $q) use ($term) {
            $matching = array_keys(array_filter(
                ActivityAction::options(),
                fn (string $label) => Str::contains($label, $term, ignoreCase: true),
            ));

            $q->where(function (Builder $inner) use ($term, $matching) {
                $inner->where('action', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('subject_label', 'like', "%{$term}%");

                if ($matching !== []) {
                    $inner->orWhereIn('action', $matching);
                }
            });
        });
    }

    public function scopeOfUserType(Builder $query, ?string $type): Builder
    {
        return $query->when(ActorType::exists($type), fn (Builder $q) => $q->where('user_type', $type));
    }

    public function scopeOfModule(Builder $query, ?string $module): Builder
    {
        return $query->when(ActivityModule::exists($module), fn (Builder $q) => $q->where('module', $module));
    }

    public function scopeOfStatus(Builder $query, ?string $status): Builder
    {
        return $query->when(ActivityStatus::exists($status), fn (Builder $q) => $q->where('status', $status));
    }

    /** Batas tanggal, keduanya inklusif dan boleh diisi salah satu saja. */
    public function scopeBetweenDates(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($this->parseDate($from), fn (Builder $q, Carbon $date) => $q->where('created_at', '>=', $date->startOfDay()))
            ->when($this->parseDate($to), fn (Builder $q, Carbon $date) => $q->where('created_at', '<=', $date->endOfDay()));
    }

    /** Tanggal yang tidak terbaca diabaikan, bukan menggagalkan penyaringan. */
    private function parseDate(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
