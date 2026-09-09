<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu nilai jawaban pendaftaran milik seorang pelanggan.
 *
 * Pertanyaan berjawaban ganda menghasilkan beberapa baris untuk satu
 * pertanyaan — satu baris per pilihan yang dicentang — supaya jawabannya dapat
 * disaring lewat kueri biasa alih-alih diurai dari JSON.
 */
class CustomerAnswer extends Model
{
    protected $fillable = [
        'user_id',
        'question_id',
        'answer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(RegistrationQuestion::class, 'question_id');
    }

    /** Jawaban atas satu pertanyaan tertentu, dirujuk lewat `key`-nya. */
    public function scopeForQuestionKey(Builder $query, string $key): Builder
    {
        return $query->whereHas('question', fn (Builder $question) => $question->where('key', $key));
    }
}
