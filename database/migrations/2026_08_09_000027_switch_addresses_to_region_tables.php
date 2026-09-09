<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Memindahkan alamat ke wilayah berjenjang empat tingkat.
 *
 * Sebelum ini alamat menunjuk tabel `regions` untuk provinsi dan kota, sedangkan
 * kecamatan dan kelurahan hanya berupa tulisan bebas. Sekarang keempatnya
 * menjadi relasi ke tabel wilayah resmi, sehingga tidak ada lagi kombinasi
 * wilayah yang tidak mungkin.
 *
 * Data yang sudah ada dipindahkan sebisanya dengan mencocokkan nama: provinsi
 * dan kota hampir selalu ketemu karena keduanya memang dipilih dari daftar,
 * sedangkan kecamatan dan kelurahan dicocokkan di dalam lingkup induknya. Yang
 * tidak ketemu dibiarkan kosong — alamat tetap tersimpan dan ditandai belum
 * lengkap agar pemiliknya melengkapinya lewat menu Alamat.
 *
 * Tabel `regions` beserta isinya ikut dibuang di akhir karena seluruh
 * pemakainya sudah berpindah.
 */
return new class extends Migration
{
    public function up(): void
    {
        $legacy = $this->readLegacyAddresses();

        // Pemetaan wilayah di bawah membutuhkan daftar wilayah, sementara
        // seeder baru berjalan setelah seluruh migrasi selesai. Daftarnya
        // karena itu dimuat lebih dulu di sini — tetapi hanya bila memang ada
        // alamat lama yang perlu dipetakan. Pada pemasangan baru tabel alamat
        // masih kosong, jadi impor 91 ribu baris tidak perlu dijalankan dan
        // pemasangan tetap ringan.
        if ($legacy !== [] && ! DB::table('provinces')->exists()) {
            Artisan::call('wilayah:import');
        }

        Schema::table('addresses', function (Blueprint $table) {
            // Foreign key lama menunjuk `regions` yang sebentar lagi dibuang.
            $table->dropConstrainedForeignId('province_id');
            $table->dropConstrainedForeignId('city_id');
            $table->dropColumn(['district', 'village']);
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->foreignId('province_id')->nullable()->after('recipient_phone')->constrained('provinces')->nullOnDelete();
            $table->foreignId('regency_id')->nullable()->after('province_id')->constrained('regencies')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->after('regency_id')->constrained('districts')->nullOnDelete();
            $table->foreignId('village_id')->nullable()->after('district_id')->constrained('villages')->nullOnDelete();
        });

        $this->remap($legacy);

        Schema::dropIfExists('regions');
    }

    /**
     * Baca nama wilayah alamat lama sebelum kolomnya dibuang.
     *
     * @return array<int, array<string, string|null>>
     */
    private function readLegacyAddresses(): array
    {
        if (! Schema::hasTable('regions')) {
            return [];
        }

        $regions = DB::table('regions')->get(['id', 'name'])->keyBy('id');

        return DB::table('addresses')
            ->get(['id', 'province_id', 'city_id', 'district', 'village'])
            ->mapWithKeys(fn (object $address) => [$address->id => [
                'province' => $regions[$address->province_id]->name ?? null,
                'regency' => $regions[$address->city_id]->name ?? null,
                'district' => $address->district,
                'village' => $address->village,
            ]])
            ->all();
    }

    /**
     * Cocokkan nama wilayah lama ke id wilayah resmi.
     *
     * Pencocokan dikerjakan bertingkat: kota hanya dicari di dalam provinsi yang
     * ketemu, kecamatan hanya di dalam kotanya, dan seterusnya. Dengan begitu
     * nama yang sama di daerah berbeda — "Cibeureum" ada di banyak kota —
     * tidak pernah tertukar.
     *
     * @param  array<int, array<string, string|null>>  $legacy
     */
    private function remap(array $legacy): void
    {
        foreach ($legacy as $addressId => $names) {
            $province = $this->find('provinces', null, null, $names['province']);
            $regency = $this->find('regencies', 'province_id', $province, $names['regency']);
            $district = $this->find('districts', 'regency_id', $regency, $names['district']);
            $village = $this->find('villages', 'district_id', $district, $names['village']);

            DB::table('addresses')->where('id', $addressId)->update([
                'province_id' => $province,
                'regency_id' => $regency,
                'district_id' => $district,
                'village_id' => $village,
            ]);
        }
    }

    /** Cari satu wilayah menurut namanya di dalam induk tertentu. */
    private function find(string $table, ?string $parentColumn, ?int $parentId, ?string $name): ?int
    {
        $name = trim((string) $name);

        if ($name === '' || ($parentColumn !== null && $parentId === null)) {
            return null;
        }

        return DB::table($table)
            ->when($parentColumn, fn ($query) => $query->where($parentColumn, $parentId))
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->value('id');
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('province_id');
            $table->dropConstrainedForeignId('regency_id');
            $table->dropConstrainedForeignId('district_id');
            $table->dropConstrainedForeignId('village_id');
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->unsignedBigInteger('province_id')->nullable()->after('recipient_phone');
            $table->unsignedBigInteger('city_id')->nullable()->after('province_id');
            $table->string('district', 120)->nullable()->after('city_id');
            $table->string('village', 120)->nullable()->after('district');
        });
    }
};
