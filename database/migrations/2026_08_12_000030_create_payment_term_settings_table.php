<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aturan pilihan payment term yang dapat diubah admin.
 *
 * Satu baris mewakili satu pilihan cicilan (1x, 3x, 4x, 5x) beserta batas
 * nominal penawaran terkecil yang boleh memakainya. Angkanya sengaja disimpan
 * di basis data, bukan di config atau frontend, supaya admin dapat menggeser
 * batasnya sendiri lewat menu "Pengaturan Payment Term".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_term_settings', function (Blueprint $table) {
            $table->id();

            // Jumlah termin, sekaligus penanda unik barisnya.
            $table->unsignedTinyInteger('installment_count')->unique();

            // Pilihan yang dinonaktifkan tidak pernah ditawarkan ke pelanggan,
            // meski nominal penawarannya memenuhi batas.
            $table->boolean('enabled')->default(true);

            // Nominal penawaran terkecil yang boleh memakai pilihan ini.
            $table->decimal('minimum_amount', 15, 2)->default(0);

            $table->timestamps();
        });

        // Nilai awal mengikuti aturan yang berlaku sekarang: pelunasan sekali
        // bayar selalu tersedia, cicilan terbuka bertahap mengikuti nominal.
        DB::table('payment_term_settings')->insert([
            ['installment_count' => 1, 'enabled' => true, 'minimum_amount' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['installment_count' => 3, 'enabled' => true, 'minimum_amount' => 5000000, 'created_at' => now(), 'updated_at' => now()],
            ['installment_count' => 4, 'enabled' => true, 'minimum_amount' => 20000000, 'created_at' => now(), 'updated_at' => now()],
            ['installment_count' => 5, 'enabled' => true, 'minimum_amount' => 50000000, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_term_settings');
    }
};
