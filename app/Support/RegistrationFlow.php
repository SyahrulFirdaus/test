<?php

namespace App\Support;

use App\Models\RegistrationQuestion;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Penyusun alur pendaftaran bertahap.
 *
 * Langkah-langkahnya tidak ditulis di dalam controller maupun view: seluruhnya
 * dibentuk dari isi tabel `registration_questions`. Menambah satu pertanyaan
 * pada langkah yang sudah ada cukup menambah barisnya; menambah langkah baru
 * cukup memakai nomor `step` yang belum terpakai. Progress bar, tombol
 * Kembali/Lanjut, dan validasinya mengikuti dengan sendirinya.
 *
 * Susunan langkahnya selalu:
 *
 *   account  data akun (nama, kontak, alamat, kata sandi)
 *   1..n     kelompok pertanyaan, satu langkah per nilai `step`
 *   review   ringkasan sebelum akun benar-benar dibuat
 *
 * Pelanggan perorangan hanya punya satu kelompok pertanyaan sehingga alurnya
 * terasa pendek; pelanggan perusahaan terbagi menjadi lima.
 */
class RegistrationFlow
{
    /** Kunci penyimpanan jawaban sementara selama pendaftaran belum selesai. */
    public const SESSION_KEY = 'registration';

    public const STEP_ACCOUNT = 'account';

    /** Data perusahaan; hanya ada pada pendaftaran Business. */
    public const STEP_COMPANY = 'company';

    public const STEP_REVIEW = 'review';

    /**
     * Seluruh pertanyaan aktif untuk satu tipe pelanggan.
     *
     * @return Collection<int, RegistrationQuestion>
     */
    public static function questions(string $customerType): Collection
    {
        return RegistrationQuestion::query()
            ->forType($customerType)
            ->active()
            ->ordered()
            ->get();
    }

    /**
     * Daftar langkah lengkap beserta pertanyaan di dalamnya.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function steps(string $customerType): array
    {
        $isBusiness = $customerType === CustomerType::BUSINESS;

        $steps = [[
            'key' => self::STEP_ACCOUNT,
            // Pelanggan perusahaan hanya mengisi kontak di sini; alamatnya
            // ditanyakan pada langkah Data Perusahaan berikutnya.
            'label' => $isBusiness ? 'Data Kontak' : 'Data Akun',
            'description' => $isBusiness
                ? 'Kontak penanggung jawab akun beserta kata sandinya.'
                : 'Informasi kontak dan pengiriman yang dipakai pada setiap penawaran Anda.',
            'number' => $isBusiness ? 1 : null,
            'questions' => collect(),
        ]];

        if ($isBusiness) {
            $steps[] = [
                'key' => self::STEP_COMPANY,
                'label' => 'Data Perusahaan',
                'description' => 'Identitas perusahaan beserta alamatnya. Data ini dipakai kembali pada setiap penawaran Anda.',
                'number' => 2,
                'questions' => collect(),
            ];
        }

        $groups = self::questions($customerType)->groupBy('step');
        // Penomoran melanjutkan langkah yang sudah ada di atas, jadi pelanggan
        // perusahaan membaca "Step 3 dari 4" pada kelompok pertanyaannya.
        $number = $isBusiness ? 2 : 0;

        foreach ($groups as $step => $questions) {
            $number++;

            $steps[] = [
                'key' => (string) $step,
                'label' => $questions->first()->step_label ?: 'Pertanyaan '.$number,
                'description' => null,
                'number' => $number,
                'questions' => $questions,
            ];
        }

        $steps[] = [
            'key' => self::STEP_REVIEW,
            'label' => 'Review',
            'description' => 'Periksa kembali sebelum akun dibuat.',
            'number' => $isBusiness ? $number + 1 : null,
            'questions' => collect(),
        ];

        return $steps;
    }

    /**
     * Banyaknya langkah bernomor, dipakai tulisan "Step 2 dari 4".
     *
     * Pelanggan perorangan hanya menomori kelompok pertanyaannya; pelanggan
     * perusahaan menomori seluruh langkahnya — Data Kontak, Data Perusahaan,
     * Kebutuhan Bisnis, dan Review — sesuai penunjuk kemajuan yang dilihatnya.
     */
    public static function questionStepCount(string $customerType): int
    {
        if ($customerType === CustomerType::BUSINESS) {
            return count(array_filter(
                array_column(self::steps($customerType), 'number'),
                fn ($number) => $number !== null,
            ));
        }

        return self::questions($customerType)->groupBy('step')->count();
    }

