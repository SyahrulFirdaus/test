@extends('layouts.dashboard')

@section('title', 'Detail User')

@section('content')
    @php $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.'); @endphp

    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600">
        &larr; Kembali ke daftar user
    </a>

    <div class="mt-5 grid gap-6 lg:grid-cols-12">

        {{-- Profil --}}
        <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card lg:col-span-4">
            <div class="flex items-center gap-4">
                <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-brand-600 font-display text-lg font-bold text-white">
                    {{ $user->initials }}
                </span>
                <div class="min-w-0">
                    <h2 class="truncate font-display text-lg font-bold text-ink-900">{{ $user->name }}</h2>
                    <p class="truncate text-sm text-ink-500">{{ $user->email }}</p>
                </div>
            </div>

            <dl class="mt-6 space-y-4">
                @foreach ([
                    'Nomor Telepon' => $user->phone ?: '—',
                    'Kota Asal' => $user->city ?: '—',
                    'Kode Pos' => $user->postal_code ?: '—',
                    'Alamat Lengkap' => $user->address ?: '—',
                    'Terdaftar Sejak' => $user->created_at->translatedFormat('d F Y'),
                ] as $label => $value)
                    <div>
                        <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $label }}</dt>
                        <dd class="mt-1 whitespace-pre-line break-words text-sm font-semibold text-ink-800">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>

            @if ($user->whatsapp_link)
                <a href="{{ $user->whatsapp_link }}" target="_blank" rel="noopener noreferrer"
                   class="btn-primary mt-6 w-full bg-emerald-600 shadow-[0_10px_30px_-12px_rgba(5,150,105,0.9)] hover:bg-emerald-700">
                    <x-icons.whatsapp class="h-4 w-4" />
                    Hubungi via WhatsApp
                </a>
            @endif
        </section>

        {{-- Riwayat penawaran --}}
        <div class="space-y-6 lg:col-span-8">
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach ([
                    ['label' => 'Total Penawaran', 'value' => number_format($summary['total'], 0, ',', '.')],
                    ['label' => 'Pesanan Selesai', 'value' => number_format($summary['completed'], 0, ',', '.')],
                    ['label' => 'Nilai Estimasi', 'value' => $rupiah($summary['value'])],
                ] as $card)
                    <div class="rounded-2xl border border-ink-100 bg-white p-5 shadow-card">
                        <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $card['label'] }}</p>
                        <p class="mt-2 font-display text-xl font-bold text-ink-900">{{ $card['value'] }}</p>
                    </div>
                @endforeach
            </div>

            <section class="overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
                <div class="border-b border-ink-100 px-6 py-5">
                    <h2 class="font-display text-base font-bold text-ink-900">Riwayat Penawaran</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-ink-100 bg-ink-50/80 text-[0.65rem] uppercase tracking-[0.14em] text-ink-500">
                                <th scope="col" class="px-6 py-3 font-bold">Nomor</th>
                                <th scope="col" class="px-6 py-3 font-bold">Tanggal</th>
                                <th scope="col" class="px-6 py-3 font-bold">File</th>
                                <th scope="col" class="px-6 py-3 font-bold">Nilai</th>
                                <th scope="col" class="px-6 py-3 font-bold">Status</th>
                                <th scope="col" class="px-6 py-3 text-right font-bold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($quotations as $quotation)
                                <tr class="transition-colors hover:bg-brand-50/40">
                                    <td class="px-6 py-3 font-mono text-xs font-semibold text-brand-600">{{ $quotation->tracking_number }}</td>
                                    <td class="px-6 py-3 text-xs text-ink-500">{{ $quotation->created_at->translatedFormat('d M Y') }}</td>
                                    <td class="px-6 py-3 text-ink-600">{{ $quotation->items_count }} file</td>
                                    <td class="px-6 py-3 font-semibold text-ink-800">{{ $rupiah($quotation->display_price) }}</td>
                                    <td class="px-6 py-3">
                                        <span class="rounded-full border border-ink-200 bg-ink-50 px-3 py-1 text-xs font-semibold text-ink-600">
                                            {{ $quotation->status_label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-right">
                                        <a href="{{ route('admin.quotations.show', $quotation) }}" class="viewer-tool">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-sm text-ink-400">
                                        User ini belum pernah membuat penawaran.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            @if ($quotations->hasPages())
                <div>{{ $quotations->links() }}</div>
            @endif
        </div>
    </div>
@endsection
