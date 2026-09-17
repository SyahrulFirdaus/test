@extends('superadmin.price-list.layout')

{{-- Rumus Harga Manual: dahulu "Rumus Harga SLA" di halaman Teknologi SLA.
     Isinya parameter BAWAAN Form Perhitungan Kalkulator Manual, dipakai
     material SLA/MJF/SLM yang memilih Kalkulator Manual. --}}

@section('title', 'Price List · Rumus Harga Manual')
@section('price-list-group', 'Harga')
@section('price-list-page', 'Rumus Harga Manual')

@section('price-list')
    <section>
        <p class="max-w-2xl text-sm leading-relaxed text-ink-500">
            Nilai <span class="font-semibold text-ink-700">bawaan</span> yang mengisi Form Perhitungan
            tiap model dengan Kalkulator Manual (SLA, MJF, dan SLM) saat tim membukanya pertama kali di Detail Penawaran.
            Mengubahnya di sini tidak menggeser harga penawaran yang sudah ditetapkan.
        </p>

        <div class="mt-5">
            @include('partials.sla-industries-formula', [
                'action' => route('superadmin.price-list.sla-industries.update'),
                'values' => $slaIndustriesFormula,
                'uid' => 'sla-default',
                'submitLabel' => 'Simpan Rumus',
                'usdRate' => $slaIndustriesUsdRate,
                'rateEndpoint' => $usdRateEndpoint,
            ])
        </div>
    </section>
@endsection
