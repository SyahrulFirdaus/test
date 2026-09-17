<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Simbol em dash tidak lagi dipakai pada teks yang dilihat pengguna.
 *
 * Teks bantuan pertanyaan pendaftaran tersimpan di basis data (dari
 * RegistrationQuestionSeeder), jadi mengubah seeder saja tidak cukup untuk
 * data yang sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('registration_questions')
            ->where('help', 'like', "%\u{2014}%")
            ->get(['id', 'help'])
            ->each(fn ($row) => DB::table('registration_questions')->where('id', $row->id)->update([
                'help' => str_replace(" \u{2014} ", ', ', $row->help),
            ]));
    }

    public function down(): void
    {
        // Tidak dikembalikan: teksnya tetap bermakna sama.
    }
};
