@extends('layouts.dashboard')

@section('title', 'Akun Admin')

@section('content')
    @php
        $angka = fn ($value) => number_format((int) $value, 0, ',', '.');
    @endphp

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight text-ink-900">Akun Admin</h1>
            <p class="mt-1 text-sm text-ink-500">
                Akun yang dibuat di sini langsung dapat dipakai masuk ke Dashboard Admin.
            </p>
        </div>

        <a href="{{ route('superadmin.admins.create') }}" class="btn-primary">Tambah Admin</a>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        @foreach ([
            'Total Admin' => $summary['total'],
            'Aktif' => $summary['active'],
            'Nonaktif' => $summary['inactive'],
        ] as $label => $value)
            <div class="rounded-2xl border border-ink-100 bg-white p-5 shadow-card">
                <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $label }}</p>
                <p class="mt-2 font-display text-2xl font-bold text-ink-900">{{ $angka($value) }}</p>
            </div>
        @endforeach
    </div>

    <section class="mt-6 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-ink-100 px-6 py-5">
            <div class="min-w-[220px] flex-1">
                <label for="q" class="field-label">Cari</label>
                <input type="search" id="q" name="q" value="{{ $filters['q'] }}" class="field-input"
                       placeholder="Nama atau email admin">
            </div>

            <button type="submit" class="btn-primary">Cari</button>
            <a href="{{ route('superadmin.admins.index') }}" class="viewer-tool">Reset</a>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[620px] text-left text-sm">
                <thead>
                    <tr class="border-b border-ink-100 text-[0.6rem] uppercase tracking-[0.14em] text-ink-400">
                        <th scope="col" class="px-6 py-4 font-bold">Nama</th>
                        <th scope="col" class="px-6 py-4 font-bold">Email</th>
                        <th scope="col" class="px-6 py-4 font-bold">Status</th>
                        <th scope="col" class="px-6 py-4 text-right font-bold">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-ink-100">
                    @forelse ($admins as $admin)
                        <tr>
                            <td class="px-6 py-4 font-semibold text-ink-900">{{ $admin->name }}</td>
                            <td class="px-6 py-4 text-ink-600">{{ $admin->email }}</td>
                            <td class="px-6 py-4">
                                <span @class([
                                    'inline-flex rounded-full px-3 py-1 text-[0.65rem] font-bold uppercase tracking-[0.12em]',
                                    'bg-emerald-100 text-emerald-800' => $admin->isActive(),
                                    'bg-ink-100 text-ink-500' => ! $admin->isActive(),
                                ])>
                                    {{ $admin->isActive() ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('superadmin.admins.edit', $admin) }}" class="viewer-tool">Edit</a>

                                    {{-- Penghapusan dikonfirmasi lebih dulu: akunnya hilang
                                         permanen, hanya jejak Activity Log-nya yang tertinggal. --}}
                                    <form method="POST" action="{{ route('superadmin.admins.destroy', $admin) }}"
                                          onsubmit="return confirm('Hapus akun admin {{ $admin->name }} ({{ $admin->email }})? Akun ini tidak akan dapat masuk lagi dan tindakan ini tidak dapat dibatalkan.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="viewer-tool border-brand-200 text-brand-700 hover:border-brand-600 hover:bg-brand-50">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-sm text-ink-400">
                                Belum ada akun admin.
                                <a href="{{ route('superadmin.admins.create') }}" class="font-semibold text-brand-600 hover:text-brand-700">Tambah Admin</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($admins->hasPages())
            <div class="border-t border-ink-100 px-6 py-4">{{ $admins->links() }}</div>
        @endif
    </section>
@endsection
