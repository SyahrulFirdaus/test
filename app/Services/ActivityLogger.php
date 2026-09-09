<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\ActivityAction;
use App\Support\ActivityStatus;
use App\Support\ActorType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Pencatat jejak audit.
 *
 * Seluruh modul memanggil kelas ini alih-alih menulis ke tabel `activity_logs`
 * sendiri, sehingga tiga hal berlaku seragam: pelaku beserta IP dan
 * perangkatnya terisi otomatis, kolom rahasia tidak pernah ikut tersimpan, dan
 * kegagalan pencatatan tidak pernah menggagalkan aktivitas yang sedang
 * dikerjakan pengguna.
 *
 * Yang terakhir disengaja. Activity Log adalah pengamat, bukan bagian dari
 * alur kerja — bila tabelnya bermasalah, penawaran dan pembayaran harus tetap
 * berjalan, dan galatnya cukup masuk log aplikasi.
 */
class ActivityLogger
{
    /**
     * Kolom yang tidak boleh masuk jejak audit dalam keadaan apa pun.
     *
     * Pencocokannya memakai potongan kata, jadi `password_confirmation`,
     * `current_password`, dan `api_token` ikut tersaring tanpa perlu didaftar
     * satu per satu.
     *
     * @var array<int, string>
     */
    private const SENSITIVE = [
        'password',
        'token',
        'secret',
        'api_key',
        'apikey',
        'authorization',
        'auth',
        'credential',
        'remember_token',
        'csrf',
        'signature',
        'private_key',
        'otp',
        'pin',
    ];

    /**
     * Catat satu aktivitas.
     *
     * Modul boleh dikosongkan — bila tidak disebut, dipakai modul bawaan
     * aktivitasnya dari App\Support\ActivityAction.
     *
     * @param  array<string, mixed>|null  $old  keadaan data sebelum perubahan
     * @param  array<string, mixed>|null  $new  keadaan data setelah perubahan
     */
    public function log(
        string $action,
        ?string $description = null,
        ?Model $subject = null,
        ?array $old = null,
        ?array $new = null,
        ?User $actor = null,
        ?string $module = null,
        string $status = ActivityStatus::SUCCESS,
        ?string $subjectLabel = null,
        ?string $userName = null,
        ?string $userType = null,
    ): ?ActivityLog {
        try {
            $actor ??= Auth::user();

            return ActivityLog::create([
                'user_id' => $actor?->getKey(),
                // Nama pelaku dibekukan supaya log tetap terbaca meski akunnya
                // kemudian dihapus. Percobaan masuk yang gagal belum punya
                // akun, jadi pemanggil boleh menitipkan namanya sendiri.
                'user_name' => $userName ?? $actor?->name,
                'user_type' => $userType ?? ActorType::forUser($actor),

                'action' => $action,
                'module' => $module ?? ActivityAction::module($action),
                'description' => $description === null ? null : Str::limit($description, 490),

                'subject_type' => $subject === null ? null : $subject->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'subject_label' => $subjectLabel ?? $this->labelFor($subject),

                'old_values' => $this->clean($old),
                'new_values' => $this->clean($new),

                'ip_address' => $this->ip(),
                'user_agent' => Str::limit((string) request()->userAgent(), 490, ''),

                'status' => $status,
            ]);
        } catch (Throwable $exception) {
            // Aktivitas pengguna tetap dianggap berhasil; hanya pencatatannya
            // yang gagal, dan itu urusan operasional.
            Log::error('Gagal mencatat activity log: '.$exception->getMessage(), [
                'action' => $action,
                'exception' => $exception,
            ]);

            return null;
        }
    }

    /**
     * Catat aktivitas hanya bila belum tercatat pada rentang waktu terakhir.
     *
     * Dipakai aktivitas membaca — membuka detail penawaran, misalnya — yang
     * memang perlu terekam siapa pengaksesnya, tetapi tidak perlu terulang
     * setiap halamannya disegarkan. Tanpa jeda ini tabel log akan penuh oleh
     * baris yang menceritakan hal yang sama.
     */
    public function logOnce(
        string $action,
        ?string $description = null,
        ?Model $subject = null,
        ?User $actor = null,
        ?string $module = null,
        int $withinMinutes = 60,
    ): ?ActivityLog {
        try {
            $actor ??= Auth::user();

            $recorded = ActivityLog::query()
                ->where('action', $action)
                ->where('user_id', $actor?->getKey())
                ->when($subject !== null, fn ($query) => $query
                    ->where('subject_type', $subject->getMorphClass())
                    ->where('subject_id', $subject->getKey()))
                ->where('created_at', '>=', now()->subMinutes($withinMinutes))
                ->exists();

            if ($recorded) {
                return null;
            }
        } catch (Throwable $exception) {
            Log::error('Gagal memeriksa activity log: '.$exception->getMessage(), [
                'action' => $action,
                'exception' => $exception,
            ]);

            return null;
        }

        return $this->log(
            action: $action,
            description: $description,
            subject: $subject,
            actor: $actor,
            module: $module,
        );
    }

