<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master data biaya mesin, dikelola admin lewat halaman Price List.
 *
 * Listrik/Hour, Machine Cost, dan Pembulatan tidak disimpan — dihitung
 * sebagai accessor di model `MachineCost` dari `watt_kwh`, `harga_listrik`,
 * dan `depresiasi` supaya formulanya tetap satu sumber kebenaran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_costs', function (Blueprint $table) {
            $table->id();
            $table->string('mesin');
            $table->decimal('watt_kwh', 6, 3);
            $table->decimal('harga_listrik', 12, 2);
            $table->decimal('depresiasi', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_costs');
    }
};
