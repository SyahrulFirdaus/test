<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Detail kebutuhan dari formulir konsultasi /miniatur-teknik: jumlah
 * pesanan, perkiraan dimensi, dan lampiran referensi (opsional). Kolom
 * umum, jadi dapat dipakai formulir lain yang memakai sistem lead yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->nullable()->after('service');
            $table->string('dimensions', 120)->nullable()->after('quantity');
            $table->json('attachments')->nullable()->after('notes')->comment('[{path, name, size, uploaded_at}] di disk local');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'dimensions', 'attachments']);
        });
    }
};
