@extends('layouts.dashboard')

@section('title', 'Ulasan Google')

@php $canEdit = auth()->user()->can(\App\Support\AdminPermission::REVIEW_EDIT); @endphp

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight text-ink-900">Ulasan Google</h1>
            <p class="mt-1 text-sm text-ink-500">
                Ulasan yang tampil di bagian "Kata Klien" halaman Home. Salin dari profil Google Business Anda; tanggalnya tampil relatif, mis. "2 minggu lalu".
            </p>
        </div>

        @if ($canEdit)
            <a href="{{ staff_route('reviews.create') }}" class="btn-primary">Tambah Ulasan</a>
        @endif
    </div>

    {{-- Tautan profil Google --}}
    <section class="mt-6 rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
        <h2 class="font-display text-base font-bold text-ink-900">Tautan "Lihat semua ulasan di Google"</h2>
        <p class="mt-1 text-sm text-ink-500">
            Tempel tautan ulasan dari Google Business Profile (mis. dari tombol "Bagikan" atau "Dapatkan lebih banyak ulasan").
            Selama kosong, tautan mengarah ke pencarian Google Maps atas nama perusahaan.
        </p>

        @if ($canEdit)
            <form method="POST" action="{{ staff_route('reviews.link') }}" class="mt-4 flex flex-wrap items-start gap-3">
                @csrf
                @method('PATCH')
                <div class="min-w-[260px] flex-1">
                    <label for="google_reviews_url" class="sr-only">URL ulasan Google</label>
                    <input type="url" id="google_reviews_url" name="google_reviews_url" maxlength="500"
                           value="{{ old('google_reviews_url', $profile?->google_reviews_url) }}"
                           placeholder="https://g.page/r/…/review atau https://maps.app.goo.gl/…"
                           class="field-input mt-0">
                    @error('google_reviews_url') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn-primary">Simpan Tautan</button>
            </form>
        @endif

        @if ($profile)
            <p class="mt-3 text-xs text-ink-400">
                Tautan aktif:
                <a href="{{ $profile->google_reviews_link }}" target="_blank" rel="noopener noreferrer" class="break-all font-semibold text-brand-600 hover:text-brand-700">{{ $profile->google_reviews_link }}</a>
            </p>
        @endif
    </section>

    @error('review') <p class="mt-4 field-error">{{ $message }}</p> @enderror

    <section class="mt-6 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead>
                    <tr class="border-b border-ink-100 text-[0.6rem] uppercase tracking-[0.14em] text-ink-400">
                        <th scope="col" class="px-6 py-4 font-bold">Urutan</th>
                        <th scope="col" class="px-6 py-4 font-bold">Pengulas</th>
                        <th scope="col" class="px-6 py-4 font-bold">Bintang</th>
                        <th scope="col" class="px-6 py-4 font-bold">Ulasan</th>
                        <th scope="col" class="px-6 py-4 font-bold">Tanggal</th>
                        <th scope="col" class="px-6 py-4 font-bold">Status</th>
                        @if ($canEdit)
                            <th scope="col" class="px-6 py-4 text-right font-bold">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($reviews as $review)
                        <tr class="align-top">
                            <td class="px-6 py-4 text-ink-500">{{ $review->sort_order }}</td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-ink-900">{{ $review->name }}</p>
                                <p class="text-xs text-ink-400">{{ $review->source ?: 'Google Review' }}</p>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-accent-600">{{ str_repeat('★', $review->rating) }}</td>
                            <td class="px-6 py-4 text-ink-600"><p class="line-clamp-2 max-w-md">{{ $review->quote }}</p></td>
                            <td class="whitespace-nowrap px-6 py-4 text-ink-600">
                                @if ($review->reviewed_at)
                                    {{ $review->reviewed_at->translatedFormat('d M Y') }}
                                    <span class="block text-xs text-ink-400">{{ $review->reviewed_relative }}</span>
                                @else
                                    <span class="text-xs font-semibold text-amber-700">Belum ada tanggal</span>
                                    <span class="block text-xs text-ink-400">tampil: "{{ $review->reviewed_label ?: '—' }}"</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[0.65rem] font-bold {{ $review->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-ink-100 text-ink-600' }}">
                                    {{ $review->is_active ? 'Tampil' : 'Disembunyikan' }}
                                </span>
                            </td>
                            @if ($canEdit)
                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ staff_route('reviews.edit', $review) }}" class="viewer-tool">Ubah</a>
                                        <form method="POST" action="{{ staff_route('reviews.destroy', $review) }}"
                                              data-confirm="Ulasan dari {{ $review->name }} akan dihapus dari website."
                                              data-confirm-title="Hapus Ulasan?"
                                              data-confirm-accept="Ya, Hapus"
                                              data-confirm-cancel="Batal">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="viewer-tool border-brand-200 text-brand-700 hover:border-brand-600 hover:bg-brand-50">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canEdit ? 7 : 6 }}" class="px-6 py-14 text-center text-sm text-ink-400">Belum ada ulasan. Bagian ulasan di Home disembunyikan selama kosong.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
