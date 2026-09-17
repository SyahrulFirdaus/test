@extends('layouts.dashboard')

@section('title', 'Alamat')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Alamat Pengiriman</h2>
            <p class="mt-2 text-sm text-ink-500">
                Simpan beberapa alamat sekaligus (misalnya rumah dan kantor), lalu pilih salah satunya
                saat meminta penawaran. Alamat utama terpilih lebih dulu.
            </p>
        </div>

        <a href="{{ route('dashboard.addresses.create') }}" class="btn-primary">Tambah Alamat</a>
    </div>

    @if (session('status'))
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    {{-- Alamat hasil pemindahan akun lama belum memuat kecamatan dan kelurahan,
         karena keduanya memang belum pernah ditanyakan sebelumnya. --}}
    @if ($incomplete > 0)
        <div class="mt-6 flex items-start gap-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <x-icons.alert class="mt-0.5 h-4 w-4 shrink-0" />
            <p>
                <span class="font-semibold">{{ $incomplete }} alamat belum lengkap.</span>
                Lengkapi provinsi, kota, kecamatan, dan kelurahannya agar pengiriman tidak salah tujuan.
            </p>
        </div>
    @endif

    @forelse ($addresses as $address)
        <article @class([
            'mt-6 rounded-2xl border bg-white p-6 shadow-card',
            'border-brand-300' => $address->is_default,
            'border-ink-100' => ! $address->is_default,
        ])>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="font-display text-base font-bold text-ink-900">{{ $address->label }}</h3>

                        @if ($address->is_default)
                            <span class="rounded-full bg-brand-600 px-2.5 py-1 text-[0.6rem] font-bold uppercase tracking-[0.1em] text-white">Utama</span>
                        @endif

                        @unless ($address->isComplete())
                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[0.6rem] font-bold uppercase tracking-[0.1em] text-amber-800">Belum Lengkap</span>
                        @endunless
                    </div>

                    <p class="mt-2 text-sm font-semibold text-ink-800">
                        {{ $address->recipient_name }}
                        <span class="font-normal text-ink-400">&middot;</span>
                        <span class="font-normal text-ink-500">{{ $address->recipient_phone }}</span>
                    </p>

                    <p class="mt-1 text-sm leading-relaxed text-ink-600">{{ $address->detail }}</p>
                    <p class="mt-0.5 text-sm leading-relaxed text-ink-500">{{ $address->region_line ?: '-' }}</p>

                    @if ($address->note)
                        <p class="mt-2 text-xs text-ink-400">Catatan: {{ $address->note }}</p>
                    @endif
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    @unless ($address->is_default)
                        <form method="POST" action="{{ route('dashboard.addresses.default', $address) }}">
                            @csrf
                            <button type="submit" class="viewer-tool">Jadikan Utama</button>
                        </form>
                    @endunless

                    <a href="{{ route('dashboard.addresses.edit', $address) }}" class="viewer-tool">Ubah</a>

                    {{-- Alamat utama tidak dapat dihapus selama masih ada alamat
                         lain: menghapusnya membuat akun kehilangan alamat yang
                         terpilih otomatis pada formulir penawaran. --}}
                    @if (! $address->is_default || $addresses->count() === 1)
                        <form method="POST" action="{{ route('dashboard.addresses.destroy', $address) }}"
                              onsubmit="return confirm('Hapus alamat {{ $address->label }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="viewer-tool text-brand-600">Hapus</button>
                        </form>
                    @endif
                </div>
            </div>
        </article>
    @empty
        <div class="mt-6 rounded-2xl border border-dashed border-ink-200 bg-white px-6 py-16 text-center shadow-card">
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-600/10 text-brand-600">
                <x-icons.map-pin class="h-6 w-6" />
            </span>
            <p class="mt-4 font-semibold text-ink-700">Belum ada alamat tersimpan.</p>
            <p class="mx-auto mt-1.5 max-w-md text-sm text-ink-400">
                Tambahkan alamat pengiriman agar tidak perlu mengetiknya lagi setiap kali meminta penawaran.
            </p>
            <a href="{{ route('dashboard.addresses.create') }}" class="btn-primary mt-6">Tambah Alamat Pertama</a>
        </div>
    @endforelse
@endsection
