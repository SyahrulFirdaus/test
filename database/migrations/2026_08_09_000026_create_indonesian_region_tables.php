<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * Wilayah administratif Indonesia dalam empat tingkat.
 *
 *   provinces  provinsi
 *   regencies  kabupaten/kota   -> province_id
 *   districts  kecamatan        -> regency_id
 *   villages   kelurahan/desa   -> district_id
 *
 * Kunci utamanya bukan angka berurut, melainkan kode wilayah resmi tanpa titik:
 * "32" (Jawa Barat), "32.77" -> 3277 (Kota Cimahi), "32.77.01" -> 327701
 * (Cimahi Selatan), "32.77.01.1001" -> 3277011001 (Melong).
 *
 * Bentuk itu dipilih karena kodenya sudah unik, stabil antar-pembaruan daftar
 * wilayah, dan langsung menunjukkan induknya — sehingga memperbarui data ke
 * keputusan menteri berikutnya tidak mengacak id yang sudah tersimpan pada
 * alamat pelanggan.
 *
 * Isinya langsung diimpor dari database/data/wilayah.csv begitu tabelnya
 * terbentuk. Sengaja dilakukan di dalam migrasi, bukan diserahkan ke seeder:
 * tanpa data wilayah, dropdown alamat dan pendaftaran Business tampil kosong
 * tanpa pesan apa pun — kegagalan yang membingungkan dan mudah terlewat.
 * `php artisan migrate` karenanya harus cukup untuk membuat aplikasi berjalan.
 *
 * Pengujian dikecualikan: di sana dipakai potongan kecil daftar wilayah
 * (tests/fixtures/wilayah-test.csv) supaya setiap pengujian tidak menunggu
 * 91.600 baris diimpor. Lihat tests/Feature/AddressBookTest.php.
 *
 * Memperbarui daftar wilayah kelak cukup mengganti berkas CSV-nya lalu
 * menjalankan `php artisan wilayah:import` — impornya memperbarui baris yang
 * sudah ada, jadi id yang tersimpan pada alamat pelanggan tidak terputus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary()->comment('Kode wilayah tanpa titik, mis. 32');
            $table->string('code', 20)->unique()->comment('Kode resmi berformat titik, mis. 32');
            $table->string('name', 120)->index();
            $table->timestamps();
        });

        Schema::create('regencies', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary()->comment('mis. 3277');
            $table->foreignId('province_id')->constrained('provinces')->cascadeOnDelete();
            $table->string('code', 20)->unique();
            $table->string('name', 120);
            $table->timestamps();

            $table->index(['province_id', 'name']);
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary()->comment('mis. 327701');
            $table->foreignId('regency_id')->constrained('regencies')->cascadeOnDelete();
            $table->string('code', 20)->unique();
            $table->string('name', 120);
            $table->timestamps();

            $table->index(['regency_id', 'name']);
        });

        Schema::create('villages', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary()->comment('mis. 3277011001');
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->string('code', 20)->unique();
            $table->string('name', 120);
            $table->timestamps();

            $table->index(['district_id', 'name']);
        });

        // Tabel kosong sama saja dengan fitur yang rusak diam-diam, jadi
        // datanya diisi sekarang juga. Pengujian memakai potongan kecilnya
        // sendiri, jadi dilewati di sana agar tetap cepat.
        if (! app()->runningUnitTests()) {
            Artisan::call('wilayah:import');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('villages');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('regencies');
        Schema::dropIfExists('provinces');
    }
};
