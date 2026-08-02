<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('tagline');
            $table->text('excerpt');
            $table->text('description');
            $table->string('icon')->comment('Nama komponen ikon SVG di resources/views/components/icons');
            $table->string('image')->nullable()->comment('Path ilustrasi relatif terhadap /public');
            $table->json('highlights')->nullable()->comment('Poin-poin singkat yang ditampilkan pada card');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
