<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master data harga packaging (kardus, foam, bubble wrap), dikelola admin
 * lewat halaman Price List. `price_unit` membedakan harga flat per item
 * (kardus) dari harga per sentimeter (foam/bubble).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packaging_items', function (Blueprint $table) {
            $table->id();
            $table->string('item');
            $table->string('ukuran')->nullable();
            $table->string('dimensi')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('price_unit')->default('flat'); // 'flat' atau 'per_cm'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packaging_items');
    }
};
