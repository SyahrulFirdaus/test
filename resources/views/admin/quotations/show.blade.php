@extends('layouts.dashboard')

@section('title', 'Detail '.$quotation->tracking_number)

@section('content')
    <div>

        <a href="{{ staff_route('quotations.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600">
            &larr; Kembali ke daftar permintaan
        </a>

        {{-- ============ PERMINTAAN PEMBATALAN ============ --}}
        @if ($quotation->hasPendingCancellation())
            <section class="mt-5 rounded-2xl border-2 border-amber-300 bg-amber-50 p-6 shadow-card">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-200 text-amber-800">
                        <x-icons.alert class="h-5 w-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <h2 class="font-display text-base font-bold text-amber-900">Permintaan Pembatalan Menunggu Persetujuan</h2>
                        <p class="mt-1 text-sm text-amber-800">
                            Diajukan {{ optional($quotation->cancellation_requested_at)->translatedFormat('d F Y, H:i') ?? '-' }} WIB
                            @if ($quotation->status_before_cancellation)
                                &middot; sebelumnya berstatus
                                <span class="font-semibold">{{ \App\Support\QuotationStatus::label($quotation->status_before_cancellation) }}</span>
                            @endif
                        </p>

                        @if ($quotation->cancellation_reason)
                            <div class="mt-4 rounded-xl border border-amber-200 bg-white p-4">
                                <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-amber-700">Alasan Pelanggan</p>
                                <p class="mt-1.5 whitespace-pre-line text-sm leading-relaxed text-ink-700">{{ $quotation->cancellation_reason }}</p>
                            </div>
                        @endif

                        @can(\App\Support\AdminPermission::QUOTATION_UPDATE_STATUS)
                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <form method="POST" action="{{ staff_route('quotations.cancellation.approve', $quotation) }}"
                                  onsubmit="return confirm('Setujui pembatalan penawaran {{ $quotation->tracking_number }}?');">
                                @csrf
                                <textarea name="note" rows="2" maxlength="2000" class="field-input mt-0" placeholder="Catatan untuk pelanggan (opsional)"></textarea>
                                <button type="submit" class="btn-primary mt-3 w-full">Setujui Pembatalan</button>
                            </form>

                            <form method="POST" action="{{ staff_route('quotations.cancellation.reject', $quotation) }}"
                                  onsubmit="return confirm('Tolak pembatalan dan lanjutkan penawaran ini?');">
                                @csrf
                                <textarea name="note" rows="2" maxlength="2000" class="field-input mt-0" placeholder="Alasan penolakan (opsional)"></textarea>
                                <button type="submit" class="btn-outline mt-3 w-full">Tolak Pembatalan</button>
                            </form>
                        </div>
                        @else
                            <p class="mt-5 text-xs font-semibold text-amber-800">Anda tidak memiliki hak akses Update Status untuk memutuskan pembatalan ini.</p>
                        @endcan

                        @if ($quotation->whatsapp_link)
                            <p class="mt-4 text-xs text-amber-800">
                                Perlu memastikan alasannya lebih dulu?
                                <a href="{{ $quotation->whatsapp_link }}" target="_blank" rel="noopener noreferrer" class="font-semibold underline">
                                    Hubungi pelanggan via WhatsApp
                                </a>.
                            </p>
                        @endif
                    </div>
                </div>
            </section>
        @elseif ($quotation->cancellation_resolved_at && $quotation->cancellation_reason)
            <section class="mt-5 rounded-2xl border border-ink-200 bg-white p-5 shadow-card">
                <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Riwayat Pembatalan</p>
                <p class="mt-1.5 text-sm text-ink-700">
                    <span class="font-semibold">{{ $quotation->status_label }}</span> &middot;
                    {{ $quotation->cancellation_resolved_at->translatedFormat('d F Y, H:i') }} WIB
                </p>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-ink-600">Alasan pelanggan: {{ $quotation->cancellation_reason }}</p>
                @if ($quotation->cancellation_admin_note)
                    <p class="mt-1.5 whitespace-pre-line text-sm leading-relaxed text-ink-600">Catatan admin: {{ $quotation->cancellation_admin_note }}</p>
                @endif
            </section>
        @endif

        <div class="mt-5 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="font-mono text-sm font-semibold text-brand-600">{{ $quotation->tracking_number }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">{{ $quotation->name }}</h2>
                <p class="mt-2 text-sm text-ink-500">
                    Dikirim {{ $quotation->created_at->translatedFormat('d F Y, H:i') }} WIB
                    @if ($quotation->company)
                        &middot; {{ $quotation->company }}
                    @endif
                    &middot; <span class="font-semibold text-ink-700">{{ $quotation->items->count() }} model</span>
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="#daftar-model" class="btn-primary px-5 py-2.5">
                    Lihat {{ $quotation->items->count() }} Model
                </a>

                @can(\App\Support\AdminPermission::QUOTATION_DELETE)
                {{-- Penghapusan PERMANEN, berbeda dari tombol "Hapus" pada daftar
                     penawaran yang hanya membatalkan. Pertanyaannya memakai modal
                     yang sama supaya rupanya konsisten, dan kata-katanya menegaskan
                     bedanya. --}}
                <form method="POST" action="{{ staff_route('quotations.destroy', $quotation) }}"
                      data-confirm="Permintaan {{ $quotation->tracking_number }} beserta berkas modelnya akan dihapus permanen. Tindakan ini tidak dapat dibatalkan. Untuk menghentikan penawaran tanpa kehilangan datanya, batalkan saja dari daftar Penawaran."
                      data-confirm-title="Hapus Permanen?"
                      data-confirm-accept="Ya, Hapus Permanen"
                      data-confirm-cancel="Batal">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="viewer-tool border-brand-200 text-brand-700 hover:border-brand-600 hover:bg-brand-50">
                        Hapus
                    </button>
                </form>
                @endcan
            </div>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-12">

            {{-- Kolom kiri --}}
            <div class="space-y-6 lg:col-span-8">

                {{-- Kontak & pesanan --}}
                <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card sm:p-7">
                    <h2 class="font-display text-base font-bold text-ink-900">Data Pemohon</h2>
                    <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                        @foreach ([
                            'Email' => $quotation->email,
                            'WhatsApp' => $quotation->whatsapp,
                            'Perusahaan' => $quotation->company ?: '-',
                            'Total Jumlah Cetak' => $quotation->quantity.' unit dari '.$quotation->items->count().' model',
                        ] as $label => $value)
                            <div>
                                <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $label }}</dt>
                                <dd class="mt-1.5 break-words text-sm font-semibold text-ink-800">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    {{-- Alamat yang tercatat adalah salinan saat penawaran dibuat,
                         jadi tetap utuh walaupun pelanggan mengubah atau menghapus
                         alamatnya di kemudian hari. --}}
                    <div class="mt-6 rounded-xl border border-ink-100 p-4">
                        <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Alamat Pengiriman</p>

                        @if ($shipping = $quotation->shipping_address)
                            <p class="mt-2 text-sm font-semibold text-ink-800">
                                {{ $shipping['recipient_name'] ?? '-' }}
                                <span class="font-normal text-ink-400">&middot;</span>
                                <span class="font-normal text-ink-600">{{ $shipping['recipient_phone'] ?? '-' }}</span>
                                @if (! empty($shipping['label']))
                                    <span class="ml-1 rounded-full bg-ink-100 px-2 py-0.5 text-[0.6rem] font-bold uppercase tracking-[0.1em] text-ink-500">{{ $shipping['label'] }}</span>
                                @endif
                            </p>
                            <p class="mt-1 text-sm leading-relaxed text-ink-600">{{ $shipping['full'] ?? '-' }}</p>

                            @if (! empty($shipping['note']))
                                <p class="mt-1.5 text-xs text-ink-400">Catatan kurir: {{ $shipping['note'] }}</p>
                            @endif
                        @else
                            <p class="mt-2 text-sm text-ink-500">
                                Pelanggan belum memilih alamat pengiriman. Tanyakan saat menghubungi pelanggan.
                            </p>
                        @endif
                    </div>

                    @if ($quotation->notes)
                        <div class="mt-6 rounded-xl bg-ink-50 p-4">
                            <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Catatan Pelanggan</p>
                            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-ink-600">{{ $quotation->notes }}</p>
                        </div>
                    @endif
                </section>

                {{-- ============ PEMBAYARAN ============
                     Verifikasi pembayaran sekali bayar dilakukan di sini, bukan
                     lagi lewat menu Pembayaran — yang kini khusus menangani
                     pembayaran bertahap. --}}
                @include('admin.quotations.partials.payment', [
                    'quotation' => $quotation,
                    'rupiah' => fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.'),
                ])

                {{-- ============ DAFTAR MODEL DALAM SATU PENAWARAN ============ --}}
                @php
                    $fmt = fn ($value, $digits = 2) => is_numeric($value) ? number_format((float) $value, $digits, ',', '.') : '-';

                    // Seluruh harga di halaman ini dibaca dari satu sumber: hasil
                    // App\Services\SellingPriceEstimator yang juga mengisi tabel
                    // Detail Perhitungan Harga. Kolom `estimated_price` penawaran
                    // sengaja tidak dipakai — pada penawaran lama isinya harga
                    // yang ditetapkan sebelum rumus Price List berlaku, sehingga
                    // tidak sama dengan baris Harga Jual di rincian. Selisih itu
                    // tetap dilaporkan di dalam rincian, pada "Tercatat pada
                    // Penawaran".
                    $hargaJualPenawaran = (float) $sellingPrice['selling_price'];

                    // Harga Jual per model, dikunci pada id modelnya supaya tabel
                    // ringkas dan kartu tiap model memakai angka yang sama persis
                    // dengan accordion rinciannya.
                    //
                    // null berarti harganya memang BELUM ADA - model SLA
                    // Industries yang kuotasi JLC-nya belum diisi. Dibedakan
                    // tegas dari Rp0 supaya tidak pernah terbaca sebagai gratis.
                    $hargaJualModel = $sellingPrice['models']
                        ->mapWithKeys(fn (array $entry) => [
                            $entry['item']->id => $entry['item']->awaitsPricing()
                                ? null
                                : (float) $entry['calculation']['selling_price'],
                        ]);

                    // Satu model tanpa harga membuat TOTAL penawaran belum ada juga.
                    $menungguHarga = $quotation->awaitsPricing();
                    $hargaLabel = fn ($value) => $value === null
                        ? 'Menunggu Perhitungan'
                        : 'Rp'.number_format((float) $value, 0, ',', '.');
                @endphp

                <section id="daftar-model" class="scroll-mt-24 rounded-2xl border border-ink-100 bg-white p-6 shadow-card sm:p-7">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="font-display text-base font-bold text-ink-900">Printer dalam Penawaran Ini</h2>
                            <p class="mt-1 text-xs text-ink-400">
                                {{ $quotation->items->count() }} mesin &middot; satu printer mencetak tepat satu model,
                                masing-masing dengan berkas, pengaturan, analisis, dan estimasinya sendiri.
                            </p>
                        </div>
                    </div>

                    {{-- Tabel ringkas seluruh model --}}
                    <div class="mt-5 overflow-x-auto">
                        <table class="w-full min-w-[760px] text-left text-sm">
                            <thead>
                                <tr class="border-b border-ink-100 text-[0.6rem] uppercase tracking-[0.14em] text-ink-400">
                                    <th scope="col" class="py-3 pr-3 font-bold">Printer</th>
                                    <th scope="col" class="px-3 py-3 font-bold">Nama File &amp; Mesin</th>
                                    <th scope="col" class="px-3 py-3 text-right font-bold">Jumlah</th>
                                    <th scope="col" class="px-3 py-3 text-right font-bold">Berat</th>
                                    <th scope="col" class="px-3 py-3 text-right font-bold">Harga</th>
                                    <th scope="col" class="px-3 py-3 text-center font-bold">Lihat 3D</th>
                                    <th scope="col" class="py-3 pl-3 text-right font-bold">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-ink-100">
                                @foreach ($quotation->items as $item)
                                    <tr>
                                        <td class="py-3 pr-3 font-mono text-xs text-ink-400">{{ $item->position }}</td>
                                        <td class="max-w-[220px] px-3 py-3">
                                            <a href="#model-{{ $item->id }}" class="block truncate font-semibold text-ink-800 hover:text-brand-600">{{ $item->file_name }}</a>
                                            <span class="block truncate text-[0.65rem] text-ink-400">{{ $item->printer_name }}</span>
                                        </td>
                                        <td class="px-3 py-3 text-right text-ink-600">{{ $item->quantity }} unit</td>
                                        <td class="px-3 py-3 text-right text-ink-600">{{ $fmt($item->total_weight_g * $item->quantity, 1) }} gr</td>
                                        <td class="px-3 py-3 text-right font-semibold {{ ($hargaJualModel[$item->id] ?? null) === null ? 'text-amber-700' : 'text-brand-700' }}">
                                            {{ $hargaLabel($hargaJualModel[$item->id] ?? null) }}
                                        </td>
                                        {{-- Pratinjau 3D dan unduhan memakai berkas yang sama milik
                                             baris ini — tanpa salinan dan tanpa upload ulang. --}}
                                        <td class="px-3 py-3 text-center">
                                            @if ($item->fileExists())
                                                <a href="{{ staff_route('quotations.items.viewer', [$quotation, $item]) }}"
                                                   class="viewer-tool whitespace-nowrap px-3 py-2"
                                                   title="Lihat 3D {{ $item->file_name }}">
                                                    <span aria-hidden="true">&#128065;</span> Lihat 3D
                                                </a>
                                            @else
                                                <span class="text-xs text-ink-400">-</span>
                                            @endif
                                        </td>
                                        <td class="py-3 pl-3 text-right">
                                            @if ($item->fileExists())
                                                <a href="{{ staff_route('quotations.items.download', [$quotation, $item]) }}"
                                                   class="viewer-tool whitespace-nowrap px-3 py-2"
                                                   title="Unduh {{ $item->file_name }}">
                                                    Download
                                                </a>
                                            @else
                                                <span class="text-xs text-ink-400">Berkas tidak ditemukan</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-ink-100 font-bold">
                                    <td colspan="2" class="py-3 pr-3 text-xs uppercase tracking-[0.12em] text-ink-500">Total</td>
                                    <td class="px-3 py-3 text-right text-ink-800">{{ $quotation->quantity }} unit</td>
                                    <td class="px-3 py-3 text-right text-ink-800">
                                        {{ $fmt($quotation->items->sum(fn ($item) => $item->total_weight_g * $item->quantity), 1) }} gr
                                    </td>
                                    <td class="px-3 py-3 text-right {{ $menungguHarga ? 'text-amber-700' : 'text-brand-700' }}">
                                        {{ $hargaLabel($menungguHarga ? null : $hargaJualPenawaran) }}
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- ============ DETAIL PERHITUNGAN HARGA (INTERNAL) ============
                         Sengaja hanya ada di halaman admin: formula, margin profit,
                         risk cost, dan machine cost adalah informasi internal
                         NUSAMA3D. Dashboard pelanggan cukup menampilkan total
                         penawaran dan statusnya. --}}
                    @include('admin.quotations.partials.selling-price')
                </section>

                {{-- Rincian tiap model --}}
                @foreach ($quotation->items as $item)
                    @php
                        $stats = $item->model_stats ?? [];
                        $dimensions = $stats['dimensions'] ?? null;
                        $box = $stats['bounding_box'] ?? null;
                        $orientation = $stats['orientation'] ?? null;
                        $rotation = $orientation['rotation_deg'] ?? null;
                        $mirrored = $orientation['mirrored'] ?? [];
                        $rotated = $rotation && ($rotation['x'] || $rotation['y'] || $rotation['z']);
                        $checks = $item->analysis ?? [];
                    @endphp

                    <section id="model-{{ $item->id }}" class="scroll-mt-24 rounded-2xl border border-ink-100 bg-white p-6 shadow-card sm:p-7">

                        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-ink-100 pb-5">
                            <div class="min-w-0">
                                <p class="text-[0.65rem] font-bold uppercase tracking-[0.14em] text-brand-600">
                                    Printer {{ $item->position }} dari {{ $quotation->items->count() }}
                                </p>
                                <h2 class="mt-1 break-all font-display text-base font-bold text-ink-900">{{ $item->file_name }}</h2>
                                <p class="mt-1 text-xs font-semibold text-brand-600">{{ $item->printer_label }}</p>
                                <p class="mt-1 text-xs text-ink-400">
                                    {{ $item->file_format }} &middot; {{ $fmt($item->file_size / 1024, 0) }} KB &middot;
                                    {{ $item->technology }} {{ $item->material }} &middot; {{ $item->quantity }} unit
                                    @unless ($item->isOriginalScale())
                                        &middot; <span class="font-semibold text-brand-600">skala {{ $item->scale_label }}</span>
                                    @endunless
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                @unless ($item->fits_build_volume)
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-brand-200 bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700">
                                        ⚠ Di luar area cetak
                                    </span>
                                @endunless

                                <x-admin.analysis-badge :status="$item->analysis_status" />

                                @if ($item->fileExists())
                                    <a href="{{ staff_route('quotations.items.viewer', [$quotation, $item]) }}" class="viewer-tool">
                                        <span aria-hidden="true">&#128065;</span> Lihat 3D
                                    </a>
                                    <a href="{{ staff_route('quotations.items.download', [$quotation, $item]) }}" class="viewer-tool">
                                        Unduh {{ $item->file_format }}
                                    </a>
                                @else
                                    <span class="viewer-tool cursor-not-allowed opacity-60">Berkas tidak ditemukan</span>
                                @endif
                            </div>
                        </div>

                        {{-- Informasi geometri --}}
                        <dl class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Vertex</dt>
                                <dd class="mt-1.5 text-sm font-semibold text-ink-800">{{ $fmt($stats['vertices'] ?? null, 0) }}</dd>
                            </div>
                            <div>
                                <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Segitiga</dt>
                                <dd class="mt-1.5 text-sm font-semibold text-ink-800">{{ $fmt($stats['triangles'] ?? null, 0) }}</dd>
                            </div>
                            <div>
                                <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Volume Model</dt>
                                <dd class="mt-1.5 text-sm font-semibold text-ink-800">{{ $fmt($item->model_volume_cm3, 3) }} cm³</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Dimensi (P × L × T)</dt>
                                <dd class="mt-1.5 text-sm font-semibold text-ink-800">
                                    @if ($dimensions)
                                        {{ $fmt($dimensions['x']) }} × {{ $fmt($dimensions['y']) }} × {{ $fmt($dimensions['z']) }} mm
                                    @else
                                        -
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Mesh Tertutup</dt>
                                <dd class="mt-1.5 text-sm font-semibold text-ink-800">
                                    {{ ($stats['topology_analyzed'] ?? false) ? (($stats['watertight'] ?? false) ? 'Ya' : 'Tidak') : 'Tidak dianalisis' }}
                                </dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Orientasi Diminta Pelanggan</dt>
                                <dd class="mt-1.5 text-sm font-semibold text-ink-800">
                                    @if ($rotated || filled($mirrored))
                                        @if ($rotated)
                                            Rotasi X {{ $rotation['x'] }}° &middot; Y {{ $rotation['y'] }}° &middot; Z {{ $rotation['z'] }}°
                                        @endif
                                        @if (filled($mirrored))
                                            <span class="text-brand-700">(dicerminkan pada {{ implode(', ', $mirrored) }})</span>
                                        @endif
                                    @else
                                        Orientasi asli file (tanpa perubahan)
                                    @endif
                                </dd>
                            </div>
                            @if ($box)
                                <div class="sm:col-span-2 lg:col-span-3">
                                    <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Bounding Box</dt>
                                    <dd class="mt-1.5 font-mono text-xs text-ink-600">
                                        min ({{ $fmt($box['min']['x'], 1) }}, {{ $fmt($box['min']['y'], 1) }}, {{ $fmt($box['min']['z'], 1) }}) sampai
                                        max ({{ $fmt($box['max']['x'], 1) }}, {{ $fmt($box['max']['y'], 1) }}, {{ $fmt($box['max']['z'], 1) }}) mm
                                    </dd>
                                </div>
                            @endif
                        </dl>

                        {{-- Pengaturan & estimasi model ini --}}
                        <div class="mt-6 rounded-xl bg-ink-50/70 p-5">
                            <p class="text-[0.65rem] font-bold uppercase tracking-[0.14em] text-ink-500">Pengaturan &amp; Estimasi</p>

                            <dl class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ([
                                    'Skala' => $item->scale_label,
                                    'Resolusi' => $item->resolution_label,
                                    'Kualitas Hasil' => $item->quality_label,
                                    'Infill' => $item->infill_label,
                                    'Finishing' => $item->finishing_label,
                                    'Support Structure' => $item->support_enabled
                                        ? 'Ya ('.($item->support_type ?: 'normal').')'
                                        : 'Tidak',
                                    'Hollow Model' => $item->hollow_label,
                                    'Volume Material' => $fmt($item->material_volume_cm3, 2).' cm³',
                                    'Berat Model' => $fmt($item->estimated_weight_g, 1).' gram',
                                    'Berat Support' => $fmt($item->support_weight_g, 1).' gram',
                                    'Total Berat / unit' => $fmt($item->total_weight_g, 1).' gram',
                                    'Estimasi Waktu' => $item->estimated_duration ?? '-',
                                    'Harga Jual' => $hargaLabel($hargaJualModel[$item->id] ?? null),
                                ] as $label => $value)
                                    <div>
                                        <dt class="text-[0.6rem] font-semibold uppercase tracking-[0.12em] text-ink-400">{{ $label }}</dt>
                                        <dd class="mt-1 text-sm font-semibold text-ink-800">{{ $value }}</dd>
                                    </div>
                                @endforeach

                                <div>
                                    <dt class="text-[0.6rem] font-semibold uppercase tracking-[0.12em] text-ink-400">Warna Material</dt>
                                    <dd class="mt-1 flex items-center gap-2 text-sm font-semibold text-ink-800">
                                        <span class="inline-block h-4 w-4 shrink-0 rounded-full border border-ink-200"
                                              style="background-color: {{ $item->material_color_hex }}"></span>
                                        {{ $item->material_color_label }}
                                    </dd>
                                </div>
                            </dl>

                        {{-- Analisis kelayakan model ini --}}
                        <div class="mt-6">
                            <p class="text-[0.65rem] font-bold uppercase tracking-[0.14em] text-ink-500">Analisis Kelayakan Cetak</p>

                            @if (filled($checks))
                                <ul class="mt-3 space-y-3">
                                    @foreach ($checks as $check)
                                        @php
                                            $dot = match ($check['status'] ?? 'skip') {
                                                'pass' => 'bg-emerald-500',
                                                'warn' => 'bg-amber-500',
                                                'fail' => 'bg-brand-600',
                                                default => 'bg-ink-300',
                                            };
                                        @endphp
                                        <li class="flex gap-3 rounded-xl border border-ink-100 bg-ink-50/60 p-4">
                                            <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $dot }}"></span>
                                            <div>
                                                <p class="text-sm font-bold text-ink-900">{{ $check['label'] ?? '-' }}</p>
                                                <p class="mt-1 text-xs leading-relaxed text-ink-500">{{ $check['message'] ?? '' }}</p>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="mt-3 text-sm text-ink-400">Tidak ada rincian analisis yang tersimpan untuk model ini.</p>
                            @endif
                        </div>

                        {{-- ===== Form Perhitungan Kalkulator Manual =====

                             Hanya muncul pada model berteknologi SLA Industries.
                             Part-nya dipesan ke vendor, jadi harganya TIDAK
                             dihitung Calculator - ditetapkan di sini dari kuotasi
                             JLC. Selama belum diisi, pelanggan melihat
                             "Menunggu Perhitungan", bukan angka.

                             INTERNAL: seluruh rincian di dalamnya - Harga JLC,
                             ongkir, bea masuk, HPP, margin - tidak pernah
                             ditampilkan kepada pelanggan. Yang sampai ke
                             pelanggan hanya Final Price. --}}
                        @if ($item->usesManualPricing())
                            @php $slaQuote = $item->slaIndustriesQuote; @endphp

                            <div class="mt-6 border-t border-ink-100 pt-6">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <h3 class="font-display text-sm font-bold text-ink-900">Form Perhitungan Kalkulator Manual</h3>
                                        <p class="mt-1 text-xs leading-relaxed text-ink-400">
                                            Isi kuotasi JLC model ini; Final Price menjadi harga penawaran resmi bagi pelanggan.
                                        </p>
                                    </div>
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-[0.6rem] font-bold uppercase tracking-[0.12em] text-amber-800">
                                        Internal &middot; Tidak Terlihat Pelanggan
                                    </span>
                                </div>

                                @if ($slaQuote === null)
                                    <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-relaxed text-amber-900">
                                        Harga model ini <span class="font-semibold">belum ditetapkan</span>. Pelanggan melihat
                                        keterangan &ldquo;Menunggu Perhitungan&rdquo;, dan penawaran belum dapat dilanjutkan ke
                                        tahap pembayaran sampai formulir ini disimpan.
                                        Nilai di bawah diambil dari parameter bawaan pada Price List.
                                    </p>
                                @else
                                    @php $slaRate = $slaQuote->usdRateInfo(); @endphp

                                    <p class="mt-4 text-xs text-ink-400">
                                        Terakhir ditetapkan
                                        {{ $slaQuote->updated_at->translatedFormat('d F Y, H:i') }} WIB
                                        @if ($slaQuote->calculatedBy)
                                            oleh {{ $slaQuote->calculatedBy->name }}
                                        @endif
                                        &middot; mengisi ulang formulir ini mengganti harganya.
                                    </p>

                                    {{-- Kurs yang benar-benar dipakai harga yang
                                         berlaku sekarang. Dicatat terpisah karena
                                         formulir di bawah memakai kurs HARI INI:
                                         menyimpan ulang berarti menghitung ulang,
                                         dan perhitungan baru memakai kurs baru. --}}
                                    <p class="mt-2 rounded-xl border border-ink-100 bg-ink-50/70 px-4 py-3 text-xs leading-relaxed text-ink-500">
                                        Harga yang berlaku sekarang dihitung dengan kurs
                                        <span class="font-mono font-semibold text-ink-800">Rp{{ number_format((float) $slaQuote->usd_rate, 0, ',', '.') }}</span>
                                        @if ($slaQuote->usd_rate_source)
                                            &middot; sumber {{ $slaQuote->usd_rate_source }}
                                        @endif
                                        @if ($slaQuote->usd_rate_published_at)
                                            &middot; terbit {{ $slaQuote->usd_rate_published_at->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i') }} WIB
                                        @endif.
                                        Menyimpan ulang formulir di bawah akan memakai kurs yang berlaku saat itu.
                                    </p>
                                @endif

                                @can(\App\Support\AdminPermission::QUOTATION_EDIT)
                                <div class="mt-4">
                                    @include('partials.sla-industries-formula', [
                                        'action' => staff_route('quotations.items.sla-industries', [$quotation, $item]),
                                        'values' => $slaQuote ?? $slaIndustriesDefaults,
                                        'uid' => 'sla-item-'.$item->id,
                                        'showProductName' => true,
                                        'productName' => $slaQuote?->product_name ?? $item->file_name,
                                        'submitLabel' => $slaQuote === null ? 'Tetapkan Harga Model Ini' : 'Perbarui Harga Model Ini',
                                        'usdRate' => $slaIndustriesUsdRate,
                                        'rateEndpoint' => $usdRateEndpoint,
                                    ])
                                </div>
                                @else
                                    <p class="mt-4 text-xs text-ink-400">Anda tidak memiliki hak akses Edit untuk menetapkan harga model ini.</p>
                                @endcan
                            </div>
                        @endif

                        {{-- Catatan admin khusus model ini. Harganya tidak dapat
                             disunting di sini: teknologi yang dicetak sendiri
                             mengikuti estimasi sistem saat permintaan dikirim,
                             dan SLA Industries memakai formulir di atas. --}}
                        <form method="POST" action="{{ staff_route('quotations.items.update', [$quotation, $item]) }}"
                              class="mt-6 grid gap-4 border-t border-ink-100 pt-6 sm:grid-cols-2">
                            @csrf
                            @method('PATCH')

                            <div>
                                <p class="block text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">
                                    Harga Model Ini
                                </p>
                                <p class="mt-2 font-display text-lg font-bold {{ ($hargaJualModel[$item->id] ?? null) === null ? 'text-amber-700' : 'text-ink-900' }}">
                                    {{ $hargaLabel($hargaJualModel[$item->id] ?? null) }}
                                </p>
                                <p class="mt-1 text-[0.65rem] text-ink-400">
                                    @if ($item->usesManualPricing())
                                        Ditetapkan tim lewat Form Perhitungan Kalkulator Manual di atas.
                                    @else
                                        Dihitung sistem dari specification model ini.
                                    @endif
                                </p>
                            </div>

                            <div>
                                <label for="item-note-{{ $item->id }}" class="block text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">
                                    Catatan Model Ini
                                </label>
                                <textarea id="item-note-{{ $item->id }}" name="admin_note" rows="2" @cannot(\App\Support\AdminPermission::QUOTATION_EDIT) disabled @endcannot
                                          class="mt-2 w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm focus:border-brand-600 focus:outline-none"
                                          placeholder="Misalnya: dinding terlalu tipis, sarankan naik ke 1,2 mm.">{{ $item->admin_note }}</textarea>
                            </div>

                            @can(\App\Support\AdminPermission::QUOTATION_EDIT)
                            <div class="sm:col-span-2">
                                <button type="submit" class="viewer-tool">Simpan Catatan Model {{ $item->position }}</button>
                            </div>
                            @endcan
                        </form>
                    </section>
                @endforeach
            </div>

            {{-- Kolom kanan --}}
            <div class="space-y-6 lg:col-span-4">

                {{-- Estimasi --}}
                <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card sm:p-7">
                    <h2 class="font-display text-base font-bold text-ink-900">Estimasi Produksi</h2>
                    <p class="mt-1 text-xs text-ink-400">Gabungan seluruh model dalam penawaran ini.</p>

                    <dl class="mt-5 space-y-4">
                        @foreach ([
                            'Printer' => $quotation->printer_summary,
                            'Jumlah Model' => $quotation->items->count().' model · 1 mesin per model',
                            'Teknologi' => $quotation->items->pluck('technology')->unique()->implode(', '),
                            'Material' => $quotation->items->pluck('material')->unique()->implode(', '),
                            'Support Structure' => $quotation->support_enabled ? 'Ada model memakai support' : 'Tidak',
                            'Volume Material' => number_format((float) $quotation->material_volume_cm3, 2, ',', '.').' cm³',
                            'Berat Model' => number_format((float) $quotation->estimated_weight_g, 1, ',', '.').' gram',
                            'Berat Support' => number_format((float) $quotation->support_weight_g, 1, ',', '.').' gram',
                            'Total Berat' => number_format($quotation->items->sum(fn ($item) => $item->total_weight_g * $item->quantity), 1, ',', '.').' gram',
                            'Estimasi Waktu' => $quotation->estimated_duration ?? '-',
                        ] as $label => $value)
                            <div class="flex items-start justify-between gap-4 border-b border-ink-100 pb-4 last:border-0 last:pb-0">
                                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-400">{{ $label }}</dt>
                                <dd class="text-right text-sm font-semibold {{ $label === 'Total Berat' ? 'text-brand-700' : 'text-ink-800' }}">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    {{-- Harga Estimasi yang dilihat pelanggan adalah Harga Jual
                         itu sendiri, jadi kartunya menyebut keduanya sekaligus
                         supaya tidak dikira dua angka yang berbeda. Angkanya
                         dibaca dari perhitungan yang sama dengan tabel Detail
                         Perhitungan Harga — lihat $hargaJualPenawaran. --}}
                    <div class="mt-5 rounded-xl {{ $menungguHarga ? 'bg-amber-600' : 'bg-brand-600' }} p-5 text-white">
                        <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-white/70">Harga Jual &middot; Harga Estimasi</p>
                        <p class="mt-1.5 font-display {{ $menungguHarga ? 'text-lg' : 'text-2xl' }} font-bold">
                            {{ $hargaLabel($menungguHarga ? null : $hargaJualPenawaran) }}
                        </p>
                        <p class="mt-1 text-xs text-white/70">
                            {{ $quotation->items->count() }} model &middot; {{ $quotation->quantity }} unit total
                        </p>
                        <p class="mt-2 border-t border-white/20 pt-2 text-[0.65rem] text-white/70">
                            @if ($menungguHarga)
                                {{-- Harga penawaran belum lengkap selama ada model SLA
                                     Industries yang kuotasi vendornya belum diisi. --}}
                                Isi dulu Form Perhitungan Kalkulator Manual pada
                                <a href="#daftar-model" class="font-semibold underline">model yang belum dihitung</a>.
                            @else
                                Subtotal + Profit + Basic Fee &middot;
                                <a href="#rincian-harga" class="font-semibold underline">lihat rinciannya</a>
                            @endif
                        </p>
                    </div>
                </section>

                {{-- Riwayat tracking --}}
                <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card sm:p-7">
                    <h2 class="font-display text-base font-bold text-ink-900">Riwayat Tracking</h2>
                    <p class="mt-1 text-xs text-ink-400">{{ $quotation->timelineHistories->count() }} entri &middot; tidak terhapus saat status berubah.</p>

                    <ol class="mt-5 space-y-3">
                        @forelse ($quotation->timelineHistories as $history)
                            <li class="rounded-xl border border-ink-100 bg-ink-50/60 p-4">
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <p class="text-sm font-bold text-ink-900">{{ $history->status_label }}</p>
                                    <p class="text-[0.65rem] text-ink-400">
                                        {{ $history->created_at->translatedFormat('d M Y, H:i') }}
                                        @if ($history->created_by) &middot; {{ $history->created_by }} @endif
                                    </p>
                                </div>
                                @if ($history->note)
                                    <p class="mt-1.5 text-xs leading-relaxed text-ink-600">{{ $history->note }}</p>
                                @endif
                            </li>
                        @empty
                            <li class="text-sm text-ink-400">Belum ada riwayat.</li>
                        @endforelse
                    </ol>
                </section>

                {{-- Tindak lanjut --}}
                <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card sm:p-7">
                    <h2 class="font-display text-base font-bold text-ink-900">Tindak Lanjut</h2>
                    <p class="mt-1 text-xs text-ink-400">
                        Berlaku untuk keseluruhan penawaran. Harga penawaran mengikuti estimasi sistem dan tidak diatur di sini;
                        catatan per model diatur di kartu masing-masing model.
                    </p>

                    @can(\App\Support\AdminPermission::QUOTATION_UPDATE_STATUS)
                    <form method="POST" action="{{ staff_route('quotations.update', $quotation) }}" class="mt-5 space-y-4" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        {{-- Status berjalan berurutan: hanya tahap sekarang dan satu tahap
                             sesudahnya yang terbuka. Tahap yang sudah dilewati tetap
                             ditampilkan sebagai jejak urutannya, tetapi ikut terkunci —
                             batasan yang sama juga dijaga di sisi server. --}}
                        <div>
                            <label for="status" class="block text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Status Tracking</label>
                            <select id="status" name="status" class="mt-2 w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm focus:border-brand-600 focus:outline-none">
                                @foreach ($statusChoices as $choice)
                                    <option value="{{ $choice['key'] }}"
                                            @disabled(! $choice['selectable'])
                                            @selected($statusDefault === $choice['key'])
                                            class="{{ $choice['selectable'] ? 'text-ink-900' : 'text-ink-300' }}">
                                        {{ $loop->iteration }}. {{ $choice['label'] }}
                                        @if ($choice['state'] === 'done') &#10003; sudah dilewati
                                        @elseif ($choice['state'] === 'current') &middot; status saat ini
                                        @elseif ($choice['state'] === 'next') &middot; tahap berikutnya
                                        @else &#128274; terkunci
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1.5 text-[0.65rem] text-ink-400">
                                @if ($statusNext)
                                    Tahap hanya dapat maju satu langkah. Setelah perubahan ini disimpan,
                                    <span class="font-semibold text-ink-600">{{ \App\Support\QuotationStatus::label($statusNext) }}</span> terbuka sebagai pilihan berikutnya.
                                @else
                                    Penawaran sudah berada pada tahap terakhir, statusnya tidak dapat dimajukan lagi.
                                @endif
                            </p>
                            @error('status')
                                <p class="mt-1.5 text-xs font-semibold text-brand-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="estimated_finish" class="block text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Estimasi Selesai</label>
                            <input type="date" id="estimated_finish" name="estimated_finish"
                                   value="{{ old('estimated_finish', $quotation->estimated_finish?->format('Y-m-d')) }}"
                                   class="mt-2 w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm focus:border-brand-600 focus:outline-none">
                            @error('estimated_finish')
                                <p class="mt-1.5 text-xs font-semibold text-brand-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="note" class="block text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">
                                Catatan untuk Pelanggan
                            </label>
                            <textarea id="note" name="note" rows="3"
                                      class="mt-2 w-full rounded-xl border border-ink-200 px-4 py-3 text-sm focus:border-brand-600 focus:outline-none"
                                      placeholder="Misalnya: Model memiliki ketebalan dinding yang terlalu tipis. Disarankan memakai material PLA."></textarea>
                            <p class="mt-1 text-[0.65rem] text-ink-400">
                                Tercatat sebagai baris riwayat baru dan langsung tampil di halaman tracking pelanggan.
                            </p>
                            @error('note')
                                <p class="mt-1.5 text-xs font-semibold text-brand-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach ([
                                'production_photo' => ['label' => 'Foto Proses Produksi', 'current' => $quotation->production_photo],
                                'result_photo' => ['label' => 'Foto Hasil Akhir', 'current' => $quotation->result_photo],
                            ] as $field => $meta)
                                <div>
                                    <label for="{{ $field }}" class="block text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">
                                        {{ $meta['label'] }} <span class="text-ink-300">(opsional)</span>
                                    </label>
                                    <input type="file" id="{{ $field }}" name="{{ $field }}" accept="image/*"
                                           class="mt-2 w-full rounded-xl border border-ink-200 px-3 py-2 text-xs file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white focus:border-brand-600 focus:outline-none">

                                    @if ($meta['current'])
                                        <a href="{{ Storage::disk('public')->url($meta['current']) }}" target="_blank" rel="noopener noreferrer"
                                           class="mt-2 inline-block text-[0.65rem] font-semibold text-brand-600 hover:text-brand-800">
                                            Lihat foto saat ini
                                        </a>
                                    @endif

                                    @error($field)
                                        <p class="mt-1.5 text-xs font-semibold text-brand-700">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>

                        <div class="border-t border-ink-100 pt-4">
                            <label for="admin_note" class="block text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">
                                Catatan Internal <span class="text-ink-300">(tidak tampil ke pelanggan)</span>
                            </label>
                            <textarea id="admin_note" name="admin_note" rows="3"
                                      class="mt-2 w-full rounded-xl border border-ink-200 px-4 py-3 text-sm focus:border-brand-600 focus:outline-none"
                                      placeholder="Misalnya: sudah dihubungi via WhatsApp, menunggu konfirmasi material.">{{ old('admin_note', $quotation->admin_note) }}</textarea>
                            @error('admin_note')
                                <p class="mt-1.5 text-xs font-semibold text-brand-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="btn-primary w-full">Simpan Perubahan</button>
                    </form>
                    @else
                        <p class="mt-5 rounded-xl border border-ink-100 bg-ink-50/70 px-4 py-3 text-xs text-ink-500">
                            Status saat ini: <span class="font-semibold text-ink-800">{{ $quotation->status_label }}</span>.
                            Anda tidak memiliki hak akses Update Status.
                        </p>
                    @endcan

                    <div class="mt-5 border-t border-ink-100 pt-5">
                        <a href="{{ route('tracking.show', $quotation->tracking_number) }}" target="_blank" rel="noopener noreferrer"
                           class="viewer-tool w-full justify-center">
                            Lihat Halaman Tracking Pelanggan
                        </a>
                    </div>

                    <div class="mt-5 flex flex-col gap-2 border-t border-ink-100 pt-5">
                        {{-- Dipakai untuk mengonfirmasi alasan pembatalan, revisi
                             desain, atau komunikasi lain dengan pelanggan. --}}
                        @if ($whatsappLink = ($quotation->user?->whatsapp_link ?? $quotation->whatsapp_link))
                            <a href="{{ $whatsappLink }}" target="_blank" rel="noopener noreferrer"
                               class="btn-primary w-full bg-emerald-600 shadow-[0_10px_30px_-12px_rgba(5,150,105,0.9)] hover:bg-emerald-700 hover:shadow-[0_18px_40px_-14px_rgba(5,150,105,0.95)]">
                                <x-icons.whatsapp class="h-4 w-4" />
                                Hubungi Pelanggan via WhatsApp
                            </a>
                        @endif

                        <a href="mailto:{{ $quotation->email }}?subject=Penawaran%20{{ $quotation->tracking_number }}" class="viewer-tool justify-center">
                            Balas via Email
                        </a>

                        @if ($quotation->user && auth()->user()->can(\App\Support\AdminPermission::USER_VIEW))
                            <a href="{{ staff_route('users.show', $quotation->user) }}" class="viewer-tool justify-center">
                                Lihat Profil &amp; Riwayat Pelanggan
                            </a>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
