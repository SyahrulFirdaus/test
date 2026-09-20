{{--
    Modal konfirmasi yang dipakai bersama.

    Satu markup untuk seluruh aksi yang perlu ditanya lebih dulu — membatalkan
    penawaran, keluar dari akun, dan aksi berbahaya lain yang menyusul. Isinya
    (judul, pesan, label tombol) ditulis oleh pemanggilnya lewat atribut
    `data-confirm-*`, jadi menambah aksi baru tidak menuntut markup baru.

    Dipasang sekali per layout. Perilakunya ada di
    resources/js/modules/confirm-dialog.js.
--}}
<div class="fixed inset-0 z-[70] hidden items-center justify-center bg-ink-900/60 p-4 backdrop-blur-sm"
     data-confirm-dialog
     role="dialog"
     aria-modal="true"
     aria-labelledby="confirm-dialog-title"
     aria-describedby="confirm-dialog-message">

    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl sm:p-7" data-confirm-panel>
        <h3 id="confirm-dialog-title"
            class="font-display text-lg font-bold text-ink-900"
            data-confirm-title>Konfirmasi</h3>

        <p id="confirm-dialog-message"
           class="mt-2 text-sm leading-relaxed text-ink-500"
           data-confirm-message></p>

        <div class="mt-6 flex flex-wrap justify-end gap-2 border-t border-ink-100 pt-5">
            {{-- Batal lebih dulu dan menjadi fokus awal: aksi yang paling aman
                 adalah yang paling mudah dijangkau, termasuk lewat Enter. --}}
            <button type="button" class="viewer-tool" data-confirm-cancel>Batal</button>

            <button type="button"
                    class="btn-primary px-5 py-2.5"
                    data-confirm-accept>Ya, Lanjutkan</button>
        </div>
    </div>
</div>
