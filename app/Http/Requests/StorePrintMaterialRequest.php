<?php

namespace App\Http\Requests;

use App\Models\PrintMaterial;
use App\Models\PrintTechnology;
use App\Support\Finishing;
use App\Support\PricingMethod;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Kolom komersial satu baris material Price List.
 *
 * Harga per gram, Pembulatan Harga, dan Harga/10 gram tidak divalidasi di
 * sini — semuanya dihitung otomatis dari Harga Beli/Harga Jual, lihat
 * App\Models\Concerns\HasMaterialPricing.
 *
 * Nama material dijaga unik PER MESIN di dalam satu teknologi, bukan unik
 * global: "PLA+" boleh berdiri sendiri pada tiap mesin, dan dua teknologi juga
 * boleh sama-sama punya "PLA+". Namanya itulah yang tersimpan pada
 * `quotation_items.material`.
 *
 * Material tanpa mesin ikut dijaga di sini, bukan oleh indeks unik basis data:
 * NULL selalu dianggap berbeda oleh indeks unik, sehingga dua "PLA+" tanpa
 * mesin akan lolos bila hanya mengandalkan basis data.
 */
class StorePrintMaterialRequest extends FormRequest
{
    /**
     * Batas atas Harga Beli/Harga Jual: kolomnya `decimal(12,2)`.
     *
     * Aturan `numeric` saja tidak cukup — ia meloloskan notasi ilmiah seperti
     * "2e23", yang baru gagal di MySQL sebagai galat 500 alih-alih pesan
     * validasi yang terbaca.
     */
    private const MAX_PRICE = '9999999999.99';

    public function authorize(): bool
    {
        // Route-nya sudah dijaga middleware `superadmin`.
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $technology = $this->route('technology');
        $material = $this->route('material');

        // Kiriman kosong dari dropdown "Tanpa mesin" datang sebagai string
        // kosong; disamakan dengan null supaya pencocokan uniknya tepat.
        $machineId = $this->filled('machine_cost_id') ? (int) $this->input('machine_cost_id') : null;

        return [
            'material' => [
                'required', 'string', 'max:120',
                Rule::unique('print_materials', 'material')
                    ->where('print_technology_id', $technology instanceof PrintTechnology ? $technology->getKey() : null)
                    ->where('machine_cost_id', $machineId)
                    ->ignore($material),
            ],
            'machine_cost_id' => ['nullable', 'integer', 'exists:machine_costs,id'],
            'brand' => ['required', 'string', 'max:60'],
            // Daftar warna milik material ini. Baris yang sama sekali kosong
            // sudah dibuang prepareForValidation(), jadi yang tersisa wajib
            // lengkap — nama tanpa hex adalah kiriman setengah jadi.
            'colors' => ['nullable', 'array', 'max:50', $this->noDuplicateColorNames()],
            // Kelebihan dan kekurangan material, satu baris satu butir. Yang
            // dilihat pelanggan pada Edit Specification adalah daftar ini.
            'advantages' => ['nullable', 'string', 'max:2000'],
            'disadvantages' => ['nullable', 'string', 'max:2000'],

            // Finishing yang ditawarkan material ini; kosong berarti semuanya.
            'finishings' => ['nullable', 'array'],
            'finishings.*' => ['string', Rule::in(Finishing::keys())],

            'colors.*.key' => ['nullable', 'string', 'max:80'],
            'colors.*.name' => ['required', 'string', 'max:60'],
            'colors.*.hex' => ['required', 'string', 'size:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],

            // Harga material tidak berlaku bagi material dengan Kalkulator
            // Manual: harganya ditetapkan tim per penawaran, jadi tidak ada
            // harga per gram yang dikutip dari sini. Kolomnya tetap ada di basis data dan terisi nol — lihat
            // prepareForValidation() — supaya struktur materialnya sama untuk
            // seluruh teknologi.
            'purchase_price' => [$this->pricesApply() ? 'required' : 'nullable', 'numeric', 'min:0', 'max:'.self::MAX_PRICE],
            'sale_price' => [$this->pricesApply() ? 'required' : 'nullable', 'numeric', 'min:0', 'max:'.self::MAX_PRICE],
            'remark' => ['nullable', 'string', 'max:120'],

            // Metode penentuan harga dimiliki material SLA, MJF, dan SLM:
            // Kalkulator Otomatis (rumus FDM) atau Kalkulator Manual. FDM dan
            // teknologi lain tidak mengirim maupun menyimpannya.
            ...($this->choosesPricing() ? [
                'pricing_method' => ['required', 'string', Rule::in(array_keys(PrintMaterial::PRICING_METHODS))],
            ] : []),
        ];
    }

    /**
     * Pilihan "Tanpa mesin" tersimpan sebagai NULL, bukan string kosong —
     * pengelompokan dan indeks uniknya bergantung pada itu.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('machine_cost_id') && ! $this->filled('machine_cost_id')) {
            $this->merge(['machine_cost_id' => null]);
        }

        /*
         * Daftar warna dirapikan sebelum divalidasi.
         *
         * Formulir selalu membawa satu baris kosong untuk material baru, dan
         * baris yang dihapus meninggalkan lubang pada penomorannya. Keduanya
         * dibereskan di sini: baris yang sama sekali kosong dibuang, sisanya
         * dinomori ulang, dan kode hexanya disamakan menjadi huruf kapital.
         */
        if ($this->has('colors')) {
            $this->merge([
                'colors' => collect((array) $this->input('colors'))
                    ->filter(fn ($row) => is_array($row))
                    ->map(fn (array $row) => [
                        'key' => trim((string) ($row['key'] ?? '')),
                        'name' => trim((string) ($row['name'] ?? '')),
                        'hex' => strtoupper(trim((string) ($row['hex'] ?? ''))),
                    ])
                    ->reject(fn (array $row) => $row['name'] === '' && $row['hex'] === '')
                    ->values()
                    ->all(),
            ]);
        }

        // Material dengan Kalkulator Manual tidak mewajibkan kolom harga,
        // jadi nilainya diisi nol di sini — bukan dibiarkan NULL, karena
        // kolomnya NOT NULL dan dibaca rumus material teknologi lain.
        if (! $this->pricesApply()) {
            $this->merge([
                'purchase_price' => $this->input('purchase_price', 0) ?: 0,
                'sale_price' => $this->input('sale_price', 0) ?: 0,
            ]);
        }
    }

