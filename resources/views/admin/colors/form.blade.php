@extends('layouts.dashboard')

@php
    $isEdit = $color->exists;
    $hex = strtoupper(old('hex', $color->hex ?? '#B8452F'));
@endphp

@section('title', $isEdit ? 'Ubah Warna' : 'Tambah Warna')

@section('content')
    <a href="{{ staff_route('colors.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600">
        &larr; Kembali ke Color
    </a>

    <h1 class="mt-5 font-display text-2xl font-bold tracking-tight text-ink-900">
        {{ $isEdit ? 'Ubah Warna' : 'Tambah Warna' }}
    </h1>
    <p class="mt-1 text-sm text-ink-500">
        {{ $isEdit
            ? 'Penawaran yang sudah memakai warna ini ikut mengikuti nama dan kode hexa yang baru.'
            : 'Warna yang disimpan di sini langsung menjadi pilihan pelanggan pada Edit Specification.' }}
    </p>

    <form method="POST"
          action="{{ $isEdit ? staff_route('colors.update', $color) : staff_route('colors.store') }}"
          class="mt-6 max-w-2xl rounded-2xl border border-ink-100 bg-white p-6 shadow-card sm:p-7"
          data-color-form>
        @csrf
        @if ($isEdit) @method('PATCH') @endif

        <div>
            <label for="label" class="field-label">Nama Warna</label>
            <input type="text" id="label" name="label" required maxlength="40"
                   value="{{ old('label', $color->label) }}" class="field-input" placeholder="mis. Merah Bata">
            @error('label') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="mt-5">
            <label for="hex" class="field-label">Kode Hexa</label>

            <div class="flex items-center gap-3">
                {{-- Contoh warna mengikuti isi kolom saat itu juga, sehingga
                     pengelola melihat warnanya sebelum menyimpan — bukan setelah
                     warnanya sudah muncul di halaman pelanggan. --}}
                <span class="h-12 w-12 shrink-0 rounded-xl border border-ink-200 shadow-inner"
                      style="background-color: {{ $hex }}"
                      data-color-preview
                      aria-hidden="true"></span>

                <input type="text" id="hex" name="hex" required maxlength="7"
                       value="{{ $hex }}"
                       class="field-input font-mono uppercase"
                       placeholder="#B8452F"
                       spellcheck="false"
                       autocomplete="off"
                       inputmode="text"
                       pattern="#[0-9A-Fa-f]{6}"
                       title="Diawali # lalu 6 digit, contoh #B8452F"
                       data-color-input>

                {{-- Pemilih warna bawaan browser: kolom teksnya tetap yang
                     dikirim, ini hanya cara lain mengisinya. --}}
                <input type="color" value="{{ $hex }}"
                       class="h-12 w-12 shrink-0 cursor-pointer rounded-xl border border-ink-200 bg-white p-1"
                       aria-label="Pilih warna"
                       data-color-picker>
            </div>

            <p class="mt-1.5 text-xs text-ink-400">
                Harus diawali <span class="font-mono font-semibold">#</span> dan berisi 6 digit (0–9, A–F). Contoh: <span class="font-mono">#B8452F</span>.
            </p>
            @error('hex') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="mt-7 flex flex-wrap items-center gap-3">
            <button type="submit" class="btn-primary">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Warna' }}</button>
            <a href="{{ staff_route('colors.index') }}" class="viewer-tool">Batal</a>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    /**
     * Kolom Kode Hexa.
     *
     * Yang boleh masuk hanya "#" diikuti enam digit heksadesimal, jadi karakter
     * lain dibuang saat diketik — bukan baru ditolak setelah dikirim. Tanda
     * pagarnya dipasang sendiri supaya pengelola yang menyalin kode tanpa pagar
     * dari aplikasi desain tidak perlu menambahkannya manual.
     *
     * Contoh warnanya ikut berubah begitu keenam digitnya lengkap; selama belum
     * lengkap, warna terakhir yang sah dibiarkan supaya kotaknya tidak
     * berkedip-kedip saat diketik.
     */
    (function () {
        const form = document.querySelector('[data-color-form]');

        if (!form) {
            return;
        }

        const input = form.querySelector('[data-color-input]');
        const preview = form.querySelector('[data-color-preview]');
        const picker = form.querySelector('[data-color-picker]');

        const normalize = (value) => '#' + value.replace(/[^0-9a-fA-F]/g, '').slice(0, 6).toUpperCase();

        const apply = (hex) => {
            if (hex.length === 7) {
                preview.style.backgroundColor = hex;
                picker.value = hex;
            }
        };

        input.addEventListener('input', () => {
            const cursorAtEnd = input.selectionStart === input.value.length;
            input.value = normalize(input.value);

            if (cursorAtEnd) {
                input.setSelectionRange(input.value.length, input.value.length);
            }

            apply(input.value);
        });

        // Pemilih warna selalu memberi bentuk "#rrggbb" yang sudah sah.
        picker.addEventListener('input', () => {
            input.value = picker.value.toUpperCase();
            preview.style.backgroundColor = input.value;
        });

        apply(normalize(input.value));
    })();
</script>
@endpush