    /**
     * Pertanyaan satu langkah, dikelompokkan menurut bagiannya.
     *
     * Pertanyaan tanpa kategori — seluruh pendaftaran Personal — jatuh ke satu
     * kelompok tanpa tajuk sehingga tampil persis seperti sebelumnya.
     *
     * @param  Collection<int, RegistrationQuestion>  $questions
     * @return Collection<string, Collection<int, RegistrationQuestion>>
     */
    public static function byCategory(Collection $questions): Collection
    {
        return $questions
            ->groupBy(fn (RegistrationQuestion $question) => (string) $question->category)
            ->sortBy(fn (Collection $group) => $group->first()->category_order);
    }

    /** @return array<int, string> kunci setiap langkah, berurutan */
    public static function stepKeys(string $customerType): array
    {
        return array_column(self::steps($customerType), 'key');
    }

    public static function stepExists(string $customerType, string $step): bool
    {
        return in_array($step, self::stepKeys($customerType), true);
    }

    /** @return array<string, mixed>|null */
    public static function step(string $customerType, string $step): ?array
    {
        foreach (self::steps($customerType) as $entry) {
            if ($entry['key'] === $step) {
                return $entry;
            }
        }

        return null;
    }

    public static function firstStep(): string
    {
        return self::STEP_ACCOUNT;
    }

    /** Langkah sesudahnya; null bila sudah di ujung. */
    public static function nextStep(string $customerType, string $step): ?string
    {
        $keys = self::stepKeys($customerType);
        $index = array_search($step, $keys, true);

        return $index === false ? null : ($keys[$index + 1] ?? null);
    }

    /** Langkah sebelumnya; null bila sudah di awal. */
    public static function previousStep(string $customerType, string $step): ?string
    {
        $keys = self::stepKeys($customerType);
        $index = array_search($step, $keys, true);

        return $index === false || $index === 0 ? null : $keys[$index - 1];
    }

    /**
     * Kemajuan pengisian dalam persen, dipakai progress bar.
     *
     * Langkah ringkasan dihitung sebagai selesai penuh karena pada titik itu
     * tidak ada lagi yang perlu diisi.
     */
    public static function progress(string $customerType, string $step): int
    {
        $keys = self::stepKeys($customerType);
        $index = array_search($step, $keys, true);

        if ($index === false) {
            return 0;
        }

        // Langkah terakhir adalah ringkasan; kemajuan diukur terhadap langkah
        // pengisian saja supaya bar sudah penuh saat ringkasan terbuka.
        $fillable = max(1, count($keys) - 1);

        return (int) round(min(1, $index / $fillable) * 100);
    }

    /**
     * Pertanyaan yang benar-benar perlu ditampilkan pada satu langkah.
     *
     * Pertanyaan bersyarat — mis. rincian dokumen resmi — hanya muncul bila
     * pertanyaan induknya sudah dijawab dengan nilai pemicunya.
     *
     * @param  Collection<int, RegistrationQuestion>  $questions
     * @param  array<int, mixed>  $answers  jawaban terkumpul, dikunci id pertanyaan
     * @return Collection<int, RegistrationQuestion>
     */
    public static function visibleQuestions(Collection $questions, array $answers): Collection
    {
        // Pertanyaan induk dapat berada di langkah mana pun, jadi pencarian
        // nilainya memakai seluruh pertanyaan milik tipe pelanggan tersebut.
        return $questions->filter(function (RegistrationQuestion $question) use ($questions, $answers) {
            if (! $question->isConditional()) {
                return true;
            }

            $parent = $questions->firstWhere('key', $question->depends_on_key);

            if ($parent === null) {
                return true;
            }

            $given = (array) ($answers[$parent->id] ?? []);

            return in_array($question->depends_on_value, $given, true);
        })->values();
    }

