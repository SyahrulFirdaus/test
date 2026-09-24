<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pindahkan pengelolaan warna dari menu Color terpisah ke dalam Material.
 *
 * Setiap material kini dapat memiliki nama warna dan kode hexa sendiri, tanpa
 * perlu mereferensikan tabel print_colors. Data warna yang ada dimigrasikan dari
 * print_colors ke field baru ini berdasarkan colors yang terdaftar di technical_spec.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_materials', function (Blueprint $table) {
            $table->string('color_name')->nullable()->after('material');
            $table->string('color_hex', 7)->nullable()->after('color_name');
        });
    }

    public function down(): void
    {
        Schema::table('print_materials', function (Blueprint $table) {
            $table->dropColumn(['color_name', 'color_hex']);
        });
    }
};
