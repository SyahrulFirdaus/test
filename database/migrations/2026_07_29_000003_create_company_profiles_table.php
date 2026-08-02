<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tagline');
            $table->string('legal_name')->nullable();
            $table->unsignedSmallInteger('founded_year')->nullable();
            $table->text('short_description');
            $table->text('about');
            $table->text('vision');
            $table->json('missions');
            $table->json('advantages')->comment('Keunggulan: icon, title, description');
            $table->json('stats')->comment('Angka pencapaian: value, suffix, label');
            $table->string('address');
            $table->string('city')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->string('operational_hours')->nullable();
            $table->string('maps_url')->nullable();
            $table->json('socials')->nullable()->comment('platform => url');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_profiles');
    }
};
