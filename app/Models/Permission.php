<?php

namespace App\Models;

use App\Support\AdminPermission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * Satu hak akses Admin, mis. `quotation.view` (modul `quotation`, aksi `view`).
 *
 * Daftarnya bersumber dari App\Support\AdminPermission; tabel ini menyimpan
 * barisnya supaya pemberian hak akses per akun (`admin_permissions`) punya
 * relasi yang nyata di basis data.
 */
class Permission extends Model
{
    protected $fillable = ['key', 'module', 'action', 'label', 'description', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'admin_permissions')->withTimestamps();
    }

    /**
     * Samakan isi tabel dengan daftar di App\Support\AdminPermission.
     *
     * Hak akses baru langsung tersedia begitu ditambahkan ke daftar; label dan
     * urutannya ikut diperbarui. Baris yang tidak lagi ada di daftar dibiarkan
     * (tidak dihapus) supaya pemberian lama tidak hilang diam-diam.
     *
     * @return Collection<string, self> baris yang terdaftar, dikunci `key`
     */
    public static function syncDefinitions(): Collection
    {
        $order = 0;

        foreach (AdminPermission::definitions() as $key => $definition) {
            static::updateOrCreate(['key' => $key], [
                'module' => $definition['module'],
                'action' => $definition['action'],
                'label' => AdminPermission::label($key),
                'description' => $definition['description'],
                'sort_order' => $order += 10,
            ]);
        }

        return static::whereIn('key', AdminPermission::keys())->orderBy('sort_order')->get()->keyBy('key');
    }
}
