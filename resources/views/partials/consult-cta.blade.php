{{--
    CTA melayang di kanan bawah: "Consult & Get a Quote".

    Membuka WhatsApp perusahaan dengan pesan pembuka yang sudah terisi. Bila
    nomor WhatsApp belum diatur di profil perusahaan, tombol mengarah ke
    bagian kontak halaman About.

    Teks WhatsApp otomatis per halaman — lihat App\Support\WhatsAppLink
    (@section('wa-text') / @section('wa-topic') / topik bawaan per route).

    Di ponsel tombol ini digantikan bilah sticky (partials/sticky-cta).
--}}
@php
    $consultHref = wa_link();
@endphp

<a href="{{ $consultHref }}"
   @if ($company->whatsapp_link) target="_blank" rel="noopener noreferrer" @endif
   data-consult-cta
   data-wa-link
   data-cta="floating"
   translate="no"
   aria-label="Consult & Get a Quote lewat WhatsApp"
   class="notranslate group fixed bottom-4 right-4 z-40 hidden items-center md:inline-flex gap-3 rounded-full bg-white py-3 pl-5 pr-6 text-ink-900 shadow-[0_0_0_1px_rgba(15,11,10,0.06),0_18px_40px_-14px_rgba(15,11,10,0.55)] transition-all duration-300 ease-out hover:-translate-y-0.5 hover:shadow-[0_0_0_1px_rgba(149,39,29,0.25),0_22px_44px_-14px_rgba(149,39,29,0.6)] sm:bottom-6 sm:right-6 sm:gap-3.5 sm:py-3.5 sm:pl-6 sm:pr-7">
    <x-icons.chat class="h-5 w-5 shrink-0 transition-colors duration-300 group-hover:text-brand-600 sm:h-6 sm:w-6" />

    <span class="font-mono uppercase leading-tight">
        <span class="block text-[0.62rem] font-semibold tracking-[0.22em] text-ink-400 sm:text-[0.7rem]">Consult &amp;</span>
        <span class="block text-sm font-bold tracking-[0.12em] transition-colors duration-300 group-hover:text-brand-600 sm:text-base">Get a Quote</span>
    </span>
</a>
