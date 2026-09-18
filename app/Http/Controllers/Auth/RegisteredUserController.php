<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\StoreBusinessProfileRequest;
use App\Models\BusinessProfile;
use App\Models\CustomerAnswer;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\RegistrationQuestion;
use App\Models\User;
use App\Models\Village;
use App\Services\ActivityLogger;
use App\Services\AddressBook;
use App\Services\CustomerSegmenter;
use App\Support\ActivityAction;
use App\Support\CustomerType;
use App\Support\RegionChain;
use App\Support\RegistrationFlow;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Pendaftaran akun pelanggan.
 *
 * Pendaftaran dibagi menjadi beberapa langkah agar pertanyaannya tidak terasa
 * panjang, terutama bagi pelanggan perusahaan yang menjawab lima belas
 * pertanyaan. Jawaban tiap langkah divalidasi di server lalu disimpan sementara
 * di sesi; akun baru benar-benar dibuat setelah pengguna menyetujui ringkasan
 * di langkah terakhir.
 *
 * Alurnya:
 *
 *   pilih tipe -> data akun -> pertanyaan (1..n langkah) -> ringkasan -> daftar
 *
 * Susunan langkah dan isi pertanyaannya dibaca dari tabel
 * `registration_questions`, jadi controller ini tidak memuat satu pun daftar
 * pertanyaan. Lihat App\Support\RegistrationFlow.
 *
 * Data pengiriman (telepon, kota, kode pos, alamat) tetap diminta sejak awal
 * agar formulir penawaran tidak perlu menanyakannya lagi setiap kali — nilainya
 * langsung mengisi formulir "Minta Penawaran" pada halaman 3D Models.
 */
class RegisteredUserController extends Controller
{
    /** Pemilihan tipe akun: Personal atau Business. */
    public function create(): View
    {
        return view('auth.register', [
            'types' => CustomerType::all(),
            'selected' => $this->state()['customer_type'] ?? null,
        ]);
    }

    /**
     * Menyimpan pilihan tipe akun lalu membuka langkah pertama.
     *
     * Mengganti tipe akun membuang jawaban yang sudah terkumpul: pertanyaan
     * kedua tipe berbeda sama sekali, jadi jawaban lama tidak dapat dipakai.
     */
    public function type(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            ['customer_type' => ['required', Rule::in(CustomerType::keys())]],
            ['customer_type.required' => 'Silakan pilih tipe akun terlebih dahulu.'],
        );

        $state = $this->state();
        $type = $validated['customer_type'];

        if (($state['customer_type'] ?? null) !== $type) {
            $state = ['customer_type' => $type, 'account' => [], 'answers' => [], 'completed' => []];
        }

        $this->save($state);

