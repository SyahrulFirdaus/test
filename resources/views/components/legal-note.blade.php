{{--
    Catatan persetujuan di bawah formulir, menautkan Syarat & Ketentuan dan
    Kebijakan Privasi. `action` melengkapi kalimatnya, mis. "mendaftar".
--}}
@props(['action' => 'melanjutkan'])

<p {{ $attributes->merge(['class' => 'text-xs leading-relaxed text-ink-400']) }} data-legal-note>
    Dengan {{ $action }}, Anda menyetujui
    <a href="{{ route('legal.terms') }}" target="_blank" class="font-semibold text-ink-500 underline decoration-ink-300 underline-offset-2 hover:text-brand-600">Syarat &amp; Ketentuan</a>
    serta
    <a href="{{ route('legal.privacy') }}" target="_blank" class="font-semibold text-ink-500 underline decoration-ink-300 underline-offset-2 hover:text-brand-600">Kebijakan Privasi</a>
    kami.
</p>
