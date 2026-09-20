@extends('layouts.dashboard')

@section('title', 'Permintaan Penawaran')

@section('content')
    <div>

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Permintaan Penawaran</h2>
                <p class="mt-2 text-sm text-ink-500">Seluruh permintaan yang dikirim melalui halaman 3D Models.</p>
            </div>

            @if ($pendingCancellations > 0)
                <a href="{{ staff_route('quotations.index', ['status' => \App\Support\QuotationStatus::CANCELLATION_REQUESTED]) }}"
                   class="inline-flex items-center gap-2 rounded-xl border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-bold text-amber-800 transition-colors hover:bg-amber-100">
                    <x-icons.alert class="h-4 w-4" />
                    {{ $pendingCancellations }} permintaan pembatalan menunggu persetujuan
                </a>
            @endif
        </div>

        {{-- Ringkasan --}}
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['label' => 'Total Permintaan', 'value' => number_format($summary['total'], 0, ',', '.')],
                ['label' => 'Belum Ditindaklanjuti', 'value' => number_format($summary['new'], 0, ',', '.')],
                ['label' => 'Model Ready to Print', 'value' => number_format($summary['ready'], 0, ',', '.').' / '.number_format($summary['models'], 0, ',', '.')],
                ['label' => 'Nilai Estimasi', 'value' => 'Rp'.number_format((float) $summary['value'], 0, ',', '.')],
            ] as $card)
                <div class="rounded-2xl border border-ink-100 bg-white p-5 shadow-card">
                    <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $card['label'] }}</p>
                    <p class="mt-2 font-display text-2xl font-bold text-ink-900">{{ $card['value'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Filter --}}
        <form method="GET" class="mt-8 grid gap-3 rounded-2xl border border-ink-100 bg-white p-5 shadow-card sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label for="q" class="block text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Cari</label>
                <input type="search" id="q" name="q" value="{{ $filters['q'] }}" placeholder="Nama, email, perusahaan, nama file, atau referensi"
                       class="mt-2 w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm focus:border-brand-600 focus:outline-none">
            </div>

            <div>
                <label for="status" class="block text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Status</label>
                <select id="status" name="status" class="mt-2 w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm focus:border-brand-600 focus:outline-none">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="technology" class="block text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Teknologi</label>
                <div class="mt-2 flex gap-2">
                    <select id="technology" name="technology" class="w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm focus:border-brand-600 focus:outline-none">
                        <option value="">Semua</option>
                        @foreach ($technologies as $code)
                            <option value="{{ $code }}" @selected($filters['technology'] === $code)>{{ $code }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-primary shrink-0 px-5 py-2.5">Filter</button>
                </div>
            </div>
        </form>

        @can(\App\Support\AdminPermission::QUOTATION_DELETE)
            {{-- Pembatalan massal. Formulirnya sengaja DI LUAR tabel: tiap baris
                 sudah punya formulir pembatalan satuannya sendiri, dan formulir
                 bersarang bukan HTML yang sah. Kotak centangnya dikaitkan lewat
                 atribut `form`. --}}
            <form method="POST" id="quotations-bulk-cancel"
                  action="{{ staff_route('quotations.cancel-many') }}"
                  data-bulk-form="quotations"
                  data-bulk-noun="penawaran"
                  data-bulk-title="Batalkan Penawaran?"
                  data-bulk-message="{count} penawaran yang dipilih akan dibatalkan dan tidak dapat diproses ke tahap berikutnya. Datanya tetap tersimpan untuk history dan Activity Log."
                  data-bulk-accept="Ya, Batalkan">
                @csrf
            </form>

            {{-- Bilah ini SELALU tampil, bahkan saat belum ada yang dipilih:
                 menekan tombolnya ketika kosong memunculkan pesan di sebelahnya,
                 bukan diam tanpa penjelasan. --}}
            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-ink-100 bg-white px-4 py-3 shadow-card">
                <div class="flex flex-wrap items-center gap-3">
                    <p class="text-sm font-semibold text-ink-600">
                        <span data-bulk-count="quotations">0</span> penawaran dipilih
                    </p>

                    <p class="hidden text-sm font-semibold text-brand-700"
                       data-bulk-empty="quotations"
                       role="alert"
                       tabindex="-1">Pilih minimal satu penawaran.</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" class="viewer-tool" data-bulk-clear="quotations">Batalkan Pilihan</button>
                    <button type="submit" form="quotations-bulk-cancel"
                            class="viewer-tool border-brand-300 text-brand-700 hover:border-brand-600 hover:bg-brand-50">
                        Hapus Terpilih
                    </button>
                </div>
            </div>
        @endcan

        {{-- Tabel --}}
        <div class="mt-6 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1080px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-ink-100 bg-ink-50/80 text-[0.65rem] uppercase tracking-[0.14em] text-ink-500">
                            @can(\App\Support\AdminPermission::QUOTATION_DELETE)
                                <th scope="col" class="w-10 px-4 py-4">
                                    <input type="checkbox"
                                           class="h-4 w-4 rounded border-ink-300 text-brand-600 focus:ring-brand-600"
                                           data-bulk-all="quotations"
                                           aria-label="Pilih seluruh penawaran di halaman ini">
                                </th>
                            @endcan
                            <th scope="col" class="px-5 py-4 font-bold">Pelanggan</th>
                            <th scope="col" class="px-5 py-4 font-bold">Jenis Akun</th>
                            <th scope="col" class="px-5 py-4 font-bold">Kontak</th>
                            <th scope="col" class="px-5 py-4 font-bold">File</th>
                            <th scope="col" class="px-5 py-4 font-bold">Teknologi &amp; Material</th>
                            <th scope="col" class="px-5 py-4 font-bold">Analisis</th>
                            <th scope="col" class="px-5 py-4 font-bold">Estimasi</th>
                            <th scope="col" class="px-5 py-4 font-bold">Status</th>
                            <th scope="col" class="px-5 py-4 font-bold">Payment</th>
                            <th scope="col" class="px-5 py-4 font-bold">Tanggal</th>
                            <th scope="col" class="px-5 py-4 text-right font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($quotations as $quotation)
                            <tr class="transition-colors hover:bg-brand-50/40">
                                @can(\App\Support\AdminPermission::QUOTATION_DELETE)
                                    @php $cancelled = \App\Support\QuotationStatus::isCancellation($quotation->status); @endphp

                                    <td class="px-4 py-4">
                                        {{-- Penawaran yang sudah dibatalkan tidak dapat dipilih:
                                             mencentangnya hanya berujung pada penolakan server.
                                             Tanpa `data-bulk-item` kotak ini juga tidak ikut
                                             terpilih oleh centang-semua. --}}
                                        <input type="checkbox"
                                               class="h-4 w-4 rounded border-ink-300 text-brand-600 focus:ring-brand-600 disabled:cursor-not-allowed disabled:opacity-40"
                                               form="quotations-bulk-cancel"
                                               name="ids[]"
                                               value="{{ $quotation->id }}"
                                               @if ($cancelled) disabled @else data-bulk-item="quotations" @endif
                                               title="{{ $cancelled ? 'Penawaran ini sudah dibatalkan.' : 'Pilih penawaran '.$quotation->tracking_number }}"
                                               aria-label="{{ $cancelled
                                                   ? 'Penawaran '.$quotation->tracking_number.' sudah dibatalkan'
                                                   : 'Pilih penawaran '.$quotation->tracking_number }}">
                                    </td>
                                @endcan
                                <td class="px-5 py-4">
                                    @if ($quotation->user_id && auth()->user()->can(\App\Support\AdminPermission::USER_VIEW))
                                        <a href="{{ staff_route('users.show', $quotation->user_id) }}" class="font-semibold text-ink-900 transition-colors hover:text-brand-600">
                                            {{ $quotation->name }}
                                        </a>
                                    @else
                                        <p class="font-semibold text-ink-900">{{ $quotation->name }}</p>
                                    @endif
                                    <p class="text-xs text-ink-400">{{ $quotation->company ?: '-' }}</p>
                                    <p class="mt-1 font-mono text-[0.65rem] text-brand-600">{{ $quotation->tracking_number }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    {{-- Tipe akun dibaca dari data registrasi
                                         (`users.customer_type`), bukan ditebak
                                         dari ada tidaknya nama perusahaan. --}}
                                    @php $business = (bool) $quotation->user?->isBusiness(); @endphp

                                    <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-0.5 text-[0.65rem] font-bold
                                                 {{ $business ? 'bg-indigo-100 text-indigo-800' : 'bg-ink-100 text-ink-600' }}">
                                        {{ $quotation->user?->customer_type_label ?? 'Tanpa Akun' }}
                                    </span>

                                    @if ($quotation->usesInstallments())
                                        <p class="mt-1 text-[0.6rem] font-semibold uppercase tracking-[0.1em] text-ink-400">
                                            {{ $quotation->paymentTerm->installment_count }} termin
                                        </p>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <a href="mailto:{{ $quotation->email }}" class="block text-ink-700 transition-colors hover:text-brand-600">{{ $quotation->email }}</a>
                                    <span class="text-xs text-ink-400">{{ $quotation->whatsapp }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="max-w-[180px] truncate font-medium text-ink-700" title="{{ $quotation->file_name }}">{{ $quotation->file_name }}</p>
                                    <p class="text-xs text-ink-400">{{ $quotation->file_format }} &middot; {{ number_format($quotation->file_size / 1024, 0, ',', '.') }} KB</p>

                                    @if ($quotation->hasMultipleModels())
                                        <p class="mt-1 inline-block rounded-full border border-brand-200 bg-brand-50 px-2 py-0.5 text-[0.6rem] font-bold text-brand-700">
                                            +{{ $quotation->model_count - 1 }} model lain
                                        </p>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-ink-900">{{ $quotation->technology }}</p>
                                    <p class="text-xs text-ink-400">{{ $quotation->material }} &middot; {{ $quotation->quantity }} unit</p>

                                    @if ($quotation->hasMultipleModels())
                                        <p class="text-[0.65rem] text-ink-400">pilihan model pertama</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <x-admin.analysis-badge :status="$quotation->analysis_status" />
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-ink-900">Rp{{ number_format((float) $quotation->estimated_cost, 0, ',', '.') }}</p>
                                    <p class="text-xs text-ink-400">{{ $quotation->estimated_duration ?? '-' }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    {{-- Keadaan pembatalan diberi warna sendiri. Tanpa itu
                                         "Pembatalan Disetujui" terbaca sama saja dengan tahap
                                         yang masih berjalan, dan penawaran yang baru saja
                                         dibatalkan tampak seolah tidak berubah. --}}
                                    @php $cancelledRow = \App\Support\QuotationStatus::isCancellation($quotation->status); @endphp

                                    <span class="inline-flex whitespace-nowrap rounded-full border px-3 py-1 text-xs font-semibold
                                                 {{ $cancelledRow
                                                     ? 'border-brand-200 bg-brand-50 text-brand-700'
                                                     : 'border-ink-200 bg-ink-50 text-ink-600' }}">
                                        {{ $quotation->status_label }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    {{-- Keadaan pembayarannya, sekaligus penunjuk
                                         ke tempat verifikasinya: sekali bayar
                                         diputuskan di Detail Penawaran, termin
                                         lewat menu Pembayaran. --}}
                                    @php
                                        [$paymentLabel, $paymentTone] = match (true) {
                                            $quotation->usesInstallments() => ['Per Termin', 'bg-indigo-100 text-indigo-800'],
                                            $quotation->awaitsPaymentDecision() => ['Menunggu Verifikasi', 'bg-amber-100 text-amber-800'],
                                            (bool) $quotation->payment_verified_at => ['Diterima', 'bg-emerald-100 text-emerald-800'],
                                            (bool) $quotation->payment_rejected_at => ['Ditolak', 'bg-brand-100 text-brand-800'],
                                            $quotation->status === \App\Support\QuotationStatus::AWAITING_PAYMENT => ['Menunggu Pembayaran', 'bg-ink-100 text-ink-600'],
                                            default => ['-', 'bg-transparent text-ink-400'],
                                        };
                                    @endphp

                                    <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-0.5 text-[0.65rem] font-bold {{ $paymentTone }}">
                                        {{ $paymentLabel }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-xs text-ink-500">
                                    {{ $quotation->created_at->translatedFormat('d M Y') }}<br>
                                    <span class="text-ink-400">{{ $quotation->created_at->format('H:i') }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <a href="{{ staff_route('quotations.show', $quotation) }}" class="viewer-tool">Detail</a>
                                        @if ($quotation->fileExists())
                                            <a href="{{ staff_route('quotations.download', $quotation) }}" class="viewer-tool">Unduh</a>
                                        @endif

                                        {{-- "Hapus" membatalkan, bukan menghapus: datanya tetap
                                             tersimpan untuk history dan Activity Log. Penawaran
                                             yang sudah dibatalkan tidak lagi menampilkannya. --}}
                                        @can(\App\Support\AdminPermission::QUOTATION_DELETE)
                                            @unless (\App\Support\QuotationStatus::isCancellation($quotation->status))
                                                <form method="POST" action="{{ staff_route('quotations.cancel', $quotation) }}"
                                                      data-confirm="Penawaran {{ $quotation->tracking_number }} akan dibatalkan dan tidak dapat diproses ke tahap berikutnya. Datanya tetap tersimpan untuk history dan Activity Log."
                                                      data-confirm-title="Batalkan Penawaran?"
                                                      data-confirm-accept="Ya, Batalkan"
                                                      data-confirm-cancel="Batal">
                                                    @csrf
                                                    <button type="submit" class="viewer-tool border-brand-200 text-brand-700 hover:border-brand-600 hover:bg-brand-50">
                                                        Hapus
                                                    </button>
                                                </form>
                                            @endunless
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->can(\App\Support\AdminPermission::QUOTATION_DELETE) ? 12 : 11 }}" class="px-5 py-16 text-center">
                                    <p class="font-semibold text-ink-700">Belum ada permintaan penawaran.</p>
                                    <p class="mt-1.5 text-sm text-ink-400">Permintaan akan muncul di sini setelah pengunjung mengirimkannya dari halaman 3D Models.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($quotations->hasPages())
            <div class="mt-6">
                {{ $quotations->links() }}
            </div>
        @endif
    </div>
@endsection