    /** Catat aktivitas yang gagal, mis. percobaan masuk yang ditolak. */
    public function logFailure(
        string $action,
        ?string $description = null,
        ?Model $subject = null,
        ?User $actor = null,
        ?string $module = null,
        ?array $new = null,
        ?string $userName = null,
        ?string $userType = null,
    ): ?ActivityLog {
        return $this->log(
            action: $action,
            description: $description,
            subject: $subject,
            new: $new,
            actor: $actor,
            module: $module,
            status: ActivityStatus::FAILED,
            userName: $userName,
            userType: $userType,
        );
    }

    /**
     * Catat perubahan data dengan hanya menyimpan kolom yang benar-benar
     * berbeda.
     *
     * Tanpa penyaringan ini satu penyuntingan kecil akan menyimpan seluruh
     * kolom formulir, sehingga halaman detail sulit dibaca — padahal yang
     * dicari admin justru apa yang berubah.
     *
     * Bila tidak ada satu pun perbedaan, tidak ada yang dicatat: penyuntingan
     * yang tidak mengubah apa pun bukan peristiwa yang perlu diaudit.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function logChanges(
        string $action,
        array $before,
        array $after,
        ?string $description = null,
        ?Model $subject = null,
        ?User $actor = null,
        ?string $module = null,
        ?string $subjectLabel = null,
    ): ?ActivityLog {
        [$old, $new] = $this->diff($before, $after);

        if ($old === [] && $new === []) {
            return null;
        }

        return $this->log(
            action: $action,
            description: $description,
            subject: $subject,
            old: $old,
            new: $new,
            actor: $actor,
            module: $module,
            subjectLabel: $subjectLabel,
        );
    }

    /**
     * Kolom yang berbeda antara dua keadaan data.
     *
     * Perbandingannya longgar terhadap tipe — `"5"` dan `5` datang dari
     * formulir dan basis data untuk nilai yang sama — namun tetap membedakan
     * nilai kosong dari nilai yang benar-benar berubah.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    public function diff(array $before, array $after): array
    {
        $old = [];
        $new = [];

        foreach ($after as $key => $value) {
            $previous = $before[$key] ?? null;

            if ($this->same($previous, $value)) {
                continue;
            }

            $old[$key] = $previous;
            $new[$key] = $value;
        }

        return [$this->clean($old) ?? [], $this->clean($new) ?? []];
    }

    /** Dua nilai dianggap sama bila tulisannya sama setelah dinormalkan. */
    private function same(mixed $a, mixed $b): bool
    {
        if (is_bool($a) || is_bool($b)) {
            return (bool) $a === (bool) $b;
        }

        if (is_numeric($a) && is_numeric($b)) {
            return abs((float) $a - (float) $b) < 0.000001;
        }

        if (is_array($a) || is_array($b)) {
            return $a === $b;
        }

        return (string) ($a ?? '') === (string) ($b ?? '');
    }

    /**
     * Buang kolom rahasia sebelum apa pun tersimpan.
     *
     * Berlaku sampai ke dalam array bersarang, karena data yang dicatat modul
     * pembayaran dan pendaftaran kerap berbentuk bertingkat.
     *
     * @param  array<string, mixed>|null  $values
     * @return array<string, mixed>|null
     */
    private function clean(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $clean = [];

        foreach ($values as $key => $value) {
            if ($this->isSensitive((string) $key)) {
                continue;
            }

            $clean[$key] = is_array($value) ? $this->clean($value) : $value;
        }

        return $clean;
    }

    private function isSensitive(string $key): bool
    {
        $key = Str::lower($key);

        foreach (self::SENSITIVE as $needle) {
            if (Str::contains($key, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Penanda data siap tampil.
     *
     * Model di sistem ini mengenal beberapa penamaan berbeda — penawaran
     * memakai nomor tracking, berkas 3D memakai nama berkas — jadi yang
     * pertama tersedia yang dipakai.
     */
    private function labelFor(?Model $subject): ?string
    {
        if ($subject === null) {
            return null;
        }

        foreach (['tracking_number', 'file_name', 'name', 'title', 'email'] as $attribute) {
            $value = $subject->getAttribute($attribute);

            if (filled($value)) {
                return Str::limit((string) $value, 180, '');
            }
        }

        return class_basename($subject).' #'.$subject->getKey();
    }

    /**
     * Alamat IP pelaku.
     *
     * Perintah terjadwal — pembayaran kedaluwarsa, misalnya — berjalan tanpa
     * request, jadi kolomnya boleh kosong.
     */
    private function ip(): ?string
    {
        return app()->runningInConsole() ? null : request()->ip();
    }
}
