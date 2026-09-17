{{--
    Tabel material satu teknologi.

    Dipakai SETIAP tab teknologi — termasuk yang baru ditambahkan Superadmin —
    sehingga menambah teknologi tidak pernah menuntut markup baru.

    Diharapkan:
      $technology  App\Models\PrintTechnology
      $materials   koleksi/paginator material milik teknologi itu
      $numbers     [id material => nomor urut di dalam kelompok mesinnya]
      $rupiah      penata angka rupiah

    Barisnya dikelompokkan per mesin (lihat App\Models\PrintMaterial::
    scopeOrderedByMachine). Nomornya TIDAK dihitung di sini melainkan diterima
    lewat $numbers, karena satu kelompok dapat terpotong paginasi — menghitung
    dari halaman yang tampil akan mengulang dari 1 di halaman berikutnya.
--}}
@php
    $tab = $technology->tabKey();

    /*
     * SLA Industries tidak menjual materialnya per gram: partnya dipesan ke
     * vendor dan harganya ditetapkan tim per penawaran. Lima kolom harga karena
     * itu tidak ditampilkan untuk teknologi ini — kolom yang selalu berisi Rp0
     * bukan informasi, melainkan sumber salah baca.
     *
     * Teknologinya juga dipanggil dengan NAMA, bukan kode: "SLAI" hanya
     * singkatan teknis yang tidak dipakai siapa pun dalam percakapan.
     */
    $showsPrice = ! $technology->isSlaIndustries();
    $label = $technology->isSlaIndustries() ? $technology->name : $technology->code;

    // Material SLA/MJF/SLM memilih sendiri metode harganya (Otomatis/Manual).
    $showsPricingMethod = \App\Support\PricingMethod::appliesTo($technology->code);

    // SLA tidak punya kolom harga sendiri, jadi Harga/10 gram ditambahkan di
    // sebelah metodenya; MJF/SLM sudah menampilkan lima kolom harganya.
    $showsPricingPrice = $showsPricingMethod && ! $showsPrice;

    // Banyaknya kolom tabel, dipakai colspan judul kelompok & baris kosong.
    // +1 untuk kolom Status (switch aktif/nonaktif).
    $columns = ($showsPrice ? 11 : 6) + ($showsPricingMethod ? 1 : 0) + ($showsPricingPrice ? 1 : 0) + 1;
@endphp

<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h3 class="font-display text-lg font-bold text-ink-900">Material {{ $label }}</h3>
        <p class="mt-1 text-xs text-ink-400">
            {{ $technology->name }}{{ $technology->family ? ' · '.$technology->family : '' }} &middot;
            {{ $materials->total() }} material
        </p>
    </div>

    <div class="flex flex-wrap gap-2">
        <a href="{{ route('superadmin.price-list.technologies.edit', $technology) }}" class="viewer-tool">Parameter Teknologi</a>
        <a href="{{ route('superadmin.price-list.materials.create', $technology) }}" class="btn-primary">Tambah Material {{ $label }}</a>
    </div>
</div>

{{-- Penghapusan massal. Formulirnya sengaja DI LUAR tabel: tiap baris sudah
     punya formulir hapus satuannya sendiri, dan formulir bersarang bukan HTML
     yang sah. Kotak centangnya dikaitkan lewat atribut `form`. --}}
<form method="POST" id="{{ $tab }}-bulk-delete"
      action="{{ route('superadmin.price-list.materials.destroy-many', $technology) }}"
      data-bulk-form="{{ $tab }}"
      data-bulk-noun="material {{ $label }}">
    @csrf
    @method('DELETE')
</form>

<div class="mt-4 hidden flex-wrap items-center justify-between gap-3 rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3"
     data-bulk-bar="{{ $tab }}">
    <p class="text-sm font-semibold text-brand-700">
        <span data-bulk-count="{{ $tab }}">0</span> material {{ $label }} dipilih
    </p>

    <div class="flex flex-wrap gap-2">
        <button type="button" class="viewer-tool" data-bulk-clear="{{ $tab }}">Batalkan Pilihan</button>
        <button type="submit" form="{{ $tab }}-bulk-delete"
                class="viewer-tool border-brand-300 text-brand-700 hover:border-brand-600 hover:bg-brand-50">
            Hapus Terpilih
        </button>
    </div>
</div>

