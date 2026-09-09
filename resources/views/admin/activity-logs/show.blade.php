{{--
    Rincian satu aktivitas.

    Bagian terpentingnya adalah perbandingan Before/After di bawah: kolom yang
    berubah disorot, sedangkan kolom lain tetap ditampilkan sebagai konteks.
--}}
@extends('layouts.dashboard')

@section('title', 'Detail Aktivitas')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-[0.65rem] font-semibold uppercase tracking-[0.18em] text-brand-600">Activity Detail</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">{{ $log->action_label }}</h2>
            <p class="mt-2 text-sm text-ink-500">
                {{ $log->description ?: 'Tidak ada keterangan tambahan untuk aktivitas ini.' }}
            </p>
        </div>

        <a href="{{ url()->previous(route('admin.activity-logs.index')) }}" class="viewer-tool">Kembali</a>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        {{-- ---------------------------------------------- identitas aktivitas --}}
        <div class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card lg:col-span-2">
            <h3 class="font-display text-base font-bold text-ink-900">Informasi Aktivitas</h3>

            <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                @php
                    $rows = [
                        ['label' => 'User', 'value' => $log->actor_name],
                        ['label' => 'Account Type', 'value' => $log->user_type_long_label],
                        ['label' => 'Activity', 'value' => $log->action_label],
                        ['label' => 'Module', 'value' => $log->module_label],
                        ['label' => 'Date & Time', 'value' => $log->created_at->translatedFormat('d F Y, H:i:s')],
                        ['label' => 'Email', 'value' => $log->user?->email ?: '-'],
                        ['label' => 'IP Address', 'value' => $log->ip_address ?: '-'],
                        ['label' => 'Device', 'value' => $log->device],
                    ];
                @endphp

                @foreach ($rows as $row)
                    <div>
                        <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $row['label'] }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink-800">{{ $row['value'] }}</dd>
                    </div>
                @endforeach

                @if ($log->subject_label)
                    <div class="sm:col-span-2">
                        <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Data Terkait</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink-800">
                            {{ $log->subject_label }}
                            @if ($log->subject_type)
                                <span class="ml-1 text-xs font-normal text-ink-400">
                                    ({{ class_basename($log->subject_type) }}{{ $log->subject_id ? ' #'.$log->subject_id : '' }})
                                </span>
                            @endif
                        </dd>
                    </div>
                @endif
            </dl>
        </div>

        {{-- ------------------------------------------------------------ status --}}
        <div class="space-y-6">
            <div class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
                <h3 class="font-display text-base font-bold text-ink-900">Status</h3>

                <span @class([
                    'mt-4 inline-flex rounded-full px-3.5 py-1.5 text-xs font-bold uppercase tracking-[0.1em]',
                    'bg-emerald-50 text-emerald-700' => $log->isSuccess(),
                    'bg-brand-50 text-brand-700' => ! $log->isSuccess(),
                ])>{{ $log->status_label }}</span>

                <p class="mt-4 text-xs leading-relaxed text-ink-500">
                    Dicatat {{ $log->created_at->diffForHumans() }}.
                </p>
            </div>

            <div class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
                <h3 class="font-display text-base font-bold text-ink-900">User Agent</h3>
                <p class="mt-3 break-words text-xs leading-relaxed text-ink-500">{{ $log->user_agent ?: '-' }}</p>
            </div>
        </div>
    </div>

    {{-- --------------------------------------------- perbandingan before/after --}}
    <div class="mt-6 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
        <div class="border-b border-ink-100 bg-ink-50/70 px-6 py-4">
            <h3 class="font-display text-base font-bold text-ink-900">Perubahan Data</h3>
            <p class="mt-1 text-xs text-ink-500">Nilai sebelum dan sesudah aktivitas ini dijalankan.</p>
        </div>

        @if ($changes->isEmpty())
            <p class="px-6 py-12 text-center text-sm text-ink-400">
                Aktivitas ini tidak mengubah data, sehingga tidak ada perbandingan yang dapat ditampilkan.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-ink-100 bg-ink-50/40 text-[0.65rem] uppercase tracking-[0.14em] text-ink-500">
                            <th scope="col" class="px-6 py-3 font-bold">Kolom</th>
                            <th scope="col" class="px-6 py-3 font-bold">Before</th>
                            <th scope="col" class="px-6 py-3 font-bold">After</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($changes as $change)
                            <tr @class(['align-top', 'bg-amber-50/40' => $change['changed']])>
                                <td class="px-6 py-4 font-semibold text-ink-800">{{ $change['label'] }}</td>
                                <td class="px-6 py-4 text-ink-500">
                                    <span class="break-words font-mono text-xs">{{ $change['old'] ?? '—' }}</span>
                                </td>
                                <td class="px-6 py-4 {{ $change['changed'] ? 'text-ink-900' : 'text-ink-500' }}">
                                    <span class="break-words font-mono text-xs {{ $change['changed'] ? 'font-bold' : '' }}">{{ $change['new'] ?? '—' }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
