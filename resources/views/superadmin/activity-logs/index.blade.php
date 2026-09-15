{{--
    Activity Logs — jejak audit seluruh aktivitas penting.

    Halaman baca saja: tidak ada tombol sunting maupun hapus di mana pun.
    Kolomnya mengikuti pertanyaan yang ingin dijawab admin — siapa, apa, kapan,
    terhadap data apa, dan bagaimana hasilnya — sedangkan perbandingan data
    sebelum/sesudah dibuka lewat tombol Detail.
--}}
@extends('layouts.dashboard')

@section('title', 'Activity Logs')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Activity Logs</h2>
            <p class="mt-2 text-sm text-ink-500">
                Riwayat aktivitas penting seluruh akun Personal, Business, dan Admin — beserta data yang berubah pada setiap aktivitas.
            </p>
        </div>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Total Aktivitas', 'value' => $summary['total']],
            ['label' => 'Aktivitas Hari Ini', 'value' => $summary['today']],
            ['label' => 'Gagal Hari Ini', 'value' => $summary['failed_today']],
            ['label' => 'User Aktif Hari Ini', 'value' => $summary['actors_today']],
        ] as $card)
            <div class="rounded-2xl border border-ink-100 bg-white p-5 shadow-card">
                <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $card['label'] }}</p>
                <p class="mt-2 font-display text-2xl font-bold text-ink-900">{{ number_format($card['value'], 0, ',', '.') }}</p>
            </div>
        @endforeach
    </div>

    {{-- Seluruh penyaring dikirim lewat GET agar hasil pencarian dapat
         dibagikan maupun disimpan sebagai bookmark. --}}
    <form method="GET" class="mt-6 rounded-2xl border border-ink-100 bg-white p-5 shadow-card">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div>
                <label for="user" class="field-label">Cari User</label>
                <input type="search" id="user" name="user" value="{{ $filters['user'] }}" placeholder="Nama atau email" class="field-input">
            </div>

            <div>
                <label for="activity" class="field-label">Cari Aktivitas</label>
                <input type="search" id="activity" name="activity" value="{{ $filters['activity'] }}" placeholder="Mis. Upload Model" class="field-input" list="activity-options">
                <datalist id="activity-options">
                    @foreach ($activities as $label)
                        <option value="{{ $label }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div>
                <label for="type" class="field-label">Tipe Akun</label>
                <select id="type" name="type" class="field-input">
                    <option value="">Semua tipe</option>
                    @foreach ($accountTypes as $key => $label)
                        <option value="{{ $key }}" @selected($filters['type'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="module" class="field-label">Module</label>
                <select id="module" name="module" class="field-input">
                    <option value="">Semua module</option>
                    @foreach ($modules as $key => $label)
                        <option value="{{ $key }}" @selected($filters['module'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="status" class="field-label">Status</label>
                <select id="status" name="status" class="field-input">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="from" class="field-label">Tanggal Dari</label>
                <input type="date" id="from" name="from" value="{{ $filters['from'] }}" class="field-input">
            </div>

            <div>
                <label for="to" class="field-label">Tanggal Sampai</label>
                <input type="date" id="to" name="to" value="{{ $filters['to'] }}" class="field-input">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="btn-primary px-6 py-3">Terapkan</button>
                <a href="{{ route('superadmin.activity-logs.index') }}" class="viewer-tool">Reset</a>
            </div>
        </div>
    </form>

    <div class="mt-6 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left text-sm">
                <thead>
                    <tr class="border-b border-ink-100 bg-ink-50/80 text-[0.65rem] uppercase tracking-[0.14em] text-ink-500">
                        <th scope="col" class="px-5 py-4 font-bold">Tanggal &amp; Waktu</th>
                        <th scope="col" class="px-5 py-4 font-bold">User</th>
                        <th scope="col" class="px-5 py-4 font-bold">Tipe Akun</th>
                        <th scope="col" class="px-5 py-4 font-bold">Aktivitas</th>
                        <th scope="col" class="px-5 py-4 font-bold">Module</th>
                        <th scope="col" class="px-5 py-4 font-bold">Deskripsi</th>
                        <th scope="col" class="px-5 py-4 font-bold">Status</th>
                        <th scope="col" class="px-5 py-4 font-bold">IP Address</th>
                        <th scope="col" class="px-5 py-4 text-right font-bold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($logs as $log)
                        <tr class="align-top transition-colors hover:bg-brand-50/40">
                            <td class="whitespace-nowrap px-5 py-4">
                                <span class="font-semibold text-ink-800">{{ $log->created_at->translatedFormat('d M Y') }}</span>
                                <span class="block text-xs text-ink-400">{{ $log->created_at->format('H:i:s') }}</span>
                            </td>

                            <td class="px-5 py-4">
                                {{-- Nama pelaku dibekukan pada barisnya, jadi tetap terbaca
                                     meski akunnya sudah tidak ada. Emailnya hanya ikut bila
                                     akunnya masih terdaftar. --}}
                                <span class="font-semibold text-ink-900">{{ $log->actor_name }}</span>
                                @if ($log->user)
                                    <span class="block text-xs text-ink-400">{{ $log->user->email }}</span>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-[0.1em]',
                                    'bg-ink-900 text-white' => $log->user_type === \App\Support\ActorType::ADMIN,
                                    'bg-brand-50 text-brand-700' => $log->user_type === \App\Support\ActorType::BUSINESS,
                                    'bg-ink-100 text-ink-600' => $log->user_type === \App\Support\ActorType::PERSONAL,
                                ])>{{ $log->user_type_label }}</span>
                            </td>

                            <td class="px-5 py-4 font-semibold text-ink-800">{{ $log->action_label }}</td>

                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-lg bg-ink-100 px-2.5 py-1 text-[0.7rem] font-semibold text-ink-600">
                                    {{ $log->module_label }}
                                </span>
                            </td>

                            <td class="max-w-[22rem] px-5 py-4 text-ink-600">
                                {{ $log->description ?: '-' }}
                                @if ($log->subject_label)
                                    <span class="mt-1 block text-xs font-semibold text-ink-400">{{ $log->subject_label }}</span>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-[0.1em]',
                                    'bg-emerald-50 text-emerald-700' => $log->isSuccess(),
                                    'bg-brand-50 text-brand-700' => ! $log->isSuccess(),
                                ])>{{ $log->status_label }}</span>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-xs text-ink-500">{{ $log->ip_address ?: '-' }}</td>

                            <td class="px-5 py-4">
                                <div class="flex justify-end">
                                    <a href="{{ route('superadmin.activity-logs.show', $log) }}" class="viewer-tool">Detail</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-16 text-center">
                                <p class="font-semibold text-ink-700">Belum ada aktivitas yang cocok.</p>
                                <p class="mt-1.5 text-sm text-ink-400">
                                    Coba longgarkan penyaring, atau tunggu aktivitas berikutnya dari pelanggan maupun admin.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($logs->hasPages())
        <div class="mt-6">{{ $logs->links() }}</div>
    @endif
@endsection
