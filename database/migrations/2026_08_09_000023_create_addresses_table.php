<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Buku alamat pengiriman milik pelanggan.
 *
 * Satu akun boleh menyimpan beberapa alamat — misalnya kantor dan rumah — dan
 * menandai salah satunya sebagai alamat utama. Alamat utama itulah yang
 * terpilih lebih dulu ketika pelanggan meminta penawaran, dan yang dicerminkan
 * kembali ke kolom `city`, `postal_code`, serta `address` pada tabel `users`
 * supaya halaman admin dan pencarian yang sudah ada tetap berjalan apa adanya.
 *
 * `province_id` dan `city_id` sengaja boleh kosong walaupun formulir
 * mewajibkannya: alamat milik akun lama dipindahkan ke sini dengan wilayah yang
 * belum tentu cocok dengan daftar resmi, dan pemiliknya melengkapi sendiri saat
 * membuka menu Alamat. Menghapus sebuah wilayah tidak ikut menghapus alamat
 * pelanggan — kolomnya hanya dikosongkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('label', 60)->comment('Penanda alamat, mis. Rumah atau Kantor');
            $table->string('recipient_name', 120);
            $table->string('recipient_phone', 32);

            $table->foreignId('province_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->string('district', 120)->nullable()->comment('Kecamatan');
            $table->string('village', 120)->nullable()->comment('Kelurahan / Desa');
            $table->string('postal_code', 12)->nullable();

            $table->text('detail')->comment('Nama jalan, nomor, RT/RW');
            $table->string('note', 255)->nullable()->comment('Patokan atau catatan untuk kurir');

            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
