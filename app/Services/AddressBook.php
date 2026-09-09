<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Regency;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Pengelolaan buku alamat pelanggan.
 *
 * Dua aturan yang dijaga di sini:
 *
 *   1. Tepat satu alamat utama. Menandai alamat lain sebagai utama akan
 *      melepas penanda dari alamat sebelumnya, dan alamat pertama yang
 *      disimpan otomatis menjadi utama. Bila alamat utama dihapus, alamat
 *      tersisa yang paling awal menggantikannya — akun yang masih punya alamat
 *      tidak boleh berakhir tanpa alamat utama.
 *
 *   2. Kolom `city`, `postal_code`, dan `address` pada tabel `users` selalu
 *      mencerminkan alamat utama. Kolom-kolom itu sudah dipakai lebih dulu oleh
 *      pencarian pengguna, halaman admin, dan pengisian otomatis formulir
 *      penawaran; mencerminkannya di sini membuat seluruh bagian itu tetap
 *      berjalan tanpa perubahan sementara pelanggan cukup mengurus alamatnya
 *      di satu tempat.
 */
class AddressBook
{
    /** Simpan alamat baru; yang pertama otomatis menjadi alamat utama. */
    public function create(User $user, array $data): Address
    {
        return DB::transaction(function () use ($user, $data) {
            $isFirst = ! $user->addresses()->exists();

            $address = $user->addresses()->create([
                ...$data,
                'is_default' => $isFirst || (bool) ($data['is_default'] ?? false),
            ]);

            if ($address->is_default) {
                $this->makeDefault($address);
            }

            return $address;
        });
    }

    public function update(Address $address, array $data): Address
    {
        return DB::transaction(function () use ($address, $data) {
            $address->update([
                ...$data,
                // Alamat utama tidak dapat melepas statusnya sendiri lewat
                // formulir: melepasnya akan menyisakan akun tanpa alamat utama.
                // Statusnya berpindah dengan menandai alamat lain sebagai utama.
                'is_default' => $address->is_default || (bool) ($data['is_default'] ?? false),
            ]);

            if ($address->is_default) {
                $this->makeDefault($address);
            } else {
                $this->syncUser($address->user);
            }

            return $address;
        });
    }

    /** Jadikan alamat ini satu-satunya alamat utama milik pemiliknya. */
    public function makeDefault(Address $address): void
    {
        DB::transaction(function () use ($address) {
            Address::query()
                ->where('user_id', $address->user_id)
                ->whereKeyNot($address->getKey())
                ->update(['is_default' => false]);

            $address->forceFill(['is_default' => true])->save();

            $this->syncUser($address->user);
        });
    }

    /** Hapus alamat; bila yang dihapus alamat utama, penggantinya ditunjuk. */
    public function delete(Address $address): void
    {
        DB::transaction(function () use ($address) {
            $user = $address->user;
            $wasDefault = $address->is_default;

            $address->delete();

            if ($wasDefault) {
                $replacement = $user->addresses()->orderBy('id')->first();

                if ($replacement !== null) {
                    $this->makeDefault($replacement);

                    return;
                }
            }

            $this->syncUser($user);
        });
    }

    /**
     * Cerminkan alamat utama ke kolom ringkas pada tabel `users`.
     *
     * Akun yang tidak lagi punya alamat dibiarkan memakai nilai terakhirnya:
     * mengosongkannya akan menghapus data pengiriman yang mungkin masih
     * dibutuhkan penawaran yang sedang berjalan.
     */
    public function syncUser(User $user): void
    {
        $default = $user->addresses()->where('is_default', true)->with(Address::REGION_RELATIONS)->first();

        if ($default === null) {
            return;
        }

        $user->forceFill([
            'city' => $default->regency?->name ?: $user->city,
            'postal_code' => $default->postal_code ?: $user->postal_code,
            'address' => $default->full_address ?: $user->address,
        ])->save();
    }

    /**
     * Alamat pertama untuk akun yang baru mendaftar.
     *
     * Data pengiriman sudah diminta pada langkah Data Akun, jadi pelanggan
     * tidak perlu mengetikkannya lagi di menu Alamat. Wilayahnya dicocokkan
     * sebisanya dengan daftar resmi; yang tidak cocok dibiarkan kosong dan
     * dilengkapi sendiri oleh pemiliknya.
     */
    public function createFromProfile(User $user): ?Address
    {
        if (blank($user->address) || $user->addresses()->exists()) {
            return null;
        }

        $regency = $this->matchCity($user->city);

        return $this->create($user, [
            'label' => 'Alamat Utama',
            'recipient_name' => $user->name,
            'recipient_phone' => (string) $user->phone,
            'province_id' => $regency?->province_id,
            'regency_id' => $regency?->id,
            // Kecamatan dan kelurahan belum pernah ditanyakan pada pendaftaran,
            // jadi keduanya dilengkapi sendiri lewat menu Alamat.
            'district_id' => null,
            'village_id' => null,
            'postal_code' => $user->postal_code,
            'detail' => $user->address,
            'note' => null,
        ]);
    }

    /**
     * Cari kabupaten/kota yang namanya cocok dengan tulisan bebas pelanggan.
     *
     * Kolom kota pada akun lama diisi bermacam-macam bentuk: "Bandung",
     * "Kota Bandung", sampai "77 - Kota Cimahi" yang masih membawa kode
     * wilayah dari formulir lama. Ketiganya harus mengarah ke baris yang sama,
     * jadi tulisannya dinormalkan dulu sebelum dicocokkan.
     *
     * Bila yang tersisa hanya nama kotanya saja, "Kota" didahulukan atas
     * "Kabupaten" — itulah yang umumnya dimaksud orang ketika menyebut nama
     * kotanya saja.
     */
    public function matchCity(?string $name): ?Regency
    {
        $name = self::normalizeCityName($name);

        if ($name === '') {
            return null;
        }

        $candidates = array_map('mb_strtolower', [$name, 'Kota '.$name, 'Kabupaten '.$name]);

        return Regency::query()
            // Dibandingkan dalam huruf kecil agar hasilnya sama pada MySQL
            // maupun SQLite yang dipakai pengujian.
            ->whereRaw('LOWER(name) IN (?, ?, ?)', $candidates)
            ->orderByRaw("CASE WHEN LOWER(name) LIKE 'kota %' THEN 0 ELSE 1 END")
            ->first();
    }

    /**
     * Bersihkan tulisan kota dari kode wilayah dan spasi berlebih.
     *
     * "77 - Kota Cimahi" menjadi "Kota Cimahi", "  bandung " menjadi "bandung".
     */
    public static function normalizeCityName(?string $name): string
    {
        $name = trim((string) $name);

        // Awalan berupa kode wilayah, mis. "77 - " atau "3277.".
        $name = (string) preg_replace('/^\s*\d+\s*[-–—.]\s*/u', '', $name);

        return trim((string) preg_replace('/\s+/u', ' ', $name));
    }
}
