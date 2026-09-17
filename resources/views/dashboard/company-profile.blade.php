{{--
    Informasi Perusahaan — hanya untuk akun Business.

    Nama perusahaan yang tampil terkunci pada modal "Minta Penawaran" berasal
    dari halaman ini, jadi inilah satu-satunya tempat mengubahnya. Susunan
    kolomnya sengaja sama dengan langkah "Data Perusahaan" pada pendaftaran,
    termasuk empat dropdown wilayah bertingkat, supaya keduanya konsisten dan
    memakai aturan validasi yang sama.
--}}
@extends('layouts.dashboard')

@section('title', 'Informasi Perusahaan')

@section('content')
    @php
        $value = fn (string $field, $fallback = '') => old($field, $profile?->{$field} ?? $fallback);

        $levels = [
            ['Provinsi', 'province_id', $provinces, 'Pilih Provinsi', null],
            ['Kota / Kabupaten', 'regency_id', $regencies, 'Pilih Kota/Kabupaten', 'Pilih provinsi terlebih dahulu'],
            ['Kecamatan', 'district_id', $districts, 'Pilih Kecamatan', 'Pilih kota/kabupaten terlebih dahulu'],
            ['Kelurahan / Desa', 'village_id', $villages, 'Pilih Kelurahan/Desa', 'Pilih kecamatan terlebih dahulu'],
        ];
    @endphp

    <div class="max-w-3xl">
        <h2 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Informasi Perusahaan</h2>
        <p class="mt-2 text-sm leading-relaxed text-ink-500">
            Data ini dipakai pada setiap permintaan penawaran Anda. Nama perusahaan pada modal
            &ldquo;Minta Penawaran&rdquo; terisi otomatis dari sini, sehingga tidak perlu diketik ulang.
        </p>

        @if ($profile?->segment)
            <div class="mt-5 inline-flex items-center gap-2 rounded-xl border border-brand-200 bg-brand-50 px-4 py-2.5">
                <span class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-brand-800/60">Customer Segment</span>
                <span class="text-sm font-bold text-brand-800">{{ $profile->segment }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('dashboard.company-profile.update') }}" class="mt-7" data-company-form>
            @csrf
            @method('PATCH')

            <div class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
                <div class="grid gap-5 sm:grid-cols-2">

                    <div class="sm:col-span-2">
                        <label for="company_name" class="field-label">Nama Perusahaan <span class="text-brand-600">*</span></label>
                        <input type="text" id="company_name" name="company_name" value="{{ $value('company_name') }}"
                               required maxlength="160" class="field-input" placeholder="PT Contoh Sejahtera">
                        @error('company_name') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="pic_name" class="field-label">Nama PIC <span class="text-brand-600">*</span></label>
                        <input type="text" id="pic_name" name="pic_name" value="{{ $value('pic_name') }}"
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
                            <option value="">Pilih industri</option>
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

                    @if ($provinces->isEmpty())
                        <div class="sm:col-span-2 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm leading-relaxed text-amber-900">
                            <span class="font-semibold">Daftar wilayah belum tersedia.</span>
                            Hubungi administrator untuk menjalankan <code class="rounded bg-amber-100 px-1">php artisan wilayah:import</code>.
                        </div>
                    @endif

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

                <div class="mt-7 flex flex-wrap items-center gap-3 border-t border-ink-100 pt-6">
                    <button type="submit" class="btn-primary px-6 py-3">Simpan Informasi Perusahaan</button>
                    <a href="{{ route('dashboard') }}" class="btn-outline px-6 py-3">Kembali ke Dashboard</a>
                </div>
            </div>
        </form>

        <p class="mt-5 text-xs leading-relaxed text-ink-400">
            Data akun pribadi Anda (nama, email, dan nomor WhatsApp) diubah di
            <a href="{{ route('dashboard.profile.edit') }}" class="font-semibold text-brand-600 hover:text-brand-700">halaman Profil</a>,
            sedangkan alamat pengiriman diatur di
            <a href="{{ route('dashboard.addresses.index') }}" class="font-semibold text-brand-600 hover:text-brand-700">menu Alamat</a>.
        </p>
    </div>
@endsection

@push('scripts')
    <x-region-cascade selector="[data-company-form]" />
@endpush
