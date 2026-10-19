@extends('layouts.dashboard')

@section('title', 'Lead')

@php
    $inputClass = 'mt-2 w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm focus:border-brand-600 focus:outline-none';
    $labelClass = 'block text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400';
    $filtered = collect($filters)->filter()->isNotEmpty();
@endphp

@section('content')
    <div>

        <div>
            <h2 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Lead</h2>
            <p class="mt-2 text-sm text-ink-500">Seluruh lead dari Form landing page layanan dan Quotation, beserta sumber kedatangannya.</p>
        </div>

        {{-- Ringkasan --}}
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['label' => 'Total Lead', 'value' => number_format($summary['total'], 0, ',', '.')],
                ['label' => 'Belum Quotation', 'value' => number_format($summary['lead'], 0, ',', '.')],
                ['label' => 'Deal', 'value' => number_format($summary['deal'], 0, ',', '.')],
                ['label' => 'Total Nilai Deal', 'value' => 'Rp'.number_format($summary['deal_value'], 0, ',', '.')],
            ] as $card)
                <div class="rounded-2xl border border-ink-100 bg-white p-5 shadow-card">
                    <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $card['label'] }}</p>
                    <p class="mt-2 font-display text-2xl font-bold text-ink-900">{{ $card['value'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Export CSV Deal (Google Ads) — hanya untuk hak lead.export. --}}
        @if ($export)
            @include('admin.leads.partials.export-deals')
        @endif

        {{-- Filter --}}
        <form method="GET" class="mt-8 grid gap-3 rounded-2xl border border-ink-100 bg-white p-5 shadow-card sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2">
                <label for="q" class="{{ $labelClass }}">Cari</label>
                <input type="search" id="q" name="q" value="{{ $filters['q'] }}" placeholder="Nama, perusahaan, email, atau nomor WhatsApp" class="{{ $inputClass }}">
            </div>

            <div>
                <label for="from" class="{{ $labelClass }}">Dari Tanggal</label>
                <input type="date" id="from" name="from" value="{{ $filters['from'] }}" class="{{ $inputClass }}">
            </div>

            <div>
                <label for="to" class="{{ $labelClass }}">Sampai Tanggal</label>
                <input type="date" id="to" name="to" value="{{ $filters['to'] }}" class="{{ $inputClass }}">
            </div>

            <div>
                <label for="source" class="{{ $labelClass }}">Sumber</label>
                <select id="source" name="source" class="{{ $inputClass }}">
                    <option value="">Semua sumber</option>
                    @foreach ($sources as $value => $label)
                        <option value="{{ $value }}" @selected($filters['source'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="service" class="{{ $labelClass }}">Layanan</label>
                <select id="service" name="service" class="{{ $inputClass }}">
                    <option value="">Semua layanan</option>
                    @foreach ($services as $service)
                        <option value="{{ $service }}" @selected($filters['service'] === $service)>{{ $service }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="status" class="{{ $labelClass }}">Status Lead</label>
                <select id="status" name="status" class="{{ $inputClass }}">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="campaign" class="{{ $labelClass }}">UTM Campaign</label>
                <select id="campaign" name="campaign" class="{{ $inputClass }}">
                    <option value="">Semua campaign</option>
                    @foreach ($campaigns as $campaign)
                        <option value="{{ $campaign }}" @selected($filters['campaign'] === $campaign)>{{ $campaign }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-4 lg:justify-end">
                @if ($filtered)
                    <a href="{{ staff_route('leads.index') }}" class="viewer-tool px-5 py-2.5">Reset</a>
                @endif
                <button type="submit" class="btn-primary px-6 py-2.5">Filter</button>
            </div>
        </form>

        {{-- Tabel --}}
        <div class="mt-6 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1900px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-ink-100 bg-ink-50/80 text-[0.65rem] uppercase tracking-[0.14em] text-ink-500">
                            <th scope="col" class="px-4 py-4 font-bold">Tanggal</th>
                            <th scope="col" class="px-4 py-4 font-bold">Nama</th>
                            <th scope="col" class="px-4 py-4 font-bold">Perusahaan</th>
                            <th scope="col" class="px-4 py-4 font-bold">Email</th>
                            <th scope="col" class="px-4 py-4 font-bold">WhatsApp</th>
                            <th scope="col" class="px-4 py-4 font-bold">Layanan</th>
                            <th scope="col" class="px-4 py-4 font-bold">Halaman Asal</th>
                            <th scope="col" class="px-4 py-4 font-bold">GCLID</th>
                            <th scope="col" class="px-4 py-4 font-bold">UTM Source</th>
                            <th scope="col" class="px-4 py-4 font-bold">UTM Medium</th>
                            <th scope="col" class="px-4 py-4 font-bold">UTM Campaign</th>
                            <th scope="col" class="px-4 py-4 font-bold">UTM Term</th>
                            <th scope="col" class="px-4 py-4 font-bold">UTM Content</th>
                            <th scope="col" class="px-4 py-4 font-bold">Status Lead</th>
                            <th scope="col" class="px-4 py-4 font-bold">Nilai Deal</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($leads as $lead)
                            <tr class="align-top transition-colors hover:bg-brand-50/40">
                                <td class="px-4 py-4 text-xs text-ink-500">
                                    {{ $lead->created_at->translatedFormat('d M Y') }}<br>
                                    <span class="text-ink-400">{{ $lead->created_at->format('H:i') }}</span>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-ink-900">{{ $lead->name }}</p>
                                    <p class="mt-1 flex flex-wrap gap-1">
                                        <span class="inline-flex rounded-full bg-ink-100 px-2 py-0.5 text-[0.6rem] font-bold text-ink-600">{{ $lead->source_label }}</span>
                                        @if ($lead->quotations_count > 0)
                                            <span class="inline-flex rounded-full border border-brand-200 bg-brand-50 px-2 py-0.5 text-[0.6rem] font-bold text-brand-700">{{ $lead->quotations_count }} quotation</span>
                                        @endif
                                    </p>
                                </td>
                                <td class="px-4 py-4 text-ink-700">{{ $lead->company ?: '—' }}</td>
                                <td class="px-4 py-4">
                                    @if ($lead->email)
                                        <a href="mailto:{{ $lead->email }}" class="text-ink-700 transition-colors hover:text-brand-600">{{ $lead->email }}</a>
                                    @else
                                        <span class="text-ink-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    @if ($lead->whatsapp)
                                        <a href="https://wa.me/{{ $lead->whatsapp }}" target="_blank" rel="noopener" class="whitespace-nowrap text-ink-700 transition-colors hover:text-brand-600">+{{ $lead->whatsapp }}</a>
                                    @else
                                        <span class="text-ink-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-ink-700">{{ $lead->service ?: '—' }}</td>
                                <td class="px-4 py-4">
                                    @if ($lead->source_url)
                                        <p class="max-w-[200px] truncate text-xs text-ink-600" title="{{ $lead->source_url }}">{{ parse_url($lead->source_url, PHP_URL_PATH) ?: '/' }}</p>
                                    @else
                                        <span class="text-ink-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    @if ($lead->gclid)
                                        <p class="max-w-[140px] truncate font-mono text-[0.65rem] text-ink-600" title="{{ $lead->gclid }}">{{ $lead->gclid }}</p>
                                    @else
                                        <span class="text-ink-400">—</span>
                                    @endif
                                </td>
                                @foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'] as $utm)
                                    <td class="px-4 py-4">
                                        @if ($lead->{$utm})
                                            <p class="max-w-[150px] truncate text-xs text-ink-700" title="{{ $lead->{$utm} }}">{{ $lead->{$utm} }}</p>
                                        @else
                                            <span class="text-ink-400">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-4 py-4">
                                    <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-0.5 text-[0.65rem] font-bold {{ \App\Support\LeadStatus::tone($lead->status) }}">
                                        {{ $lead->status_label }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 font-semibold text-ink-900">{{ $lead->deal_value_label }}</td>
                                <td class="px-4 py-4 text-right">
                                    <a href="{{ staff_route('leads.show', $lead) }}" class="viewer-tool">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="16" class="px-5 py-16 text-center">
                                    @if ($filtered)
                                        <p class="font-semibold text-ink-700">Tidak ada lead yang cocok dengan filter ini.</p>
                                        <p class="mt-1.5 text-sm text-ink-400">Ubah atau reset filter untuk melihat lead lainnya.</p>
                                    @else
                                        <p class="font-semibold text-ink-700">Belum ada lead.</p>
                                        <p class="mt-1.5 text-sm text-ink-400">Lead muncul di sini setelah pengunjung mengirim form landing page layanan atau quotation.</p>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($leads->hasPages())
            <div class="mt-6">
                {{ $leads->links() }}
            </div>
        @endif
    </div>
@endsection
