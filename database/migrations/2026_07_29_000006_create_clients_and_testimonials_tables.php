<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->comment('Path logo relatif terhadap /public');
            $table->string('industry')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role')->nullable()->comment('Jabatan atau perusahaan, bila diketahui');
            $table->unsignedTinyInteger('rating')->default(5);
            $table->text('quote');
            $table->string('source')->nullable()->comment('Asal ulasan, mis. Google Review');
            $table->string('reviewed_label')->nullable()->comment('Keterangan waktu apa adanya dari sumber, mis. "2 tahun lalu"');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('clients');
    }
};
