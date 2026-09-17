@extends('layouts.dashboard')

@section('title', 'Manajemen User')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Manajemen User</h2>
            <p class="mt-2 text-sm text-ink-500">Seluruh akun pelanggan yang terdaftar beserta jumlah penawarannya.</p>
        </div>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-3 xl:grid-cols-5">
        @foreach ([
            ['label' => 'Total User', 'value' => $summary['total']],
            ['label' => 'Personal', 'value' => $summary['personal']],
            ['label' => 'Business', 'value' => $summary['business']],
            ['label' => 'Pernah Membuat Penawaran', 'value' => $summary['with_quotations']],
            ['label' => 'Daftar Bulan Ini', 'value' => $summary['new_this_month']],
        ] as $card)
            <div class="rounded-2xl border border-ink-100 bg-white p-5 shadow-card">
                <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $card['label'] }}</p>
                <p class="mt-2 font-display text-2xl font-bold text-ink-900">{{ number_format($card['value'], 0, ',', '.') }}</p>
            </div>
        @endforeach
    </div>

    <form method="GET" class="mt-6 flex flex-wrap gap-3 rounded-2xl border border-ink-100 bg-white p-5 shadow-card">
        <div class="min-w-[240px] flex-1">
            <label for="q" class="field-label">Cari</label>
            <input type="search" id="q" name="q" value="{{ $filters['q'] }}" placeholder="Nama, email, telepon, atau kota" class="field-input">
        </div>
        <div class="min-w-[180px]">
            <label for="type" class="field-label">Tipe Customer</label>
            <select id="type" name="type" class="field-input">
                <option value="">Semua tipe</option>
                @foreach ($customerTypes as $key => $label)
                    <option value="{{ $key }}" @selected($filters['type'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="btn-primary px-6 py-3">Cari</button>
        </div>
    </form>

    <div class="mt-6 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px] text-left text-sm">
                <thead>
                    <tr class="border-b border-ink-100 bg-ink-50/80 text-[0.65rem] uppercase tracking-[0.14em] text-ink-500">
                        <th scope="col" class="px-5 py-4 font-bold">Nama</th>
                        <th scope="col" class="px-5 py-4 font-bold">Tipe Customer</th>
                        <th scope="col" class="px-5 py-4 font-bold">Kontak</th>
                        <th scope="col" class="px-5 py-4 font-bold">Kota</th>
                        <th scope="col" class="px-5 py-4 font-bold">Penawaran</th>
                        <th scope="col" class="px-5 py-4 font-bold">Terdaftar</th>
                        <th scope="col" class="px-5 py-4 text-right font-bold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($users as $user)
                        <tr class="transition-colors hover:bg-brand-50/40">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-xs font-bold text-white">
                                        {{ $user->initials }}
                                    </span>
                                    <span class="font-semibold text-ink-900">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-[0.1em]',
                                    'bg-brand-50 text-brand-700' => $user->isBusiness(),
                                    'bg-ink-100 text-ink-600' => ! $user->isBusiness(),
                                ])>{{ $user->customer_type_label }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <a href="mailto:{{ $user->email }}" class="block text-ink-700 transition-colors hover:text-brand-600">{{ $user->email }}</a>
                                <span class="text-xs text-ink-400">{{ $user->phone ?: '-' }}</span>
                            </td>
                            <td class="px-5 py-4 text-ink-600">
                                {{ $user->city ?: '-' }}
                                <span class="block text-xs text-ink-400">{{ $user->postal_code ?: '' }}</span>
                            </td>
                            <td class="px-5 py-4 font-semibold text-ink-800">{{ $user->quotation_requests_count }}</td>
                            <td class="px-5 py-4 text-xs text-ink-500">{{ $user->created_at->translatedFormat('d M Y') }}</td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    @if ($user->whatsapp_link)
                                        <a href="{{ $user->whatsapp_link }}" target="_blank" rel="noopener noreferrer" class="viewer-tool">WhatsApp</a>
                                    @endif
                                    <a href="{{ staff_route('users.show', $user) }}" class="viewer-tool">Detail</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <p class="font-semibold text-ink-700">Belum ada user terdaftar.</p>
                                <p class="mt-1.5 text-sm text-ink-400">Akun akan muncul di sini setelah pengunjung mendaftar dari halaman Register.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($users->hasPages())
        <div class="mt-6">{{ $users->links() }}</div>
    @endif
@endsection
