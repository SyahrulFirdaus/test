{{--
    Langkah Data Perusahaan — hanya pada pendaftaran Business.

    Alamat perusahaan memakai empat dropdown bertingkat yang sama dengan buku
    alamat pelanggan: Provinsi → Kota/Kabupaten → Kecamatan → Kelurahan. Tiap
    tingkat terkunci sampai induknya dipilih, dan mengganti sebuah pilihan
    mengosongkan seluruh tingkat di bawahnya.

    Alamat yang diisi di sini sekaligus menjadi alamat pengiriman utama akun,
    jadi pelanggan tidak perlu mengetiknya lagi di menu Alamat.
--}}
@extends('layouts.auth')

@section('title', 'Data Perusahaan')
@section('panelWidth', 'max-w-2xl')
@section('heading', 'Buat akun baru')
@section('subheading', $step['description'])

@section('form')
    @php
        $value = fn (string $field, $fallback = '') => old($field, $companyData[$field] ?? $fallback);

        // Tiap tingkat: label, nama field, pilihan yang sudah tersedia, tulisan
        // penuntun, dan tulisan saat induknya belum dipilih.
        $levels = [
            ['Provinsi', 'province_id', $provinces, 'Pilih Provinsi', null],
            ['Kota / Kabupaten', 'regency_id', $regencies, 'Pilih Kota/Kabupaten', 'Pilih provinsi terlebih dahulu'],
            ['Kecamatan', 'district_id', $districts, 'Pilih Kecamatan', 'Pilih kota/kabupaten terlebih dahulu'],
            ['Kelurahan / Desa', 'village_id', $villages, 'Pilih Kelurahan/Desa', 'Pilih kecamatan terlebih dahulu'],
        ];
    @endphp

    <div class="mt-7 border-t border-ink-100 pt-7">
        <x-register-progress
            :type-label="$typeLabel"
            :label="$step['label']"
            :number="$step['number']"
            :step-count="$stepCount"
            :progress="$progress" />
    </div>

    <form method="POST" action="{{ route('register.step.store', $stepKey) }}" class="mt-8" data-company-form>
        @csrf

        <div class="grid gap-5 sm:grid-cols-2">

            <div class="sm:col-span-2">
                <label for="company_name" class="field-label">Nama Perusahaan <span class="text-brand-600">*</span></label>
                <input type="text" id="company_name" name="company_name" value="{{ $value('company_name') }}"
                       required maxlength="160" class="field-input" placeholder="PT Contoh Indonesia">
                @error('company_name') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="pic_name" class="field-label">Nama PIC <span class="text-brand-600">*</span></label>
                <input type="text" id="pic_name" name="pic_name" value="{{ $value('pic_name', $account['name'] ?? '') }}"
                       required maxlength="120" class="field-input" placeholder="Penanggung jawab yang kami hubungi">
                @error('pic_name') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="position" class="field-label">Jabatan / Posisi <span class="text-brand-600">*</span></label>
                <input type="text" id="position" name="position" value="{{ $value('position') }}"
                       required maxlength="120" class="field-input" placeholder="Engineering Manager">
                @error('position') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phone" class="field-label">Nomor Telepon Perusahaan <span class="text-brand-600">*</span></label>
                <input type="tel" id="phone" name="phone" value="{{ $value('phone') }}"
                       required maxlength="32" class="field-input" placeholder="022 1234567">
                @error('phone') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="field-label">Email Perusahaan <span class="text-brand-600">*</span></label>
                <input type="email" id="email" name="email" value="{{ $value('email') }}"
                       required maxlength="160" class="field-input" placeholder="procurement@perusahaan.co.id">
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="website" class="field-label">
                    Website Perusahaan <span class="ml-1 normal-case tracking-normal text-ink-300">(opsional)</span>
                </label>
                <input type="url" id="website" name="website" value="{{ $value('website') }}"
                       maxlength="255" class="field-input" placeholder="https://perusahaan.co.id">
                @error('website') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="industry" class="field-label">Industri / Bidang Perusahaan <span class="text-brand-600">*</span></label>
                <select id="industry" name="industry" required class="field-input">
                    <option value="">— Pilih industri —</option>
                    @foreach ($industries as $industry)
                        <option value="{{ $industry }}" @selected($value('industry') === $industry)>{{ $industry }}</option>
                    @endforeach
                </select>
                @error('industry') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="address" class="field-label">Alamat Lengkap <span class="text-brand-600">*</span></label>
                <textarea id="address" name="address" rows="3" required maxlength="500" class="field-input"
                          placeholder="Nama jalan, nomor, gedung, lantai, kawasan industri">{{ $value('address') }}</textarea>
                @error('address') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            {{-- Daftar wilayah belum terisi berarti pemasangannya belum lengkap.
                 Ditandai terang-terangan supaya tidak tampil sebagai dropdown
                 kosong tanpa penjelasan. --}}
            @if ($provinces->isEmpty())
                <div class="sm:col-span-2 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm leading-relaxed text-amber-900">
                    <span class="font-semibold">Daftar wilayah belum tersedia.</span>
                    Hubungi administrator untuk menjalankan <code class="rounded bg-amber-100 px-1">php artisan wilayah:import</code>.
                </div>
            @endif

            {{-- Empat tingkat wilayah --}}
            @foreach ($levels as $index => [$label, $field, $options, $prompt, $lockedPrompt])
                @php $selected = (int) $value($field); @endphp

                <div>
                    <label for="{{ $field }}" class="field-label">{{ $label }} <span class="text-brand-600">*</span></label>

                    <select id="{{ $field }}"
                            name="{{ $field }}"
                            required
                            class="field-input disabled:cursor-not-allowed disabled:bg-ink-50 disabled:text-ink-300"
                            data-region-level="{{ $index }}"
                            data-region-prompt="{{ $prompt }}"
                            @if ($lockedPrompt) data-region-locked="{{ $lockedPrompt }}" @endif
                            @disabled($index > 0 && $options->isEmpty())>
                        <option value="">{{ $index > 0 && $options->isEmpty() ? $lockedPrompt : $prompt }}</option>

                        @foreach ($options as $option)
                            <option value="{{ $option->id }}" @selected($selected === $option->id)>{{ $option->name }}</option>
                        @endforeach
                    </select>

                    @error($field) <p class="field-error">{{ $message }}</p> @enderror
                </div>
            @endforeach

            <div class="sm:col-span-2">
                <label for="postal_code" class="field-label">Kode Pos <span class="text-brand-600">*</span></label>
                <input type="text" id="postal_code" name="postal_code" value="{{ $value('postal_code') }}" required
                       inputmode="numeric" maxlength="12" class="field-input sm:max-w-[12rem]" placeholder="40532">
                @error('postal_code') <p class="field-error">{{ $message }}</p> @enderror
            </div>

        </div>

        <div class="mt-9 flex flex-wrap items-center gap-3 border-t border-ink-100 pt-6">
            <a href="{{ $previousUrl }}" class="btn-outline px-6 py-3">&larr; Kembali</a>
            <button type="submit" class="btn-primary flex-1 px-6 py-3">Lanjutkan &rarr;</button>
        </div>
    </form>
@endsection

@push('scripts')
    <x-region-cascade selector="[data-company-form]" />
@endpush