        return redirect()->route('register.step', RegistrationFlow::firstStep());
    }

    /** Menampilkan satu langkah pengisian. */
    public function step(string $step): View|RedirectResponse
    {
        if ($redirect = $this->guard($step)) {
            return $redirect;
        }

        $state = $this->state();
        $type = $state['customer_type'];
        $entry = RegistrationFlow::step($type, $step);

        if ($step === RegistrationFlow::STEP_REVIEW) {
            return $this->review($state, $type, $entry);
        }

        if ($step === RegistrationFlow::STEP_COMPANY) {
            return view('auth.register-company', [
                'typeLabel' => CustomerType::label($type),
                'step' => $entry,
                'stepKey' => $step,
                // Sengaja bukan `company`: nama itu sudah dipakai profil
                // perusahaan NUSAMA3D yang dibagikan ke seluruh view lewat
                // view composer, dan akan menimpa data langkah ini.
                'companyData' => $state['company'] ?? [],
                'account' => $state['account'] ?? [],
                'industries' => BusinessProfile::INDUSTRIES,
                'provinces' => Province::ordered()->get(['id', 'name']),
                ...$this->companyRegionOptions($state['company'] ?? []),
                'progress' => RegistrationFlow::progress($type, $step),
                'stepCount' => RegistrationFlow::questionStepCount($type),
                'previousUrl' => $this->previousUrl($type, $step),
            ]);
        }

        $questions = RegistrationFlow::visibleQuestions($entry['questions'], $state['answers'] ?? []);

        return view('auth.register-step', [
            'customerType' => $type,
            'typeLabel' => CustomerType::label($type),
            'step' => $entry,
            'stepKey' => $step,
            'questions' => $questions,
            // Pertanyaan dikelompokkan menjadi bagian bertajuk supaya langkah
            // Kebutuhan Bisnis tidak terbaca sebagai formulir panjang.
            'sections' => RegistrationFlow::byCategory($questions),
            'answers' => $state['answers'] ?? [],
            'account' => $state['account'] ?? [],
            'prefill' => $this->prefill($state),
            'progress' => RegistrationFlow::progress($type, $step),
            'stepCount' => RegistrationFlow::questionStepCount($type),
            'previousUrl' => $this->previousUrl($type, $step),
        ]);
    }

    /**
     * Memvalidasi satu langkah lalu melanjutkan ke langkah berikutnya.
     *
     * Validasinya dijalankan di server untuk setiap langkah — bukan hanya di
     * browser — sehingga jawaban yang dikirim langsung tanpa melewati halaman
     * pun tetap diperiksa.
     */
    public function storeStep(Request $request, string $step): RedirectResponse
    {
        if ($redirect = $this->guard($step)) {
            return $redirect;
        }

        $state = $this->state();
        $type = $state['customer_type'];

        $state = match ($step) {
            RegistrationFlow::STEP_ACCOUNT => $this->fillAccount($state, $request, $type),
            RegistrationFlow::STEP_COMPANY => $this->fillCompany($state, $request),
            default => $this->fillAnswers($state, $request, $type, $step),
        };

        $state['completed'] = array_values(array_unique([...$state['completed'] ?? [], $step]));

        $this->save($state);

        return redirect()->route('register.step', RegistrationFlow::nextStep($type, $step));
    }

    /**
     * Membuat akun beserta seluruh jawabannya.
     *
     * Seluruh isi sesi divalidasi ulang di sini sebagai jaring pengaman: sesi
     * memang hanya terisi lewat langkah yang sudah divalidasi, tetapi
     * pemeriksaan terakhir ini memastikan tidak ada pertanyaan wajib yang
     * terlewat — misalnya karena pertanyaan baru ditambahkan admin di tengah
     * pengisian seseorang.
     */
    public function store(): RedirectResponse
    {
        if ($redirect = $this->guard(RegistrationFlow::STEP_REVIEW)) {
            return $redirect;
        }

        $state = $this->state();
        $type = $state['customer_type'];
        $answers = $state['answers'] ?? [];

        $questions = RegistrationFlow::visibleQuestions(RegistrationFlow::questions($type), $answers);

        $missing = $questions->first(
            fn (RegistrationQuestion $question) => $question->is_required && blank($answers[$question->id] ?? null)
        );

        if ($missing !== null) {
            return redirect()
                ->route('register.step', (string) $missing->step)
                ->withErrors(['questions' => 'Silakan lengkapi informasi terlebih dahulu: '.$missing->question]);
        }

        // Akun dan jawabannya tersimpan bersama-sama: akun tanpa jawaban tidak
        // membawa informasi apa pun untuk admin, jadi keduanya harus berhasil.
        // Akun hasil pendaftaran dikembalikan dari transaksi agar dapat dicatat
        // ke jejak audit setelah seluruh datanya benar-benar tersimpan.
        $user = DB::transaction(function () use ($state, $type, $questions, $answers) {
            $user = (new User([
                ...collect($state['account'])->only(['name', 'phone', 'city', 'postal_code', 'address', 'email'])->all(),
                'customer_type' => $type,
                // Sudah di-hash sejak disimpan ke sesi; cast `hashed` pada model
                // membiarkan nilai yang memang sudah berupa hash.
                'password' => $state['account']['password'],
            ]))->forceFill([
                // Pendaftaran publik SELALU menghasilkan akun pelanggan.
                'role' => User::ROLE_USER,
                'is_active' => true,
            ]);
            $user->save();

            $rows = [];
            $now = now();

            foreach ($questions as $question) {
                foreach ((array) ($answers[$question->id] ?? []) as $value) {
                    $rows[] = [
                        'user_id' => $user->id,
                        'question_id' => $question->id,
                        'answer' => $value,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if ($rows !== []) {
                CustomerAnswer::insert($rows);
            }

            if ($type === CustomerType::BUSINESS) {
                $this->createBusinessProfile($user, $state, $questions, $answers);

                return $user;
            }

            // Data pengiriman sudah diminta pada langkah Data Akun, jadi buku
            // alamatnya langsung terisi satu alamat utama — pelanggan tidak
            // perlu mengetikkannya lagi sebelum meminta penawaran pertamanya.
            app(AddressBook::class)->createFromProfile($user);

            return $user;
        });

        // Kata sandi yang baru didaftarkan tidak ikut dicatat; yang tersimpan
        // hanya identitas akun yang terbentuk.
        app(ActivityLogger::class)->log(
            action: ActivityAction::REGISTER,
            description: 'Mendaftarkan akun '.CustomerType::label($type).' baru.',
            subject: $user,
            new: [
                'name' => $user->name,
                'email' => $user->email,
                'customer_type' => $type,
            ],
            actor: $user,
        );

        session()->forget(RegistrationFlow::SESSION_KEY);

        // Akun baru sengaja tidak langsung dimasukkan ke sesi: dashboard hanya
        // terbuka setelah pemiliknya benar-benar berhasil masuk memakai email
        // dan kata sandi yang baru saja didaftarkan.
        return redirect()
            ->route('login')
            ->with('status', $type === CustomerType::BUSINESS
                ? 'Registrasi Business berhasil. Silakan login menggunakan email dan password Anda.'
                : 'Registrasi berhasil. Silakan login menggunakan email dan password Anda.');
    }

    /**
     * Profil perusahaan, segmen pelanggan, dan alamat pengiriman pertamanya.
     *
     * Alamat perusahaan sekaligus menjadi alamat pengiriman utama akun,
     * sehingga pelanggan Business tidak perlu mengetik ulang alamat yang sama
     * di menu Alamat sebelum dapat meminta penawaran.
     *
     * @param  array<string, mixed>  $state
     * @param  Collection<int, RegistrationQuestion>  $questions
     * @param  array<int, array<int, string>>  $answers
     */
    private function createBusinessProfile(User $user, array $state, Collection $questions, array $answers): void
    {
        $company = $state['company'] ?? [];

        // Jawaban dikunci ulang memakai `key` pertanyaannya agar penggolongan
        // tidak bergantung pada id yang berbeda-beda antar-pemasangan.
        $byKey = $questions
            ->mapWithKeys(fn (RegistrationQuestion $question) => [
                $question->key => $answers[$question->id] ?? [],
            ])
            ->all();

        $user->businessProfile()->create([
            ...collect($company)->only([
                'company_name', 'pic_name', 'position', 'phone', 'email', 'website',
                'industry', 'address', 'province_id', 'regency_id', 'district_id',
                'village_id', 'postal_code',
            ])->all(),
            'segment' => app(CustomerSegmenter::class)->segment($company['industry'] ?? null, $byKey),
        ]);

        app(AddressBook::class)->create($user, [
            'label' => 'Alamat Perusahaan',
            'recipient_name' => $company['pic_name'] ?? $user->name,
            'recipient_phone' => $company['phone'] ?? (string) $user->phone,
            'province_id' => $company['province_id'] ?? null,
            'regency_id' => $company['regency_id'] ?? null,
            'district_id' => $company['district_id'] ?? null,
            'village_id' => $company['village_id'] ?? null,
            'postal_code' => $company['postal_code'] ?? null,
            'detail' => $company['address'] ?? '',
            'note' => null,
        ]);
    }

    /**
     * Ringkasan seluruh isian sebelum akun dibuat.
     *
     * Tiap kelompok membawa tautan Edit ke langkahnya sendiri sehingga
     * pengguna dapat memperbaiki satu bagian tanpa mengulang dari awal.
     *
     * @param  array<string, mixed>  $state
     * @param  array<string, mixed>  $entry
     */
    private function review(array $state, string $type, array $entry): View
    {
        $answers = $state['answers'] ?? [];
        $questions = RegistrationFlow::visibleQuestions(RegistrationFlow::questions($type), $answers);

        // Dikelompokkan per bagian bila pertanyaannya memang berkategori
        // (pendaftaran Business), atau per langkah bila tidak (Personal).
        $groups = $questions
            ->groupBy(fn (RegistrationQuestion $question) => $question->category ?: $question->step_label)
            ->map(fn (Collection $group) => [
                'label' => $group->first()->category ?: $group->first()->step_label,
                'editUrl' => route('register.step', (string) $group->first()->step),
                'items' => $group
                    ->map(fn (RegistrationQuestion $question) => [
                        'question' => $question->question,
                        'answer' => implode(', ', (array) ($answers[$question->id] ?? [])),
                    ])
                    ->filter(fn (array $item) => $item['answer'] !== '')
                    ->values(),
            ])
            ->values();

        return view('auth.register-review', [
            'customerType' => $type,
            'typeLabel' => CustomerType::label($type),
            'step' => $entry,
            'stepKey' => RegistrationFlow::STEP_REVIEW,
            'account' => $state['account'] ?? [],
            // Nama `company` dihindari: view composer sudah memakainya untuk
            // profil perusahaan NUSAMA3D dan akan menimpanya.
            'companyData' => $this->companySummary($state['company'] ?? []),
            'companyEditUrl' => route('register.step', RegistrationFlow::STEP_COMPANY),
            'stepCount' => RegistrationFlow::questionStepCount($type),
            'groups' => $groups,
            'progress' => RegistrationFlow::progress($type, RegistrationFlow::STEP_REVIEW),
            'accountEditUrl' => route('register.step', RegistrationFlow::STEP_ACCOUNT),
            'previousUrl' => $this->previousUrl($type, RegistrationFlow::STEP_REVIEW),
        ]);
    }

    /* ------------------------------------------------------------ internal --- */

    /**
     * Memastikan langkah yang dibuka memang boleh diakses.
     *
     * Pengguna yang melompati langkah — atau membuka tautan langkah tanpa
     * pernah memilih tipe akun — dikembalikan ke langkah pertama yang belum
     * terisi, bukan disuguhi formulir setengah jadi.
     */
    private function guard(string $step): ?RedirectResponse
    {
        $state = $this->state();
        $type = $state['customer_type'] ?? null;

        if (! CustomerType::exists($type)) {
            return redirect()->route('register');
        }

        if (! RegistrationFlow::stepExists($type, $step)) {
            return redirect()->route('register.step', RegistrationFlow::firstStep());
        }

        $completed = $state['completed'] ?? [];

        foreach (RegistrationFlow::stepKeys($type) as $key) {
            if ($key === $step) {
                break;
            }

            if ($key !== RegistrationFlow::STEP_REVIEW && ! in_array($key, $completed, true)) {
                return redirect()->route('register.step', $key);
            }
        }

        return null;
    }

    /**
     * Menyimpan data akun setelah divalidasi aturan yang sama dengan profil.
     *
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function fillAccount(array $state, Request $request, string $customerType): array
    {
        // Pelanggan perusahaan hanya mengisi kontak di langkah ini; alamatnya
        // ditanyakan pada langkah Data Perusahaan.
        $rules = (new RegisterRequest)->forCustomerType($customerType);

        $data = $request->validate($rules->rules(), $rules->messages());

        $state['account'] = [
            ...collect($data)->only(['name', 'phone', 'city', 'postal_code', 'address', 'email'])->all(),
            // Kata sandi tidak pernah tersimpan apa adanya di sesi.
            'password' => Hash::make($data['password']),
        ];

        return $state;
    }

    /**
     * Menyimpan data perusahaan setelah divalidasi, termasuk rantai wilayahnya.
     *
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function fillCompany(array $state, Request $request): array
    {
        $rules = new StoreBusinessProfileRequest;

        $validator = validator($request->all(), $rules->rules(), $rules->messages());
        $validator->after(fn ($validator) => RegionChain::validate($validator, $request));

        $state['company'] = $validator->validate();

        return $state;
    }

    /**
     * Data perusahaan siap tampil pada halaman ringkasan.
     *
     * Id wilayah diterjemahkan menjadi namanya supaya yang dibaca pelanggan
     * adalah "Melong, Cimahi Selatan, Kota Cimahi, Jawa Barat", bukan deretan
     * angka. Kosong bila pendaftarannya bukan Business.
     *
     * @param  array<string, mixed>  $company
     * @return array<string, string>
     */
    private function companySummary(array $company): array
    {
        if ($company === []) {
            return [];
        }

        $region = collect([
            Village::find($company['village_id'] ?? null)?->name,
            District::find($company['district_id'] ?? null)?->name,
            Regency::find($company['regency_id'] ?? null)?->name,
            Province::find($company['province_id'] ?? null)?->name,
        ])->filter()->implode(', ');

        return array_filter([
            'Nama Perusahaan' => $company['company_name'] ?? null,
            'Nama PIC' => $company['pic_name'] ?? null,
            'Jabatan' => $company['position'] ?? null,
            'Industri' => $company['industry'] ?? null,
            'Nomor Telepon' => $company['phone'] ?? null,
            'Email Perusahaan' => $company['email'] ?? null,
            'Website' => $company['website'] ?? null,
            'Alamat' => trim(($company['address'] ?? '').', '.$region.' '.($company['postal_code'] ?? ''), ', '),
        ], fn (?string $value) => filled($value));
    }

    /**
     * Pilihan wilayah yang sudah terpilih pada langkah Data Perusahaan.
     *
     * Hanya tingkat yang induknya sudah dipilih yang ikut dimuat; sisanya
     * diambil menyusul oleh dropdown bertingkat di browser.
     *
     * @param  array<string, mixed>  $company
     * @return array<string, mixed>
     */
    private function companyRegionOptions(array $company): array
    {
        return [
            'regencies' => filled($company['province_id'] ?? null)
                ? Regency::where('province_id', $company['province_id'])->ordered()->get(['id', 'name'])
                : collect(),
            'districts' => filled($company['regency_id'] ?? null)
                ? District::where('regency_id', $company['regency_id'])->ordered()->get(['id', 'name'])
                : collect(),
            'villages' => filled($company['district_id'] ?? null)
                ? Village::where('district_id', $company['district_id'])->ordered()->get(['id', 'name'])
                : collect(),
        ];
    }

    /**
     * Menyimpan jawaban satu langkah setelah divalidasi.
     *
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function fillAnswers(array $state, Request $request, string $type, string $step): array
    {
        $entry = RegistrationFlow::step($type, $step);
        $existing = $state['answers'] ?? [];

        // Pertanyaan bersyarat ditentukan dari jawaban yang baru dikirim pada
        // langkah ini, bukan dari jawaban lama, supaya pengguna yang mengubah
        // jawaban induknya langsung mengikuti aturan yang baru.
        $submitted = RegistrationFlow::normalize($entry['questions'], (array) $request->input('questions', []));

        // array_replace, bukan spread: kunci array ini adalah id pertanyaan,
        // dan spread akan menomori ulang kunci numerik sehingga jawabannya
        // tidak lagi dapat dicocokkan dengan pertanyaannya.
        $questions = RegistrationFlow::visibleQuestions(
            $entry['questions'],
            array_replace($existing, $submitted),
        );

        $request->validate(
            RegistrationFlow::rules($questions),
            RegistrationFlow::messages($questions),
        );

        $answers = RegistrationFlow::normalize($questions, (array) $request->input('questions', []));

        // Jawaban pertanyaan yang kini tersembunyi ikut dibuang agar ringkasan
        // tidak menampilkan sisa jawaban yang sudah tidak berlaku.
        foreach ($entry['questions'] as $question) {
            unset($existing[$question->id]);
        }

        $state['answers'] = $existing + $answers;

        return $state;
    }

    /**
     * Nilai bawaan bagi pertanyaan yang jawabannya sudah diketahui dari data akun.
     *
     * Pelanggan perusahaan diminta menuliskan nama penanggung jawab dan email
     * perusahaan; keduanya diisikan lebih dulu dari data akun agar tidak perlu
     * mengetik ulang, tetapi tetap boleh diubah karena penanggung jawab dan
     * email perusahaan tidak selalu sama dengan pemilik akun.
     *
     * @param  array<string, mixed>  $state
     * @return array<string, string>
     */
    private function prefill(array $state): array
    {
        $account = $state['account'] ?? [];

        return array_filter([
            'business_contact_name' => $account['name'] ?? null,
            'business_email' => $account['email'] ?? null,
        ]);
    }

    private function previousUrl(string $type, string $step): ?string
    {
        $previous = RegistrationFlow::previousStep($type, $step);

        return $previous === null
            ? route('register')
            : route('register.step', $previous);
    }

    /** @return array<string, mixed> */
    private function state(): array
    {
        return (array) session(RegistrationFlow::SESSION_KEY, []);
    }

    /** @param  array<string, mixed>  $state */
    private function save(array $state): void
    {
        session()->put(RegistrationFlow::SESSION_KEY, $state);
    }
}
