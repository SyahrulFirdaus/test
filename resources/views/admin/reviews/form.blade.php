@extends('layouts.dashboard')

@php $isEdit = $review->exists; @endphp

@section('title', $isEdit ? 'Ubah Ulasan' : 'Tambah Ulasan')

@section('content')
    <a href="{{ staff_route('reviews.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600">
        Kembali ke Ulasan Google
    </a>

    <h1 class="mt-5 font-display text-2xl font-bold tracking-tight text-ink-900">{{ $isEdit ? 'Ubah Ulasan' : 'Tambah Ulasan' }}</h1>
    <p class="mt-1 text-sm text-ink-500">Salin nama, bintang, isi, dan tanggal ulasan apa adanya dari Google.</p>

    <form method="POST"
          action="{{ $isEdit ? staff_route('reviews.update', $review) : staff_route('reviews.store') }}"
          class="mt-6 max-w-2xl space-y-5 rounded-2xl border border-ink-100 bg-white p-6 shadow-card sm:p-7">
        @csrf
        @if ($isEdit) @method('PATCH') @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="name" class="field-label">Nama Pengulas</label>
                <input type="text" id="name" name="name" required maxlength="120" value="{{ old('name', $review->name) }}" class="field-input">
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="role" class="field-label">Jabatan/Perusahaan <span class="font-normal normal-case text-ink-400">(opsional)</span></label>
                <input type="text" id="role" name="role" maxlength="120" value="{{ old('role', $review->role) }}" class="field-input">
                @error('role') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="rating" class="field-label">Bintang</label>
                <select id="rating" name="rating" class="field-input">
                    @for ($i = 5; $i >= 1; $i--)
                        <option value="{{ $i }}" @selected((int) old('rating', $review->rating) === $i)>{{ $i }} ★</option>
                    @endfor
                </select>
                @error('rating') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="reviewed_at" class="field-label">Tanggal Ulasan</label>
                <input type="date" id="reviewed_at" name="reviewed_at" required max="{{ now()->toDateString() }}"
                       value="{{ old('reviewed_at', $review->reviewed_at?->toDateString()) }}" class="field-input">
                <p class="mt-1 text-xs text-ink-400">Tampil relatif di website, mis. "2 minggu lalu".</p>
                @error('reviewed_at') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="quote" class="field-label">Isi Ulasan</label>
            <textarea id="quote" name="quote" rows="5" required maxlength="3000" class="field-input">{{ old('quote', $review->quote) }}</textarea>
            @error('quote') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="source" class="field-label">Sumber</label>
                <input type="text" id="source" name="source" maxlength="60" value="{{ old('source', $review->source ?: 'Google Review') }}" class="field-input">
                @error('source') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="sort_order" class="field-label">Urutan Tampil</label>
                <input type="number" id="sort_order" name="sort_order" min="0" max="9999" value="{{ old('sort_order', $review->sort_order) }}" class="field-input" placeholder="Otomatis di akhir">
                @error('sort_order') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <label class="flex items-center gap-3 text-sm font-semibold text-ink-700">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-ink-300 text-brand-600 focus:ring-brand-600" @checked(old('is_active', $review->is_active))>
            Tampilkan di halaman Home
        </label>

        <div class="flex flex-wrap items-center gap-3 pt-2">
            <button type="submit" class="btn-primary">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Ulasan' }}</button>
            <a href="{{ staff_route('reviews.index') }}" class="viewer-tool">Batal</a>
        </div>
    </form>
@endsection
