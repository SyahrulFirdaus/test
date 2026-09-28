@props([
    // id <dialog>; tombol pembukanya memakai atribut data-dialog-open berisi id ini.
    'id',
    // Label kecil di atas judul, mis. "Detail Perhitungan Harga".
    'eyebrow' => null,
    'title',
    // Kelas pembungkus isi yang dapat digulir.
    'bodyClass' => '',
    // Lebar maksimal kotak modal.
    'size' => 'max-w-3xl',
    // Langsung terbuka saat halaman dimuat, mis. setelah validasi gagal.
    'autoOpen' => false,
])

{{--
    Modal halaman Admin/Superadmin berbasis <dialog> asli.

    Dipakai Detail Perhitungan Harga, Pengaturan & Estimasi, dan Analisis
    Kelayakan Cetak pada Detail Penawaran: satu bentuk, satu perilaku. Isinya
    hanya ditampilkan — tidak ada data yang dihitung atau disimpan dari sini.

    Menutup: tombol ✕, tombol Tutup, Esc, atau klik di luar kotak. Tautan
    jangkar (#...) di dalamnya menutup modal lebih dulu lalu menggulir.

    Slot:
      $meta   baris keterangan di bawah judul (opsional)
      $slot   isi modal
--}}
<dialog id="{{ $id }}"
        aria-labelledby="{{ $id }}-title"
        {{ $attributes->merge(['class' => 'm-auto w-[calc(100%-1.5rem)] '.$size.' overflow-hidden rounded-2xl border border-ink-100 bg-white p-0 text-left shadow-2xl backdrop:bg-ink-900/60 backdrop:backdrop-blur-sm']) }}
        @if ($autoOpen) data-dialog-autoopen @endif
        data-dialog>
    <div class="flex max-h-[90vh] flex-col">
        <div class="flex items-start justify-between gap-4 border-b border-ink-100 px-5 py-4 sm:px-6">
            <div class="min-w-0">
                @if ($eyebrow)
                    <p class="text-[0.6rem] font-semibold uppercase tracking-[0.12em] text-ink-400">{{ $eyebrow }}</p>
                @endif
                <h4 id="{{ $id }}-title" class="mt-0.5 break-words font-display text-base font-bold text-ink-900">{{ $title }}</h4>
                @isset($meta)
                    <p class="mt-0.5 break-words text-[0.65rem] text-ink-400">{{ $meta }}</p>
                @endisset
            </div>

            <button type="button" class="shrink-0 rounded-lg p-1.5 text-ink-400 transition-colors hover:bg-ink-50 hover:text-ink-700"
                    data-dialog-close aria-label="Tutup">
                <x-icons.close class="h-5 w-5" />
            </button>
        </div>

        <div class="flex-1 overflow-y-auto {{ $bodyClass }}">
            {{ $slot }}
        </div>

        <div class="flex justify-end border-t border-ink-100 bg-white px-5 py-3 sm:px-6">
            <button type="button" class="btn-outline px-5 py-2" data-dialog-close>Tutup</button>
        </div>
    </div>
</dialog>

@once
    @push('scripts')
        <script>
            // Modal Admin (<dialog>): buka lewat [data-dialog-open], tutup lewat
            // [data-dialog-close], klik latar, Esc, atau tautan jangkar di dalamnya.
            (() => {
                document.querySelectorAll('[data-dialog-open]').forEach((button) => {
                    const dialog = document.getElementById(button.dataset.dialogOpen);

                    if (!dialog || typeof dialog.showModal !== 'function') {
                        return;
                    }

                    button.addEventListener('click', () => {
                        dialog.showModal();
                        document.body.style.overflow = 'hidden';
                    });
                });

                document.querySelectorAll('dialog[data-dialog]').forEach((dialog) => {
                    dialog.addEventListener('close', () => {
                        document.body.style.overflow = '';
                    });

                    dialog.querySelectorAll('[data-dialog-close]').forEach((el) => {
                        el.addEventListener('click', () => dialog.close());
                    });

                    dialog.addEventListener('click', (event) => {
                        if (event.target === dialog) {
                            dialog.close();
                        }
                    });

                    dialog.querySelectorAll('a[href^="#"]').forEach((link) => {
                        link.addEventListener('click', () => dialog.close());
                    });

                    // Mis. formulir di dalamnya baru saja ditolak validasi:
                    // dibuka kembali supaya pesan kesalahannya terlihat.
                    if (dialog.hasAttribute('data-dialog-autoopen') && typeof dialog.showModal === 'function') {
                        dialog.showModal();
                        document.body.style.overflow = 'hidden';
                    }
                });
            })();
        </script>
    @endpush
@endonce
