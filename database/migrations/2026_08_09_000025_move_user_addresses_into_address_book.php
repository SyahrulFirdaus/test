<?php

use App\Services\AddressBook;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Memindahkan alamat akun lama ke buku alamat.
 *
 * Sebelum ini setiap akun hanya menyimpan satu alamat berupa teks bebas pada
 * kolom `users.address`. Isinya dipindahkan apa adanya menjadi baris pertama di
 * buku alamat dan ditandai sebagai alamat utama, sehingga tidak ada pelanggan
 * yang kehilangan data pengirimannya.
 *
 * Wilayahnya dulu ditulis bebas, jadi tidak selalu cocok dengan daftar resmi:
 * nama kota dicocokkan sebisanya, dan yang tidak ketemu dibiarkan kosong untuk
 * dilengkapi sendiri pemiliknya lewat menu Alamat. Kecamatan dan kelurahan
 * memang belum pernah ditanyakan, jadi keduanya selalu kosong.
 *
 * Kolom `users.city`, `users.postal_code`, dan `users.address` sengaja tidak
 * dihapus: keduanya kini menjadi cerminan alamat utama yang tetap dipakai
 * pencarian pengguna, halaman admin, dan pengisian otomatis formulir penawaran.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Tabel `regions` sudah digantikan wilayah empat tingkat pada migrasi
        // berikutnya, dan pada pemasangan baru isinya memang kosong. Pencocokan
        // kota di bawah karena itu hanya berjalan bila daftarnya benar-benar
        // ada — pada pemasangan baru tidak ada pula alamat lama yang perlu
        // dipindahkan.
        //
        // Dicocokkan di PHP, bukan lewat join, supaya perilakunya sama pada
        // MySQL maupun SQLite yang dipakai pengujian.
        $cities = DB::table('regions')
            ->where('type', 'city')
            ->get(['id', 'parent_id', 'name'])
            ->keyBy(fn (object $city) => mb_strtolower($city->name));

        DB::table('users')
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($users) use ($cities, $now) {
                $rows = [];

                foreach ($users as $user) {
                    // Akun yang entah bagaimana sudah punya alamat dilewati agar
                    // migrasi ini aman diulang.
                    if (DB::table('addresses')->where('user_id', $user->id)->exists()) {
                        continue;
                    }

                    $city = $this->matchCity($cities, $user->city);

                    $rows[] = [
                        'user_id' => $user->id,
                        'label' => 'Alamat Utama',
                        'recipient_name' => $user->name,
                        'recipient_phone' => (string) ($user->phone ?? ''),
                        'province_id' => $city?->parent_id,
                        'city_id' => $city?->id,
                        'district' => null,
                        'village' => null,
                        'postal_code' => $user->postal_code,
                        'detail' => $user->address,
                        'note' => null,
                        'is_default' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows !== []) {
                    DB::table('addresses')->insert($rows);
                }
            });
    }

    /**
     * "Bandung" pada data lama dapat berarti "Kota Bandung"; kota didahulukan.
     *
     * Normalisasinya memakai App\Services\AddressBook agar aturan yang sama
     * berlaku di sini maupun saat pelanggan menyimpan alamat baru.
     */
    private function matchCity($cities, ?string $name): ?object
    {
        $name = mb_strtolower(AddressBook::normalizeCityName($name));

        if ($name === '') {
            return null;
        }

        return $cities->get($name)
            ?? $cities->get('kota '.$name)
            ?? $cities->get('kabupaten '.$name);
    }

    public function down(): void
    {
        // Isi buku alamat dibuang; kolom alamat pada tabel `users` tidak pernah
        // dihapus, jadi data aslinya tetap utuh.
        DB::table('addresses')->truncate();
    }
};
