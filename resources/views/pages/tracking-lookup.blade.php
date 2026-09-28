@extends('layouts.app')

@section('title', 'Tracking Penawaran')
@section('description', 'Masukkan nomor tracking untuk memantau perkembangan permintaan penawaran 3D printing Anda.')

@section('content')

    <x-page-hero
        eyebrow="Tracking Penawaran"
        current="Tracking"
        title='Pantau perkembangan <span class="text-brand-400">permintaan penawaran</span> Anda'
        description="Masukkan nomor tracking yang Anda terima setelah mengirim permintaan penawaran, atau pindai QR code pada dokumen bukti penawaran." />

    <section class="section">
        <div class="container-page">
            <div class="mx-auto max-w-xl">
                <div class="rounded-3xl border border-ink-100 bg-white p-8 shadow-card sm:p-10" data-aos="fade-up">
                    {{-- Verifikasi gagal: tetap di halaman ini dan dapat mencoba lagi. --}}
                    @error('whatsapp_last4')
                        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-semibold text-brand-800"
                             role="alert">
                            <x-icons.lock class="mt-0.5 h-4 w-4 shrink-0" />
                            <span>{{ $message }}</span>
                        </div>
                    @enderror

                    <form method="POST" action="{{ route('tracking.lookup') }}" id="tracking-lookup-form" data-tracking-form>
                        @csrf

                        <label for="tracking_number" class="field-label">Nomor Tracking</label>
                        <input type="text"
                               id="tracking_number"
                               name="tracking_number"
                               value="{{ old('tracking_number') }}"
                               class="field-input font-mono uppercase"
                               placeholder="QTN-20260729-A7K2QX"
                               autocomplete="off"
                               autofocus
                               required
                               maxlength="40">

                        @error('tracking_number')
                            <p class="field-error">{{ $message }}</p>
                        @enderror

                        <button type="submit" class="btn-primary mt-6 w-full">
                            Lacak Penawaran
                            <x-icons.arrow-right class="h-4 w-4" />
                        </button>

                    </form>

                    <div class="mt-8 border-t border-ink-100 pt-6">
                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-ink-400">Nomor tracking Anda ada di mana?</p>
                        <ul class="mt-3 space-y-2.5 text-sm text-ink-600">
                            @foreach ([
                                'Ditampilkan tepat setelah permintaan penawaran berhasil dikirim.',
                                'Tercantum pada dokumen Bukti Permintaan Penawaran (PDF) yang Anda unduh.',
                                'Dapat dibuka langsung dengan memindai QR code pada dokumen tersebut.',
                            ] as $hint)
                                <li class="flex gap-2.5">
                                    <x-icons.check class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />
                                    <span>{{ $hint }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <p class="mt-6 text-center text-sm text-ink-500" data-aos="fade-up">
                    Belum pernah mengirim permintaan?
                    <a href="{{ route('models') }}" class="font-semibold text-brand-600 transition-colors hover:text-brand-800">
                        Cek model Anda dulu di halaman 3D Models
                    </a>
                </p>
            </div>
        </div>
    </section>

            {{--
                Modal Verifikasi Penawaran.

                Berada di dalam formulir yang sama, jadi 4 digitnya terkirim
                bersama nomor tracking dan dicocokkan di SERVER
                (QuotationTrackingController::lookup). Nomor WhatsApp yang
                terdaftar tidak pernah dikirim ke halaman ini.
            --}}
            <div class="fixed inset-0 z-50 hidden items-center justify-center bg-ink-900/60 p-4 backdrop-blur-sm"
                 data-verify-modal
                 role="dialog"
                 aria-modal="true"
                 aria-labelledby="verify-title"
                 aria-describedby="verify-description">
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl sm:p-7" data-verify-dialog>
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                                <x-icons.shield class="h-5 w-5" />
                            </span>
                            <div>
                                <h3 id="verify-title" class="font-display text-lg font-bold text-ink-900">Verifikasi Penawaran</h3>
                                <p id="verify-description" class="mt-1 text-sm leading-relaxed text-ink-500">
                                    Untuk melihat detail penawaran, silakan masukkan 4 digit terakhir nomor WhatsApp
                                    yang terdaftar pada penawaran ini.
                                </p>
                            </div>
                        </div>

                        <button type="button" class="shrink-0 rounded-lg p-1.5 text-ink-400 transition-colors hover:bg-ink-50 hover:text-ink-700"
                                data-verify-cancel
                                aria-label="Tutup">
                            <x-icons.close class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="mt-5">
                        <label for="whatsapp_last4" class="field-label">4 Digit Terakhir WhatsApp</label>
                        <input type="text"
                               id="whatsapp_last4"
                               name="whatsapp_last4"
                               form="tracking-lookup-form"
                               inputmode="numeric"
                               maxlength="4"
                               autocomplete="off"
                               class="field-input text-center font-mono text-lg tracking-[0.5em]"
                               placeholder="••••"
                               data-verify-input>

                        @error('whatsapp_last4')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-6 flex flex-wrap justify-end gap-2">
                        <button type="button" class="viewer-tool" data-verify-cancel>Batal</button>
                        {{-- Menyala hanya bila isiannya tepat 4 angka. --}}
                        <button type="button"
                                class="btn-primary disabled:cursor-not-allowed disabled:opacity-40"
                                data-verify-submit disabled>Verifikasi</button>
                    </div>
                </div>
            </div>

@endsection

@push('scripts')
<script>
    /**
     * Lacak Penawaran → modal Verifikasi Penawaran lebih dulu.
     *
     * Hanya mengatur tampilannya: 4 digit dicocokkan di server. Isian dibatasi
     * angka dan tepat 4 digit supaya pengguna tidak mengirim yang pasti ditolak.
     */
    (function () {
        const form = document.querySelector('[data-tracking-form]');
        const modal = document.querySelector('[data-verify-modal]');

        if (!form || !modal) {
            return;
        }

        const dialog = modal.querySelector('[data-verify-dialog]');
        const input = modal.querySelector('[data-verify-input]');
        const submit = modal.querySelector('[data-verify-submit]');
        const trigger = form.querySelector('button[type="submit"]');
        let verifying = false;

        const valid = () => /^\d{4}$/.test(input.value);

        const setOpen = (isOpen) => {
            modal.style.display = isOpen ? 'flex' : 'none';
            document.body.style.overflow = isOpen ? 'hidden' : '';

            if (isOpen) {
                input.focus();
            } else {
                trigger?.focus();
            }
        };

        // Batal: tutup modal, tidak ada yang dikirim.
        const cancel = () => {
            input.value = '';
            submit.disabled = true;
            setOpen(false);
        };

        form.addEventListener('submit', (event) => {
            if (!verifying) {
                event.preventDefault();
                setOpen(true);
            }
        });

        // Hanya angka, paling banyak 4.
        input.addEventListener('input', () => {
            input.value = input.value.replace(/\D/g, '').slice(0, 4);
            submit.disabled = !valid();
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                submit.click();
            }
        });

        submit.addEventListener('click', () => {
            if (!valid()) {
                return;
            }

            verifying = true;
            submit.disabled = true;
            form.requestSubmit ? form.requestSubmit() : form.submit();
        });

        modal.querySelectorAll('[data-verify-cancel]').forEach((el) => el.addEventListener('click', cancel));

        modal.addEventListener('mousedown', (event) => {
            if (!dialog.contains(event.target)) {
                cancel();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal.style.display === 'flex') {
                cancel();
            }
        });

        // Verifikasi gagal, atau datang dari tautan tracking langsung:
        // modalnya dibuka kembali dengan nomor tracking yang sudah terisi.
        @if ($errors->has('whatsapp_last4') || session('tracking_verify'))
            setOpen(true);
        @endif
    })();
</script>
@endpush
