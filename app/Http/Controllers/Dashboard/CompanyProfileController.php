<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBusinessProfileRequest;
use App\Models\BusinessProfile;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\RegistrationQuestion;
use App\Models\Village;
use App\Services\ActivityLogger;
use App\Services\CustomerSegmenter;
use App\Support\ActivityAction;
use App\Support\CustomerType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Informasi Perusahaan milik akun Business.
 *
 * Data ini dikumpulkan sekali saat pendaftaran, lalu dibaca ulang setiap kali
 * pelanggan meminta penawaran — nama perusahaan pada modal "Minta Penawaran"
 * berasal dari sini dan tidak dapat diketik ulang di sana. Halaman inilah satu-
 * satunya tempat mengubahnya.
 *
 * Aturan validasinya dipakai bersama dengan langkah pendaftaran lewat
 * App\Http\Requests\StoreBusinessProfileRequest, sehingga data yang disunting
 * belakangan tidak pernah lebih longgar daripada yang diisi saat mendaftar.
 */
class CompanyProfileController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function edit(Request $request): View
    {
        $profile = $request->user()->businessProfile;

        return view('dashboard.company-profile', [
            'profile' => $profile,
            'industries' => BusinessProfile::INDUSTRIES,
            'provinces' => Province::ordered()->get(['id', 'name']),

            // Tingkat wilayah di bawah provinsi hanya diisi sejauh rantainya
            // sudah terpilih; sisanya diambil browser lewat region-cascade.
            'regencies' => $profile?->province_id
                ? Regency::where('province_id', $profile->province_id)->ordered()->get(['id', 'name'])
                : collect(),
            'districts' => $profile?->regency_id
                ? District::where('regency_id', $profile->regency_id)->ordered()->get(['id', 'name'])
                : collect(),
            'villages' => $profile?->district_id
                ? Village::where('district_id', $profile->district_id)->ordered()->get(['id', 'name'])
                : collect(),
        ]);
    }

    public function update(StoreBusinessProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->safe()->only([
            'company_name', 'pic_name', 'position', 'phone', 'email', 'website',
            'industry', 'address', 'province_id', 'regency_id', 'district_id',
            'village_id', 'postal_code',
        ]);

        // Penggolongan pelanggan sebagian ditentukan industrinya, jadi ikut
        // dihitung ulang memakai jawaban pendaftaran yang tersimpan — bukan
        // dibiarkan memakai industri yang lama.
        $data['segment'] = app(CustomerSegmenter::class)->segment(
            $data['industry'] ?? null,
            $this->answersByKey($user),
        );

        // Keadaan lama dibaca sebelum disimpan agar perubahan data perusahaan
        // terbaca berdampingan pada jejak audit.
        $before = $user->businessProfile?->only(array_keys($data)) ?? [];

        $profile = $user->businessProfile()->updateOrCreate(['user_id' => $user->id], $data);

        $this->activity->logChanges(
            action: ActivityAction::COMPANY_UPDATE,
            before: $before,
            after: $data,
            description: 'Memperbarui informasi perusahaan.',
            subject: $profile,
            actor: $user,
            subjectLabel: $profile->company_name,
        );

        return back()->with('status', 'Informasi perusahaan berhasil diperbarui. '
            .'Nama perusahaan pada permintaan penawaran berikutnya mengikuti data ini.');
    }

    /**
     * Jawaban pendaftaran dikunci ulang memakai `key` pertanyaannya.
     *
     * Dikunci lewat `key`, bukan id, agar penggolongan tidak bergantung pada
     * nomor pertanyaan yang berbeda-beda antar-pemasangan.
     *
     * @return array<string, array<int, string>>
     */
    private function answersByKey($user): array
    {
        $questions = RegistrationQuestion::query()
            ->where('customer_type', CustomerType::BUSINESS)
            ->get(['id', 'key']);

        $answers = $user->customerAnswers()->get()->groupBy('question_id');

        return $questions
            ->mapWithKeys(fn (RegistrationQuestion $question) => [
                $question->key => ($answers[$question->id] ?? collect())->pluck('answer')->all(),
            ])
            ->all();
    }
}
