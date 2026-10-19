@extends('layouts.dashboard')

@section('title', 'Pengaturan WhatsApp')

@section('content')
    @php
        $canEdit = auth()->user()->can(\App\Support\AdminPermission::WHATSAPP_EDIT);

        $connectionTone = match ($setting->connection_status) {
            \App\Models\WhatsAppSetting::CONNECTED => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            \App\Models\WhatsAppSetting::DISCONNECTED => 'bg-brand-50 text-brand-700 border-brand-200',
            default => 'bg-ink-50 text-ink-600 border-ink-200',
        };

        $logTone = fn (string $status) => match ($status) {
            \App\Models\WhatsAppNotification::SENT => 'bg-emerald-50 text-emerald-700',
            \App\Models\WhatsAppNotification::FAILED => 'bg-brand-50 text-brand-700',
            default => 'bg-amber-50 text-amber-700',
        };
    @endphp

    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink-900">Pengaturan WhatsApp</h1>
        <p class="mt-1 text-sm text-ink-500">
            Nomor WhatsApp resmi NUSAMA3D yang mengirim notifikasi perubahan status penawaran ke pelanggan.
            Mengganti nomor cukup dilakukan di sini, tanpa perubahan program.
        </p>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Nomor pengirim --}}
        <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <h2 class="font-display text-base font-bold text-ink-900">Nomor Pengirim</h2>

                <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $connectionTone }}">
                    {{ $setting->connection_label }}
                </span>
            </div>

            <form method="POST" action="{{ staff_route('whatsapp.update') }}" class="mt-5">
                @csrf
                @method('PATCH')

                <label for="phone_number" class="field-label">Nomor WhatsApp</label>
                <input type="text"
                       id="phone_number"
                       name="phone_number"
                       inputmode="tel"
                       autocomplete="off"
                       value="{{ old('phone_number', $setting->phone_number) }}"
                       placeholder="6281234567890"
                       @disabled(! $canEdit)
                       class="field-input">
                <p class="mt-2 text-xs text-ink-400">
                    Format internasional tanpa tanda, mis. 6281234567890. Awalan 0 otomatis diubah menjadi 62.
                </p>
                @error('phone_number')
                    <p class="field-error">{{ $message }}</p>
                @enderror

                <dl class="mt-5 space-y-2 rounded-xl bg-ink-50/70 p-4 text-sm">
                    <div class="flex flex-wrap justify-between gap-2">
                        <dt class="text-ink-500">Status koneksi</dt>
                        <dd class="font-semibold text-ink-900">{{ $setting->connection_label }}</dd>
                    </div>
                    <div class="flex flex-wrap justify-between gap-2">
                        <dt class="text-ink-500">Diperiksa terakhir</dt>
                        <dd class="font-semibold text-ink-900">{{ $setting->connection_checked_at?->translatedFormat('d M Y, H:i') ?? '—' }}</dd>
                    </div>
                    @if ($setting->connection_message)
                        <div class="border-t border-ink-100 pt-2 text-xs leading-relaxed text-ink-600">
                            {{ $setting->connection_message }}
                        </div>
                    @endif
                </dl>

                @if ($canEdit)
                    <div class="mt-5 flex flex-wrap gap-3">
                        <button type="submit" class="btn-primary">Simpan</button>
                        {{-- Memakai formulir terpisah di bawah: di dalam formulir ini
                             ikut terkirim `_method=PATCH`, yang tidak diterima route cek koneksi. --}}
                        <button type="submit" form="whatsapp-check-form" class="viewer-tool"
                                @disabled(! $setting->hasSender())>
                            Periksa Koneksi
                        </button>
                    </div>
                @endif
            </form>

            @if ($canEdit)
                <form id="whatsapp-check-form" method="POST" action="{{ staff_route('whatsapp.check') }}" class="hidden">
                    @csrf
                </form>
            @endif
        </section>

        {{-- Uji kirim --}}
        <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
            <h2 class="font-display text-base font-bold text-ink-900">Uji Kirim</h2>
            <p class="mt-1 text-sm text-ink-500">
                Kirim pesan percobaan untuk memastikan konfigurasi WhatsApp berjalan.
            </p>

            @if ($canEdit)
                <form method="POST" action="{{ staff_route('whatsapp.test') }}" class="mt-5">
                    @csrf

                    <label for="test_number" class="field-label">Nomor Tujuan</label>
                    <input type="text"
                           id="test_number"
                           name="test_number"
                           inputmode="tel"
                           autocomplete="off"
                           value="{{ old('test_number') }}"
                           placeholder="628xxxxxxxxxx"
                           class="field-input">
                    @error('test_number')
                        <p class="field-error">{{ $message }}</p>
                    @enderror

                    <button type="submit" class="btn-primary mt-5" @disabled(! $setting->hasSender())>
                        Uji Kirim WhatsApp
                    </button>

                    @unless ($setting->hasSender())
                        <p class="mt-2 text-xs text-ink-400">Simpan nomor pengirim lebih dulu.</p>
                    @endunless
                </form>
            @else
                <p class="mt-5 text-sm text-ink-500">Anda tidak memiliki hak untuk melakukan uji kirim.</p>
            @endif

            <div class="mt-6 rounded-xl border border-ink-100 bg-ink-50/70 p-4 text-xs leading-relaxed text-ink-600">
                <p>
                    Jalur kirim:
                    <span class="font-semibold text-ink-900">
                        {{ $driver === 'cloud_api' ? 'WhatsApp Cloud API (resmi Meta)' : 'Mode log — pesan hanya dicatat, belum dikirim' }}
                    </span>
                </p>
                @if ($driver === 'cloud_api')
                    <p class="mt-1">
                        Format pesan:
                        <span class="font-semibold text-ink-900">{{ $usesTemplate ? 'Template yang disetujui Meta' : 'Teks biasa (hanya sampai bila pelanggan menghubungi dalam 24 jam terakhir)' }}</span>
                    </p>
                @endif
                <p class="mt-1">Credential API disimpan pada konfigurasi server dan tidak ditampilkan di halaman ini.</p>
            </div>
        </section>
    </div>

    {{-- Riwayat pengiriman --}}
    <section class="mt-6 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
        <div class="flex flex-wrap items-end justify-between gap-3 border-b border-ink-100 px-6 py-5">
            <div>
                <h2 class="font-display text-base font-bold text-ink-900">Riwayat Pengiriman</h2>
                <p class="mt-1 text-xs text-ink-500">
                    Notifikasi status penawaran —
                    {{ (int) ($summary[\App\Models\WhatsAppNotification::SENT] ?? 0) }} berhasil,
                    {{ (int) ($summary[\App\Models\WhatsAppNotification::FAILED] ?? 0) }} gagal,
                    {{ (int) ($summary[\App\Models\WhatsAppNotification::PENDING] ?? 0) }} menunggu.
                </p>
            </div>

            <form method="GET" class="flex items-end gap-2">
                <div>
                    <label for="status" class="field-label">Status Pengiriman</label>
                    <select id="status" name="status" class="field-input" onchange="this.form.submit()">
                        <option value="">Semua</option>
                        @foreach ($logStatuses as $key => $label)
                            <option value="{{ $key }}" @selected($logFilter === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <noscript><button type="submit" class="btn-primary">Saring</button></noscript>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead>
                    <tr class="border-b border-ink-100 text-[0.6rem] uppercase tracking-[0.14em] text-ink-400">
                        <th scope="col" class="px-6 py-4 font-bold">Waktu</th>
                        <th scope="col" class="px-6 py-4 font-bold">Penawaran</th>
                        <th scope="col" class="px-6 py-4 font-bold">Pelanggan</th>
                        <th scope="col" class="px-6 py-4 font-bold">Nomor Tujuan</th>
                        <th scope="col" class="px-6 py-4 font-bold">Status Penawaran</th>
                        <th scope="col" class="px-6 py-4 font-bold">Pengiriman</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-ink-100">
                    @forelse ($logs as $log)
                        <tr class="align-top">
                            <td class="whitespace-nowrap px-6 py-4 text-ink-600">
                                {{ ($log->sent_at ?? $log->created_at)->translatedFormat('d M Y, H:i') }}
                                @if ($log->triggeredBy)
                                    <span class="block text-xs text-ink-400">oleh {{ $log->triggeredBy->name }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($log->type === \App\Models\WhatsAppNotification::TYPE_TEST)
                                    <span class="text-ink-500">Uji Kirim</span>
                                @elseif ($log->quotation_request_id && auth()->user()->can(\App\Support\AdminPermission::QUOTATION_VIEW))
                                    <a href="{{ staff_route('quotations.show', $log->quotation_request_id) }}" class="whitespace-nowrap font-mono font-semibold text-brand-700 hover:underline">
                                        {{ $log->tracking_number }}
                                    </a>
                                @else
                                    <span class="whitespace-nowrap font-mono text-ink-700">{{ $log->tracking_number ?? '—' }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-ink-900">{{ $log->customer_name ?? '—' }}</td>
                            <td class="px-6 py-4 font-mono text-ink-600">{{ $log->recipient ?? '—' }}</td>
                            <td class="px-6 py-4 text-ink-700">
                                {{ $log->quotation_status ? \App\Support\QuotationStatus::label($log->quotation_status) : '—' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $logTone($log->status) }}">
                                    {{ $log->status_label }}
                                </span>
                                @if ($log->error_message)
                                    <p class="mt-1 max-w-xs text-xs leading-relaxed text-brand-700">{{ $log->error_message }}</p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-ink-400">Belum ada pengiriman WhatsApp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="border-t border-ink-100 px-6 py-4">{{ $logs->links() }}</div>
        @endif
    </section>
@endsection
