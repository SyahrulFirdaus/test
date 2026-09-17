@extends('layouts.dashboard')

@section('title', 'Pengaturan Payment Term')

@section('content')
    @php $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.'); @endphp

    <a href="{{ staff_route('payment-terms.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600">
        &larr; Kembali ke Payment Terms
    </a>

    <div class="mt-5">
        <h2 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Pengaturan Payment Term</h2>
        <p class="mt-2 text-sm text-ink-500">
            Menentukan skema pembayaran mana yang ditawarkan ke pelanggan Business dan mulai dari nilai penawaran
            berapa. Aturan ini dibaca langsung oleh halaman pemilihan skema, tidak ada angka yang dikunci di kode.
        </p>
    </div>

    <form method="POST" action="{{ staff_route('payment-terms.settings.update') }}" class="mt-6">
        @csrf
        @method('PATCH')

        <div class="space-y-4">
            @foreach ($settings as $setting)
                <div class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <label class="flex items-center gap-3">
                            <input type="checkbox"
                                   name="settings[{{ $setting->id }}][enabled]"
                                   value="1"
                                   @checked(old('settings.'.$setting->id.'.enabled', $setting->enabled))
                                   class="h-5 w-5 accent-brand-600">
                            <span class="font-display text-base font-bold text-ink-900">{{ $setting->label }}</span>
                        </label>

                        <span class="rounded-full bg-ink-100 px-3 py-1 text-xs font-bold text-ink-600">
                            {{ $setting->enabled ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>

                    <div class="mt-5 max-w-md">
                        <label class="field-label">
                            Minimum Nilai Penawaran
                            @if ($setting->installment_count > 1)
                                untuk {{ $setting->installment_count }}x
                            @endif
                        </label>

                        <input type="number"
                               step="1"
                               min="0"
                               name="settings[{{ $setting->id }}][minimum_amount]"
                               value="{{ old('settings.'.$setting->id.'.minimum_amount', (int) $setting->minimum_amount) }}"
                               class="field-input">

                        <p class="mt-2 text-xs text-ink-400">
                            Sekarang {{ $rupiah($setting->minimum_amount) }}. Skema ini ditawarkan bila total
                            penawaran sama dengan atau lebih besar dari angka tersebut.
                        </p>

                        @error('settings.'.$setting->id.'.minimum_amount')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 rounded-2xl border border-ink-100 bg-ink-50/70 p-5">
            <p class="text-sm leading-relaxed text-ink-600">
                Perubahan di sini hanya berlaku untuk pemilihan skema berikutnya. Payment term yang sudah disetujui
                beserta jadwal terminnya tidak ikut berubah.
            </p>
        </div>

        <button type="submit" class="btn-primary mt-6 w-full sm:w-auto sm:px-8">Simpan Pengaturan</button>
    </form>
@endsection