    /**
     * Material ini dijual per gram, jadi harganya wajib diisi: seluruh
     * teknologi selain SLA/MJF/SLM, dan material mereka dengan Kalkulator Otomatis.
     */
    private function pricesApply(): bool
    {
        return ! $this->choosesPricing() || $this->input('pricing_method') === PrintMaterial::PRICING_AUTOMATIC;
    }

    /**
     * Dua warna dengan nama yang sama dalam satu material tidak diterima.
     *
     * Kuncinya dibuat dari namanya (lihat App\Models\PrintMaterialColor), jadi
     * nama kembar akan bertabrakan pada indeks unik basis data. Ditolak di sini
     * supaya yang terbaca pengelola adalah pesan validasi, bukan galat 500.
     */
    private function noDuplicateColorNames(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $keys = collect((array) $value)
                ->pluck('name')
                ->filter()
                ->map(fn ($name) => Str::slug((string) $name) ?: 'warna');

            if ($keys->count() !== $keys->unique()->count()) {
                $fail('Ada dua warna dengan nama yang sama. Tiap warna harus bernama berbeda.');
            }
        };
    }

    private function choosesPricing(): bool
    {
        $technology = $this->route('technology');

        return PricingMethod::appliesTo($technology instanceof PrintTechnology ? $technology->code : null);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'material.required' => 'Nama material wajib diisi.',
            'material.unique' => 'Mesin ini sudah punya material dengan nama tersebut.',
            'machine_cost_id.exists' => 'Mesin yang dipilih tidak ditemukan di Machine Cost.',
            'brand.required' => 'Brand wajib diisi.',
            'colors.*.name.required' => 'Nama Color wajib diisi, atau hapus barisnya.',
            'colors.*.hex.required' => 'Hexa Color wajib diisi, atau hapus barisnya.',
            'colors.*.hex.size' => 'Kode hexa harus terdiri dari 7 karakter (#RRGGBB).',
            'colors.*.hex.regex' => 'Format kode hexa tidak valid. Gunakan format #RRGGBB (contoh: #FFFFFF).',
            'purchase_price.required' => 'Harga Beli wajib diisi.',
            'sale_price.required' => 'Harga Jual wajib diisi.',
            'purchase_price.max' => 'Harga Beli terlalu besar, maksimal Rp9.999.999.999.',
            'sale_price.max' => 'Harga Jual terlalu besar, maksimal Rp9.999.999.999.',
            'purchase_price.min' => 'Harga Beli tidak boleh negatif.',
            'sale_price.min' => 'Harga Jual tidak boleh negatif.',
            'numeric' => 'Kolom ini harus berupa angka.',
            'pricing_method.required' => 'Pilih metode penentuan harga: Kalkulator Otomatis atau Kalkulator Manual.',
            'pricing_method.in' => 'Metode penentuan harga tidak dikenal.',
        ];
    }
}
