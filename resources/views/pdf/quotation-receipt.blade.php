{{--
    Bukti Permintaan Penawaran (PDF).

    Dirender dompdf, jadi tata letaknya memakai tabel dan properti CSS sederhana
    saja — flexbox dan grid tidak didukung. Logo dan QR code disematkan sebagai
    data URI SVG karena PHP di lingkungan ini tidak memuat ekstensi GD.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bukti Permintaan Penawaran {{ $quotation->tracking_number }}</title>
    <style>
        @page { margin: 0; }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5px;
            color: #2c2523;
            line-height: 1.5;
        }

        .page { padding: 0 34px 34px; }

        /* ---------- header ---------- */
        .masthead {
            background-color: #95271d;
            color: #ffffff;
            padding: 24px 34px 20px;
        }
        .masthead td { vertical-align: middle; }
        .brand-name { font-size: 20px; font-weight: bold; letter-spacing: 0.4px; }
        .brand-tagline { font-size: 8.5px; text-transform: uppercase; letter-spacing: 1.6px; color: #f5c8c0; }
        .doc-title { font-size: 12.5px; font-weight: bold; text-align: right; }
        .doc-sub { font-size: 8.5px; text-align: right; color: #f5c8c0; text-transform: uppercase; letter-spacing: 1.2px; }

        /* ---------- pita nomor tracking ---------- */
        .tracking-band {
            background-color: #2c2523;
            color: #ffffff;
            padding: 11px 34px;
        }
        .tracking-band td { vertical-align: middle; }
        .tracking-label { font-size: 8px; text-transform: uppercase; letter-spacing: 1.4px; color: #bfb1ac; }
        .tracking-number { font-size: 16px; font-weight: bold; letter-spacing: 1.2px; }
        .status-pill {
            background-color: #ffffff;
            color: #95271d;
            font-size: 9px;
            font-weight: bold;
            padding: 5px 12px;
            border-radius: 9px;
        }

        /* ---------- bagian ---------- */
        h2.section {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #95271d;
            border-bottom: 1.6px solid #95271d;
            padding-bottom: 5px;
            margin: 22px 0 10px;
        }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th,
        table.data td {
            text-align: left;
            padding: 6px 8px;
            border-bottom: 1px solid #efe9e7;
            vertical-align: top;
        }
        table.data th {
            width: 33%;
            font-weight: normal;
            color: #776862;
            font-size: 9.5px;
        }
        table.data td { font-weight: bold; }
        table.data tr.alt th, table.data tr.alt td { background-color: #f8f5f4; }

        /* Daftar model: kolomnya dibuat rata sesuai lebar masing-masing, bukan
           dua kolom label/nilai seperti tabel data lainnya. */
        table.items th {
            width: auto;
            font-weight: bold;
            color: #95271d;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border-bottom: 1.2px solid #e3d9d6;
        }
        table.items td.num, table.items th.num { text-align: right; }
        table.items tfoot td {
            border-top: 1.4px solid #95271d;
            border-bottom: 0;
            background-color: #f8f5f4;
        }

        /* ---------- QR ---------- */
        .qr-box {
            border: 1.4px solid #efe9e7;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }
        .qr-box img { width: 118px; height: 118px; }
        .qr-caption { font-size: 8px; color: #776862; margin-top: 6px; line-height: 1.4; }

        /* ---------- catatan ---------- */
        .note {
            margin-top: 20px;
            background-color: #fdf4f2;
            border-left: 3.5px solid #95271d;
            padding: 12px 14px;
            font-size: 9.5px;
            color: #611913;
        }

        .footer {
            margin-top: 24px;
            border-top: 1px solid #efe9e7;
            padding-top: 10px;
            font-size: 8.5px;
            color: #97867f;
        }
        .footer strong { color: #5d514c; }
    </style>
</head>
<body>

    {{-- ===================== HEADER ===================== --}}
    <table class="masthead" width="100%">
        <tr>
            <td width="52">
                <img src="{{ $logo }}" alt="" width="44" height="44">
            </td>
            <td>
                <div class="brand-name">{{ $company->name }}</div>
                <div class="brand-tagline">{{ $company->tagline }}</div>
            </td>
            <td>
                <div class="doc-title">Bukti Permintaan Penawaran</div>
                <div class="doc-sub">3D Printing</div>
            </td>
        </tr>
    </table>

    {{-- ===================== NOMOR TRACKING ===================== --}}
    <table class="tracking-band" width="100%">
        <tr>
            <td>
                <div class="tracking-label">Nomor Penawaran / Nomor Tracking</div>
                <div class="tracking-number">{{ $quotation->tracking_number }}</div>
            </td>
            <td align="right">
                <span class="status-pill">{{ $quotation->status_label }}</span>
            </td>
        </tr>
    </table>

    <div class="page">

        {{-- ===================== PELANGGAN ===================== --}}
        <h2 class="section">Informasi Pelanggan</h2>
        <table class="data">
            <tr>
                <th>Nama</th>
                <td>{{ $quotation->name }}</td>
            </tr>
            <tr class="alt">
                <th>Email</th>
                <td>{{ $quotation->email }}</td>
            </tr>
            <tr>
                <th>Nomor WhatsApp</th>
                <td>{{ $quotation->whatsapp }}</td>
            </tr>
            @if ($quotation->company)
                <tr class="alt">
                    <th>Nama Perusahaan</th>
                    <td>{{ $quotation->company }}</td>
                </tr>
            @endif
        </table>

        {{-- ===================== DAFTAR MODEL ===================== --}}
        <h2 class="section">Daftar Printer &amp; Model ({{ $quotation->items->count() }})</h2>
        <table class="data items">
            <thead>
                <tr>
                    <th style="width: 7%;">Printer</th>
                    <th style="width: 27%;">Nama File</th>
                    <th style="width: 24%;">Mesin, Teknologi &amp; Material</th>
                    <th style="width: 11%;">Resolusi</th>
                    <th style="width: 9%;" class="num">Jumlah</th>
                    <th style="width: 10%;" class="num">Berat</th>
                    <th style="width: 12%;" class="num">Estimasi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($quotation->items as $item)
                    <tr class="{{ $loop->even ? 'alt' : '' }}">
                        <td>{{ $item->position }}</td>
                        <td>
                            {{ $item->file_name }}
                            @unless ($item->isOriginalScale())
                                <br><span style="color: #776862; font-weight: normal;">skala {{ $item->scale_label }}</span>
                            @endunless
                        </td>
                        @php
                            $extras = collect(['infill '.$item->infill_label, strtolower($item->finishing_label)])
                                ->when($item->support_enabled, fn ($list) => $list->push('support'))
                                ->when($item->hollow_enabled, fn ($list) => $list->push('hollow'))
                                ->implode(', ');
                        @endphp
                        <td>
                            {{ $item->printer_name }}
                            <br><span style="color: #776862; font-weight: normal;">
                                {{ $item->technology }} &middot; {{ $item->material }} &middot; {{ $extras }}
                            </span>
                        </td>
                        <td>{{ $item->resolution_label }}</td>
                        <td class="num">{{ $item->quantity }} unit</td>
                        <td class="num">{{ number_format($item->total_weight_g * $item->quantity, 1, ',', '.') }} gr</td>
                        <td class="num">Rp{{ number_format((float) $item->display_price, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-transform: uppercase; letter-spacing: 1px; font-size: 8.5px;">Total</td>
                    <td class="num">{{ $quotation->quantity }} unit</td>
                    <td class="num">
                        {{ number_format($quotation->items->sum(fn ($item) => $item->total_weight_g * $item->quantity), 1, ',', '.') }} gr
                    </td>
                    <td class="num">
                        @if ($quotation->display_price !== null)
                            Rp{{ number_format($quotation->display_price, 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            </tfoot>
        </table>


        {{-- ===================== PENAWARAN + QR ===================== --}}
        <h2 class="section">Informasi Penawaran</h2>
        <table width="100%">
            <tr>
                <td width="63%" style="vertical-align: top; padding-right: 16px;">
                    <table class="data">
                        <tr>
                            <th>Nomor Tracking</th>
                            <td>{{ $quotation->tracking_number }}</td>
                        </tr>
                        <tr class="alt">
                            <th>Tanggal Pengajuan</th>
                            <td>{{ $quotation->created_at->translatedFormat('d F Y, H:i') }} WIB</td>
                        </tr>
                        <tr>
                            <th>Printer</th>
                            <td>{{ $quotation->printer_summary }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>{{ $quotation->status_label }}</td>
                        </tr>
                        <tr class="alt">
                            <th>Estimasi Biaya</th>
                            <td>
                                @if ($quotation->display_price !== null)
                                    Rp{{ number_format($quotation->display_price, 0, ',', '.') }}
                                @else
                                    Belum tersedia
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Estimasi Lead Time</th>
                            <td>{{ $quotation->lead_time ?? 'Belum tersedia' }}</td>
                        </tr>
                        @if ($quotation->estimated_finish)
                            <tr class="alt">
                                <th>Estimasi Selesai</th>
                                <td>{{ $quotation->estimated_finish->translatedFormat('d F Y') }}</td>
                            </tr>
                        @endif
                    </table>
                </td>
                <td width="37%" style="vertical-align: top;">
                    <div class="qr-box">
                        <img src="{{ $qrCode }}" alt="QR code halaman tracking">
                        <div class="qr-caption">
                            Pindai untuk membuka<br>halaman Tracking Penawaran
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        {{-- ===================== CATATAN ===================== --}}
        <div class="note">
            Simpan dokumen ini sebagai bukti permintaan penawaran. Gunakan Nomor Tracking
            untuk memantau perkembangan proses penawaran.
            <br><br>
            Halaman tracking: <strong>{{ $trackingUrl }}</strong>
        </div>

        <div class="footer">
            <strong>{{ $company->legal_name ?: $company->name }}</strong><br>
            {{ $company->address }}, {{ $company->city }}<br>
            {{ $company->phone }} &middot; {{ $company->email }}
            <br><br>
            Dokumen ini dibuat otomatis oleh sistem pada
            {{ now()->translatedFormat('d F Y, H:i') }} WIB dan bukan merupakan penawaran final.
            Estimasi biaya dan waktu dapat berubah setelah tim kami meninjau file Anda.
        </div>
    </div>

</body>
</html>
