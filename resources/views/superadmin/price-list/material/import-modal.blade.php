{{--
    Modal "Import Material".

    Hanya mengunggah berkasnya; tidak ada satu baris pun yang tersimpan dari
    sini. Yang muncul berikutnya adalah halaman pratinjau — di situlah pengelola
    melihat apa yang akan terjadi dan memutuskan.

    Diharapkan:
      $technology  App\Models\PrintTechnology
      $label       nama teknologi pada teks, mis. "FDM"
--}}
<div class="fixed inset-0 z-50 hidden items-center justify-center bg-ink-900/60 p-4 backdrop-blur-sm"
     data-excel-modal
     role="dialog"
     aria-modal="true"
     aria-labelledby="excel-import-title">

    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl sm:p-7" data-excel-dialog>
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 id="excel-import-title" class="font-display text-lg font-bold text-ink-900">
                    Import Material {{ $label }}
                </h3>
                <p class="mt-1 text-sm text-ink-500">Upload file Excel (.xlsx)</p>
            </div>

            <button type="button" class="shrink-0 rounded-lg p-1.5 text-ink-400 transition-colors hover:bg-ink-50 hover:text-ink-700"
                    data-excel-close
                    aria-label="Tutup">
                <x-icons.close class="h-5 w-5" />
            </button>
        </div>

        <form method="POST"
              action="{{ route('superadmin.price-list.materials.excel.preview', $technology) }}"
              enctype="multipart/form-data"
              class="mt-5"
              data-excel-form>
            @csrf

            <label class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-ink-200 px-6 py-8 text-center transition-colors hover:border-brand-400 hover:bg-brand-50/40"
                   for="excel-file">
                <x-icons.upload class="h-7 w-7 text-ink-400" />
                <span class="text-sm font-semibold text-ink-700">Pilih File</span>
                <span class="text-xs text-ink-400">Format .xlsx, maksimal 5 MB</span>

                <input type="file" id="excel-file" name="file" accept=".xlsx" required class="sr-only" data-excel-input>
            </label>

            <p class="mt-3 text-sm text-ink-500">
                File yang dipilih:
                <span class="font-semibold text-ink-800" data-excel-filename>belum ada</span>
            </p>

            @error('file') <p class="field-error">{{ $message }}</p> @enderror

            <div class="mt-5 flex flex-wrap gap-2 border-t border-ink-100 pt-4">
                <a href="{{ route('superadmin.price-list.materials.excel.template', $technology) }}" class="viewer-tool">
                    <x-icons.download class="h-4 w-4" />
                    Download Template
                </a>
                <a href="{{ route('superadmin.price-list.materials.excel.example', $technology) }}" class="viewer-tool">
                    <x-icons.book class="h-4 w-4" />
                    Download Contoh
                </a>
            </div>

            <div class="mt-6 flex flex-wrap justify-end gap-2">
                <button type="button" class="viewer-tool" data-excel-close>Batal</button>
                {{-- Terlihat mati selama belum ada berkas: tombol yang tampak
                     menyala tetapi tidak bereaksi terbaca sebagai halaman rusak. --}}
                <button type="submit"
                        class="btn-primary disabled:cursor-not-allowed disabled:opacity-40"
                        data-excel-submit disabled>Import</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    /**
     * Modal Import Excel.
     *
     * Tombol Import baru menyala setelah ada berkas yang dipilih — mengirim
     * formulir kosong hanya menghasilkan pesan kesalahan yang dapat dihindari
     * sejak di halaman.
     */
    (function () {
        const modal = document.querySelector('[data-excel-modal]');
        const open = document.querySelector('[data-excel-open]');

        if (!modal || !open) {
            return;
        }

        const input = modal.querySelector('[data-excel-input]');
        const filename = modal.querySelector('[data-excel-filename]');
        const submit = modal.querySelector('[data-excel-submit]');
        const dialog = modal.querySelector('[data-excel-dialog]');

        const setOpen = (isOpen) => {
            modal.style.display = isOpen ? 'flex' : 'none';
            document.body.style.overflow = isOpen ? 'hidden' : '';

            if (isOpen) {
                input.focus();
            } else {
                open.focus();
            }
        };

        open.addEventListener('click', () => setOpen(true));
        modal.querySelectorAll('[data-excel-close]').forEach((el) => el.addEventListener('click', () => setOpen(false)));

        // Klik di luar kotaknya menutup modal; klik di dalam tidak.
        modal.addEventListener('mousedown', (event) => {
            if (!dialog.contains(event.target)) {
                setOpen(false);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal.style.display === 'flex') {
                setOpen(false);
            }
        });

        input.addEventListener('change', () => {
            const file = input.files?.[0] ?? null;

            filename.textContent = file ? file.name : 'belum ada';
            submit.disabled = !file;
        });

        // Pesan kesalahan berkas berarti percobaan sebelumnya ditolak; modalnya
        // dibuka kembali supaya pengelola tidak perlu mencarinya lagi.
        @error('file') setOpen(true); @enderror
    })();
</script>
@endpush
