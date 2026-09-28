{{--
    Baris tombol Excel — Import, Export, Template, Contoh — beserta modal
    Import-nya. Bentuknya sama dengan baris Excel pada halaman material, dan
    dijaga hak akses yang sama (`price_list.import` / `price_list.export`);
    route-nya juga dijaga di backend.

    Diharapkan:
      $title        judul modal Import, mis. "Import Teknologi"
      $routePrefix  mis. "superadmin.price-list.technologies.excel"
--}}
@if (auth()->user()->can(\App\Support\AdminPermission::PRICE_LIST_EXPORT) || auth()->user()->can(\App\Support\AdminPermission::PRICE_LIST_IMPORT))
    <div class="mt-4 flex flex-wrap items-center gap-2 rounded-2xl border border-ink-100 bg-white px-4 py-3 shadow-card">
        <span class="mr-1 text-[0.65rem] font-bold uppercase tracking-[0.14em] text-ink-400">Excel</span>

        @can(\App\Support\AdminPermission::PRICE_LIST_IMPORT)
            <button type="button" class="viewer-tool" data-excel-open>
                <x-icons.upload class="h-4 w-4" />
                Import Excel
            </button>
        @endcan

        @can(\App\Support\AdminPermission::PRICE_LIST_EXPORT)
            <a href="{{ route($routePrefix.'.export') }}" class="viewer-tool">
                <x-icons.download class="h-4 w-4" />
                Export Excel
            </a>

            <a href="{{ route($routePrefix.'.template') }}" class="viewer-tool">
                <x-icons.download class="h-4 w-4" />
                Template Excel
            </a>

            <a href="{{ route($routePrefix.'.example') }}" class="viewer-tool">
                <x-icons.book class="h-4 w-4" />
                Contoh Excel
            </a>
        @endcan
    </div>

    @can(\App\Support\AdminPermission::PRICE_LIST_IMPORT)
        @include('superadmin.price-list.excel.import-modal', ['title' => $title, 'routePrefix' => $routePrefix])
    @endcan
@endif
