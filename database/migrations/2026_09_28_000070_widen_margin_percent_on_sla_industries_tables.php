<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Margin Profit Kalkulator Manual tidak lagi dibatasi 30%–50%, jadi kolomnya
 * dilebarkan dari decimal(5,2) (paling besar 999,99%) menjadi decimal(8,2)
 * (paling besar 999.999,99%). Nilai yang sudah tersimpan tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sla_industries_formulas', function (Blueprint $table) {
            $table->decimal('margin_percent', 8, 2)->default(50)->change();
        });

        Schema::table('sla_industries_quotes', function (Blueprint $table) {
            $table->decimal('margin_percent', 8, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('sla_industries_formulas', function (Blueprint $table) {
            $table->decimal('margin_percent', 5, 2)->default(50)->change();
        });

        Schema::table('sla_industries_quotes', function (Blueprint $table) {
            $table->decimal('margin_percent', 5, 2)->default(0)->change();
        });
    }
};
