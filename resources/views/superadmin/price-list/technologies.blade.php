@extends('superadmin.price-list.layout')

@section('title', 'Price List · Teknologi')
@section('price-list-group', 'Teknologi')
@section('price-list-page', 'Teknologi')

@section('price-list')
    @php $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.'); @endphp

    {{-- ================= TEKNOLOGI ================= --}}
    <section>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h3 class="font-display text-lg font-bold text-ink-900">Teknologi Cetak</h3>
                <p class="mt-1.5 text-sm text-ink-500">
                    Menambah teknologi di sini langsung memunculkan halaman materialnya sendiri di menu Teknologi &amp; Material,
                    pilihan Technology pada Edit Specification, dan baris parameternya pada halaman Rumus Harga Otomatis.
                </p>
            </div>

            <a href="{{ route('superadmin.price-list.technologies.create') }}" class="btn-primary">Tambah Teknologi</a>
        </div>

        {{-- Import & Export Excel: alur yang sama dengan material. --}}
        @include('superadmin.price-list.excel.toolbar', [
            'title' => 'Import Teknologi',
            'routePrefix' => 'superadmin.price-list.technologies.excel',
        ])

        <div class="mt-4 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-ink-100 bg-ink-50/80 text-[0.65rem] uppercase tracking-[0.14em] text-ink-500">
                            <th scope="col" class="px-4 py-4 font-bold">Kode</th>
                            <th scope="col" class="px-4 py-4 font-bold">Nama</th>
                            <th scope="col" class="px-4 py-4 font-bold">Keluarga</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Material</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-4 font-bold">Dipakai Oleh</th>
                            <th scope="col" class="px-4 py-4 font-bold">Area Cetak</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Tarif Mesin</th>
                            <th scope="col" class="px-4 py-4 font-bold">Status</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-ink-100">
                        @forelse ($technologies as $technology)
                            <tr class="transition-colors hover:bg-brand-50/40 {{ $technology->is_active ? '' : 'bg-ink-50/60' }}">
                                <td class="px-4 py-3 font-mono font-bold {{ $technology->is_active ? 'text-brand-700' : 'text-ink-400' }}">{{ $technology->code }}</td>
                                <td class="px-4 py-3 font-semibold text-ink-900">{{ $technology->name }}</td>
                                <td class="px-4 py-3 text-ink-600">{{ $technology->family ?: '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if ($technology->materials_count > 0)
                                        <span class="font-semibold text-ink-800">{{ $technology->materials_count }}</span>
                                    @else
                                        {{-- Tanpa material, teknologinya tidak dapat dipilih pelanggan. --}}
                                        <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-[0.1em] text-amber-800">
                                            Belum ada
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    {{-- Penawaran yang menahan teknologi ini dari penghapusan;
                                         daftarnya dibuka di modal. --}}
                                    @php $usedBy = $usage[$technology->code] ?? []; @endphp
                                    @if ($usedBy === [])
                                        <span class="whitespace-nowrap text-xs text-ink-400">Belum dipakai</span>
                                    @else
                                        <button type="button" class="inline-flex items-center whitespace-nowrap rounded-full border border-ink-200 bg-white px-2.5 py-1 text-[0.7rem] font-semibold text-ink-700 transition-colors hover:border-brand-400 hover:text-brand-700"
                                                data-dialog-open="usage-{{ $technology->id }}"
                                                aria-haspopup="dialog" aria-controls="usage-{{ $technology->id }}">
                                            {{ count($usedBy) }} penawaran
                                        </button>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-ink-600">
                                    {{ $technology->build_volume_x }} × {{ $technology->build_volume_y }} × {{ $technology->build_volume_z }} mm
                                </td>
                                <td class="px-4 py-3 text-right text-ink-700">{{ $rupiah($technology->machine_rate_per_hour) }}/jam</td>
                                <td class="px-4 py-3">
                                    {{-- Switch Status: aktif = tampil di Edit Specification.
                                         Formulir terkirim begitu switch diubah. --}}
                                    <form method="POST" action="{{ route('superadmin.price-list.technologies.status', $technology) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="0">

                                        <label class="inline-flex cursor-pointer items-center gap-2.5"
                                               title="{{ $technology->is_active ? 'Aktif: tampil di Edit Specification' : 'Nonaktif: tidak tampil di Edit Specification' }}">
                                            <input type="checkbox" role="switch" name="is_active" value="1"
                                                   class="peer sr-only" data-auto-submit
                                                   aria-label="Status teknologi {{ $technology->code }}"
                                                   @checked($technology->is_active)>
                                            <span class="relative h-6 w-11 shrink-0 rounded-full bg-ink-200 transition-colors
                                                         peer-checked:bg-brand-600 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-600
                                                         after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-transform
                                                         peer-checked:after:translate-x-5"></span>
                                            <span class="w-16 text-[0.65rem] font-bold uppercase tracking-[0.1em] text-ink-400 peer-checked:hidden">Nonaktif</span>
                                            <span class="hidden w-16 text-[0.65rem] font-bold uppercase tracking-[0.1em] text-emerald-700 peer-checked:inline">Aktif</span>
                                        </label>
                                    </form>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('superadmin.price-list.technologies.edit', $technology) }}" class="viewer-tool">Ubah</a>

                                        @if ($technology->isInUse())
                                            {{-- Penawaran lama menunjuk teknologinya lewat kode, jadi
                                                 tombolnya dimatikan — penjaganya juga ada di controller. --}}
                                            <span class="viewer-tool cursor-not-allowed opacity-50"
                                                  title="Sudah dipakai penawaran, tidak dapat dihapus">Hapus</span>
                                        @else
                                            <form method="POST" action="{{ route('superadmin.price-list.technologies.destroy', $technology) }}"
                                                  onsubmit="return confirm('Hapus teknologi {{ $technology->code }} beserta {{ $technology->materials_count }} materialnya? Tindakan ini tidak dapat dibatalkan.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="viewer-tool text-brand-600">Hapus</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-12 text-center text-ink-400">
                                    Belum ada teknologi.
                                    <a href="{{ route('superadmin.price-list.technologies.create') }}" class="font-semibold text-brand-600 hover:text-brand-700">Tambahkan sekarang</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{-- ================= Modal "Dipakai oleh" =================
             Daftar penawaran yang memakai tiap teknologi. Menghapus penawaran
             di sini memakai aksi hapus penawaran yang sudah ada
             (QuotationRequestController::destroy) — berkas, riwayat, dan Activity
             Log-nya sama persis — lalu kembali ke halaman ini. Setelah tidak ada
             lagi yang memakainya, tombol Hapus teknologi menyala. --}}
        @foreach ($technologies as $technology)
            @continue (empty($usage[$technology->code] ?? []))

            <x-admin.modal id="usage-{{ $technology->id }}" eyebrow="Dipakai Oleh"
                           :title="$technology->code.' · '.$technology->name" body-class="p-5 sm:p-6">
                <x-slot:meta>
                    {{ count($usage[$technology->code]) }} penawaran memakai teknologi ini, jadi teknologinya belum dapat dihapus.
                    Hapus penawarannya lebih dulu bila memang tidak dibutuhkan lagi.
                </x-slot:meta>

                <ul class="divide-y divide-ink-100 rounded-xl border border-ink-100">
                    @foreach ($usage[$technology->code] as $entry)
                        @php $quotation = $entry['quotation']; @endphp
                        <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                            <div class="min-w-0">
                                <a href="{{ staff_route('quotations.show', $quotation) }}"
                                   class="font-mono text-sm font-bold text-brand-700 hover:text-brand-800">{{ $quotation->tracking_number }}</a>
                                <p class="mt-0.5 text-xs text-ink-600">
                                    {{ $quotation->name }} &middot; {{ \App\Support\QuotationStatus::label($quotation->status) }}
                                    &middot; {{ $quotation->created_at->translatedFormat('d M Y') }}
                                </p>
                                <p class="mt-0.5 text-[0.65rem] text-ink-400">
                                    {{ $entry['models'] }} model {{ $technology->code }}
                                    @if ($entry['others'] !== [])
                                        &middot; juga memakai {{ implode(', ', $entry['others']) }}
                                    @endif
                                </p>
                            </div>

                            @can(\App\Support\AdminPermission::QUOTATION_DELETE)
                                <form method="POST" action="{{ staff_route('quotations.destroy', $quotation) }}"
                                      data-quotation-delete
                                      data-tracking="{{ $quotation->tracking_number }}"
                                      data-others="{{ implode(', ', $entry['others']) }}">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="return_to" value="technologies">
                                    <button type="submit" class="viewer-tool border-brand-200 text-brand-700 hover:border-brand-600 hover:bg-brand-50">
                                        Hapus
                                    </button>
                                </form>
                            @endcan
                        </li>
                    @endforeach
                </ul>
            </x-admin.modal>
        @endforeach

        {{-- Konfirmasi hapus penawaran. Sengaja <dialog> tersendiri: modal
             daftar di atas berada di top layer, jadi hanya dialog lain yang
             dapat tampil di atasnya. --}}
        <dialog id="quotation-delete-confirm"
                aria-labelledby="quotation-delete-confirm-title"
                class="m-auto w-[calc(100%-1.5rem)] max-w-md rounded-2xl border border-ink-100 bg-white p-6 text-left shadow-2xl backdrop:bg-ink-900/60">
            <h3 id="quotation-delete-confirm-title" class="font-display text-lg font-bold text-ink-900">Hapus Penawaran?</h3>
            <p class="mt-2 text-sm leading-relaxed text-ink-500" data-confirm-text></p>
            <div class="mt-6 flex flex-wrap justify-end gap-2 border-t border-ink-100 pt-5">
                <button type="button" class="viewer-tool" data-confirm-no>Batal</button>
                <button type="button" class="btn-primary px-5 py-2.5" data-confirm-yes>Ya, Hapus</button>
            </div>
        </dialog>
    </section>
