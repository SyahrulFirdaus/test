@props(['status'])

@php
    $presentation = match ($status) {
        \App\Models\QuotationRequest::ANALYSIS_READY => ['🟢 Ready to Print', 'border-emerald-200 bg-emerald-50 text-emerald-700'],
        \App\Models\QuotationRequest::ANALYSIS_NOT_PRINTABLE => ['🔴 Not Printable', 'border-brand-200 bg-brand-50 text-brand-700'],
        default => ['🟡 Need Improvement', 'border-amber-200 bg-amber-50 text-amber-700'],
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-3 py-1 text-xs font-bold {$presentation[1]}"]) }}>
    {{ $presentation[0] }}
</span>
