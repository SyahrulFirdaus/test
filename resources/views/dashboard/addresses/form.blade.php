{{--
    Formulir tambah/ubah alamat pengiriman.

    Wilayah diisi lewat empat dropdown bertingkat:

        Provinsi -> Kota/Kabupaten -> Kecamatan -> Kelurahan

    Tiap tingkat terkunci sampai induknya dipilih, dan mengganti sebuah pilihan
    mengosongkan seluruh tingkat di bawahnya. Isinya diambil menyusul dari
    endpoint wilayah karena daftar kelurahan se-Indonesia berjumlah puluhan ribu
    baris — terlalu besar untuk dikirim bersama halaman.

    Relasi antar-tingkat tetap diperiksa ulang di server, jadi kiriman yang
    disusun sendiri tanpa melewati halaman ini pun tidak dapat menyimpan
    gabungan wilayah yang mustahil.
--}}
@extends('layouts.dashboard')

@php $isEdit = $address->exists; @endphp

@section('title', $isEdit ? 'Ubah Alamat' : 'Tambah Alamat')

@section('content')
    @php
        $value = fn (string $field, $fallback = '') => old($field, $address->{$field} ?? $fallback);

        // Tiap tingkat: label, nama field, daftar pilihan yang sudah tersedia,
        // dan tulisan penuntun saat induknya belum dipilih.
        $levels = [
            ['Provinsi', 'province_id', $provinces, 'Pilih Provinsi', null],
            ['Kota / Kabupaten', 'regency_id', $regencies, 'Pilih Kota/Kabupaten', 'Pilih provinsi terlebih dahulu'],
            ['Kecamatan', 'district_id', $districts, 'Pilih Kecamatan', 'Pilih kota/kabupaten terlebih dahulu'],
            ['Kelurahan / Desa', 'village_id', $villages, 'Pilih Kelurahan/Desa', 'Pilih kecamatan terlebih dahulu'],
        ];
    @endphp

    <a href="{{ route('dashboard.addresses.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600">
        &larr; Kembali ke daftar alamat
    </a>

    <div class="mt-5 max-w-3xl">
        <h2 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">
            {{ $isEdit ? 'Ubah Alamat' : 'Tambah Alamat' }}
        </h2>
        <p class="mt-2 text-sm text-ink-500">Seluruh bagian alamat wajib diisi agar paket dapat dikirim tanpa salah tujuan.</p>

        <form method="POST"
              action="{{ $isEdit ? route('dashboard.addresses.update', $address) : route('dashboard.addresses.store') }}"
              class="mt-8 rounded-2xl border border-ink-100 bg-white p-6 shadow-card sm:p-8"
              data-address-form>
            @csrf
            @if ($isEdit) @method('PATCH') @endif

            <div class="grid gap-5 sm:grid-cols-2">

                <div class="sm:col-span-2">
                    <label for="label" class="field-label">Nama Alamat <span class="text-brand-600">*</span></label>
                    <input type="text" id="label" name="label" value="{{ $value('label') }}" required maxlength="60"
                           class="field-input" placeholder="Rumah, Kantor, Gudang…">
                    @error('label') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="recipient_name" class="field-label">Nama Penerima <span class="text-brand-600">*</span></label>
                    <input type="text" id="recipient_name" name="recipient_name" value="{{ $value('recipient_name', auth()->user()->name) }}"
                           required maxlength="120" class="field-input">
                    @error('recipient_name') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="recipient_phone" class="field-label">Nomor Telepon Penerima <span class="text-brand-600">*</span></label>
                    <input type="tel" id="recipient_phone" name="recipient_phone" value="{{ $value('recipient_phone', auth()->user()->phone) }}"
                           required maxlength="32" class="field-input" placeholder="0812 3456 7890">
                    @error('recipient_phone') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                {{-- Daftar wilayah belum terisi berarti pemasangannya belum
                     lengkap; ditandai terang-terangan agar tidak tampil sebagai
                     dropdown kosong tanpa penjelasan. --}}
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
                                @if ($index > 0) data-region-parent="{{ $levels[$index - 1][1] }}" @endif
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

                <div>
                    <label for="postal_code" class="field-label">Kode Pos <span class="text-brand-600">*</span></label>
                    <input type="text" id="postal_code" name="postal_code" value="{{ $value('postal_code') }}" required
                           inputmode="numeric" maxlength="12" class="field-input" placeholder="40532">
                    @error('postal_code') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="detail" class="field-label">Alamat Lengkap <span class="text-brand-600">*</span></label>
                    <textarea id="detail" name="detail" rows="3" required maxlength="500" class="field-input"
                              placeholder="Nama jalan, nomor rumah, RT/RW, nama gedung, lantai">{{ $value('detail') }}</textarea>
                    @error('detail') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="note" class="field-label">Catatan untuk Kurir <span class="text-ink-300">(opsional)</span></label>
                    <input type="text" id="note" name="note" value="{{ $value('note') }}" maxlength="255"
                           class="field-input" placeholder="Patokan, jam terima paket, atau titip ke satpam">
                    @error('note') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                @if (! $address->is_default)
                    <div class="sm:col-span-2">
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-ink-200 px-4 py-3 transition-colors hover:border-brand-300">
                            <input type="checkbox" name="is_default" value="1" @checked(old('is_default'))
                                   class="mt-0.5 h-4 w-4 rounded border-ink-300 text-brand-600 focus:ring-brand-600">
                            <span class="text-sm text-ink-700">
                                <span class="font-semibold text-ink-900">Jadikan alamat utama</span><br>
                                <span class="text-ink-500">Alamat utama terpilih lebih dulu saat Anda meminta penawaran.</span>
                            </span>
                        </label>
                    </div>
                @endif

            </div>

            <div class="mt-7 flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('dashboard.addresses.index') }}" class="btn-outline">Batal</a>
                <button type="submit" class="btn-primary">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Alamat' }}</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <x-region-cascade selector="[data-address-form]" />
@endpush