@endsection

@push('scripts')
    <script>
        // Hapus penawaran dari modal "Dipakai oleh": tanyakan dulu, baru kirim.
        (() => {
            const confirmDialog = document.getElementById('quotation-delete-confirm');

            if (!confirmDialog || typeof confirmDialog.showModal !== 'function') {
                return;
            }

            const text = confirmDialog.querySelector('[data-confirm-text]');
            let pendingForm = null;

            document.querySelectorAll('form[data-quotation-delete]').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    if (form.dataset.confirmed === 'true') {
                        return;
                    }

                    event.preventDefault();
                    pendingForm = form;

                    const others = form.dataset.others;
                    text.textContent = `Penawaran ${form.dataset.tracking} beserta seluruh berkas dan riwayatnya akan dihapus permanen dan tidak dapat dikembalikan.`
                        + (others ? ` Penawaran ini juga memakai ${others}.` : '');

                    confirmDialog.showModal();
                    confirmDialog.querySelector('[data-confirm-no]').focus();
                });
            });

            confirmDialog.querySelector('[data-confirm-no]').addEventListener('click', () => {
                pendingForm = null;
                confirmDialog.close();
            });

            confirmDialog.addEventListener('click', (event) => {
                if (event.target === confirmDialog) {
                    pendingForm = null;
                    confirmDialog.close();
                }
            });

            confirmDialog.querySelector('[data-confirm-yes]').addEventListener('click', () => {
                if (!pendingForm) {
                    return;
                }

                const form = pendingForm;
                pendingForm = null;
                confirmDialog.close();
                form.dataset.confirmed = 'true';
                form.requestSubmit ? form.requestSubmit() : form.submit();
            });
        })();
    </script>
@endpush