    /**
     * Aturan validasi satu langkah, disusun dari jenis tiap pertanyaan.
     *
     * Selalu dijalankan di server: JavaScript hanya membantu kenyamanan
     * pengisian, bukan menjadi penentu sah atau tidaknya jawaban.
     *
     * @param  Collection<int, RegistrationQuestion>  $questions  pertanyaan yang tampil
     * @return array<string, mixed>
     */
    public static function rules(Collection $questions): array
    {
        $rules = [];

        foreach ($questions as $question) {
            $field = 'questions.'.$question->id;
            $required = $question->is_required ? 'required' : 'nullable';

            if ($question->isMultiple()) {
                $rules[$field] = $question->is_required
                    ? ['required', 'array', 'min:1']
                    : ['nullable', 'array'];
                $rules[$field.'.*'] = ['string', Rule::in($question->resolvedOptions())];

                continue;
            }

            if ($question->isChoice()) {
                $rules[$field] = [$required, 'string', Rule::in($question->resolvedOptions())];

                continue;
            }

            $rules[$field] = match ($question->type) {
                'email' => [$required, 'string', 'email:rfc', 'max:160'],
                'url' => [$required, 'string', 'url', 'max:255'],
                // Jawaban bebas boleh panjang: pertanyaan tentang tantangan
                // perusahaan memang meminta penjelasan, bukan satu baris.
                'textarea' => [$required, 'string', 'max:2000'],
                default => [$required, 'string', 'max:255'],
            };
        }

        return $rules;
    }

    /**
     * Pesan kesalahan yang menyebut pertanyaannya, bukan nama field teknis.
     *
     * @param  Collection<int, RegistrationQuestion>  $questions
     * @return array<string, string>
     */
    public static function messages(Collection $questions): array
    {
        $messages = [];

        foreach ($questions as $question) {
            $field = 'questions.'.$question->id;

            $messages[$field.'.required'] = 'Silakan lengkapi informasi terlebih dahulu: '.$question->question;
            $messages[$field.'.min'] = 'Pilih minimal satu jawaban untuk: '.$question->question;
            $messages[$field.'.in'] = 'Pilihan jawaban tidak dikenal untuk: '.$question->question;
            $messages[$field.'.*.in'] = 'Pilihan jawaban tidak dikenal untuk: '.$question->question;
            $messages[$field.'.email'] = 'Format email pada "'.$question->question.'" belum benar.';
            $messages[$field.'.url'] = 'Alamat website pada "'.$question->question.'" belum benar. Awali dengan https://';
        }

        return $messages;
    }

    /**
     * Jawaban yang sudah dibersihkan dan siap disimpan.
     *
     * Nilainya selalu berbentuk array — pertanyaan berjawaban tunggal berisi
     * satu elemen — supaya penyimpanannya seragam: satu baris per nilai.
     *
     * @param  Collection<int, RegistrationQuestion>  $questions
     * @param  array<int, mixed>  $input
     * @return array<int, array<int, string>>
     */
    public static function normalize(Collection $questions, array $input): array
    {
        $answers = [];

        foreach ($questions as $question) {
            $value = $input[$question->id] ?? null;

            $values = collect(is_array($value) ? $value : [$value])
                ->map(fn ($item) => trim((string) $item))
                ->filter(fn (string $item) => $item !== '')
                ->values()
                ->all();

            if ($values !== []) {
                $answers[$question->id] = $values;
            }
        }

        return $answers;
    }
}