<div class="mt-4 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
    <div class="overflow-x-auto">
        <table class="w-full {{ $showsPrice ? 'min-w-[1000px]' : 'min-w-[640px]' }} text-left text-sm">
            <thead>
                <tr class="border-b border-ink-100 bg-ink-50/80 text-[0.65rem] uppercase tracking-[0.14em] text-ink-500">
                    <th scope="col" class="w-10 px-4 py-4">
                        <input type="checkbox"
                               class="h-4 w-4 rounded border-ink-300 text-brand-600 focus:ring-brand-600"
                               data-bulk-all="{{ $tab }}"
                               aria-label="Pilih seluruh material {{ $label }} di halaman ini">
                    </th>
                    <th scope="col" class="px-4 py-4 font-bold">No</th>
                    <th scope="col" class="px-4 py-4 font-bold">Material</th>
                    <th scope="col" class="px-4 py-4 font-bold">Brand</th>
                    @if ($showsPrice)
                        <th scope="col" class="px-4 py-4 text-right font-bold">Harga Beli</th>
                        <th scope="col" class="px-4 py-4 text-right font-bold">Harga/gram</th>
                        <th scope="col" class="px-4 py-4 text-right font-bold">Harga Jual</th>
                        <th scope="col" class="px-4 py-4 text-right font-bold">Pembulatan</th>
                        <th scope="col" class="px-4 py-4 text-right font-bold">Harga/10 gram</th>
                    @endif
                    @if ($showsPricingMethod)
                        <th scope="col" class="px-4 py-4 font-bold">Metode Harga</th>
                        @if ($showsPricingPrice)
                            <th scope="col" class="px-4 py-4 text-right font-bold">Harga/10 gram</th>
                        @endif
                    @endif
                    <th scope="col" class="px-4 py-4 font-bold">Remark</th>
                    <th scope="col" class="px-4 py-4 font-bold">Status</th>
                    <th scope="col" class="px-4 py-4 text-right font-bold">Aksi</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-ink-100">
                @forelse ($materials->getCollection()->groupBy(fn ($material) => $material->machine_cost_id ?? 0) as $rows)
                    @php $machine = $rows->first()->machine; @endphp

                    <tr class="bg-ink-50/70">
                        <th colspan="{{ $columns }}" scope="colgroup" class="px-4 py-2.5 text-left">
                            <span class="text-[0.7rem] font-bold uppercase tracking-[0.14em] text-ink-700">
                                {{ $machine?->mesin ?? 'Tanpa Mesin' }}
                            </span>
                            <span class="ml-2 text-[0.65rem] font-medium normal-case tracking-normal text-ink-400">
                                @if ($machine)
                                    {{ $machine->technology?->code ?? 'Tanpa teknologi' }} &middot; {{ $rows->count() }} material
                                @else
                                    Belum ditentukan mesinnya &middot; {{ $rows->count() }} material
                                @endif
                            </span>
                        </th>
                    </tr>

                    @foreach ($rows as $material)
                    <tr class="transition-colors hover:bg-brand-50/40" data-bulk-row="{{ $tab }}">
                        <td class="px-4 py-3">
                            <input type="checkbox" name="ids[]" value="{{ $material->id }}"
                                   form="{{ $tab }}-bulk-delete"
                                   class="h-4 w-4 rounded border-ink-300 text-brand-600 focus:ring-brand-600"
                                   data-bulk-item="{{ $tab }}"
                                   aria-label="Pilih {{ $material->material }}">
                        </td>
                        <td class="px-4 py-3 text-ink-500">{{ $numbers[$material->id] ?? $loop->iteration }}</td>
                        <td class="px-4 py-3 font-semibold text-ink-900">{{ $material->material }}</td>
                        <td class="px-4 py-3 text-ink-600">{{ $material->brand }}</td>
                        @if ($showsPrice)
                            <td class="px-4 py-3 text-right text-ink-700">{{ $rupiah($material->purchase_price) }}</td>
                            <td class="px-4 py-3 text-right text-ink-700">{{ $rupiah($material->price_per_gram) }}</td>
                            <td class="px-4 py-3 text-right text-ink-700">{{ $rupiah($material->sale_price) }}</td>
                            <td class="px-4 py-3 text-right text-ink-700">{{ $rupiah($material->rounded_price) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-brand-700">{{ $rupiah($material->price_per_10_gram) }}</td>
                        @endif
                        @if ($showsPricingMethod)
                            <td class="px-4 py-3">
                                <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-0.5 text-[0.65rem] font-bold {{ $material->usesAutomaticPricing() ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $material->pricing_method_label }}
                                </span>
                            </td>
                            @if ($showsPricingPrice)
                                <td class="px-4 py-3 text-right {{ $material->usesAutomaticPricing() ? 'font-semibold text-brand-700' : 'text-ink-400' }}">
                                    {{ $material->usesAutomaticPricing() ? $rupiah($material->price_per_10_gram) : '-' }}
                                </td>
                            @endif
                        @endif
                        <td class="px-4 py-3 text-ink-500">{{ $material->remark ?: '-' }}</td>
                        <td class="px-4 py-3">
                            {{-- Switch Status: aktif = tampil di Edit Specification.
                                 Formulir terkirim begitu switch diubah. --}}
                            <form method="POST" action="{{ route('superadmin.price-list.materials.status', [$technology, $material]) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_active" value="0">

                                <label class="inline-flex cursor-pointer items-center gap-2.5"
                                       title="{{ $material->isOffered() ? 'Aktif: tampil di Edit Specification' : 'Nonaktif: tidak tampil di Edit Specification' }}">
                                    <input type="checkbox" role="switch" name="is_active" value="1"
                                           class="peer sr-only" data-auto-submit
                                           aria-label="Status material {{ $material->material }}"
                                           @checked($material->isOffered())>
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
                                <a href="{{ route('superadmin.price-list.materials.edit', [$technology, $material]) }}" class="viewer-tool">Ubah</a>
                                <form method="POST" action="{{ route('superadmin.price-list.materials.destroy', [$technology, $material]) }}"
                                      onsubmit="return confirm('Hapus material {{ $material->material }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="viewer-tool text-brand-600">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="{{ $columns }}" class="px-4 py-12 text-center text-ink-400">
                            Belum ada material {{ $label }}.
                            <a href="{{ route('superadmin.price-list.materials.create', $technology) }}" class="font-semibold text-brand-600 hover:text-brand-700">Tambahkan sekarang</a>
                            agar teknologi ini dapat dipilih pelanggan di Edit Specification.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($materials->hasPages())
    <div class="mt-4">{{ $materials->links() }}</div>
@endif
