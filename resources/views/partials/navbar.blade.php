@php
    // Halaman 3D Models tidak lagi menjadi butir menu tersendiri, tombol
    // "Order Now" di kanan navbar sudah menuju ke sana.

    // Navbar tampil transparan di atas hero gelap dan baru memadat setelah
    // digulir. Halaman yang tidak memakai hero meminta tampilan solid sejak
    // awal lewat @section('navbar-style', 'solid'), supaya menunya tetap
    // terbaca di atas latar terang.
    $navbarSolid = trim($__env->yieldContent('navbar-style')) === 'solid';

    $menu = [
        ['label' => 'Home', 'route' => 'home'],
        ['label' => 'Services', 'route' => 'services'],
        ['label' => 'Technologies', 'route' => 'technologies'],
        // Menu dropdown; isinya diambil dari config/industries.php.
        ['label' => 'Industri', 'route' => 'industries', 'dropdown' => true],
        ['label' => 'Miniatur Teknik', 'route' => 'miniatur'],
        ['label' => 'About', 'route' => 'about'],
    ];

    // Industri unggulan yang ditampilkan di dropdown "Industri".
    $featuredIndustries = collect(config('industries'))->where('featured', true);

    // Admin diarahkan ke dashboardnya sendiri, bukan dashboard pelanggan.
    $dashboardRoute = auth()->check() && auth()->user()->isAdmin() ? 'admin.dashboard' : 'dashboard';

    // Kontak yang ditampilkan menu "Support Us"; baris kosong dilewati.
    $contacts = collect([
        [
            'icon' => 'whatsapp',
            'label' => 'WhatsApp',
            'value' => $company->whatsapp,
            // Teks otomatis per halaman (App\Support\WhatsAppLink).
            'href' => $company->whatsapp_link ? wa_link() : null,
            'external' => true,
        ],
        [
            'icon' => 'mail',
            'label' => 'Email',
            'value' => $company->email,
            'href' => $company->email ? 'mailto:'.$company->email : null,
            'external' => false,
        ],
        [
            'icon' => 'phone',
            'label' => 'Telepon',
            'value' => $company->phone,
            'href' => $company->phone_link,
            'external' => false,
        ],
        [
            'icon' => 'map-pin',
            'label' => 'Alamat Kantor',
            'value' => $company->address,
            'href' => $company->maps_url,
            'external' => true,
        ],
        [
            'icon' => 'clock',
            'label' => 'Jam Operasional',
            'value' => $company->operational_hours,
            'href' => null,
            'external' => false,
        ],
    ])->filter(fn (array $contact) => filled($contact['value']));
@endphp

<header class="site-header {{ $navbarSolid ? 'is-scrolled' : '' }}"
        data-navbar
        @if ($navbarSolid) data-navbar-solid @endif>

    {{-- ================= TOP BAR =================
         Slogan perusahaan, satu tingkat lebih kecil daripada menu navbar.
         Selalu berlatar putih sehingga tidak ikut menyesuaikan hero gelap. --}}
    <div class="border-b border-ink-100 bg-white">
        <div class="flex h-9 items-center justify-between gap-4 px-5 sm:px-8 lg:px-10">
            <p class="truncate text-[0.7rem] font-medium tracking-wide text-ink-500 sm:text-xs">
                3D Printing Service &amp; Engineering Solutions
            </p>

            @if ($company->whatsapp_link)
                <a href="{{ wa_link() }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   data-wa-link
                   data-cta="header"
                   class="hidden shrink-0 items-center gap-1.5 text-[0.7rem] font-semibold text-ink-500 transition-colors hover:text-brand-600 sm:inline-flex">
                    <x-icons.whatsapp class="h-3.5 w-3.5" />
                    {{ $company->whatsapp }}
                </a>
            @endif
        </div>
    </div>

    {{-- ================= NAVBAR =================
         Lebarnya memenuhi layar (tanpa container): logo dan menu di kiri,
         tombol aksi di kanan. --}}
    <nav class="navbar" aria-label="Navigasi utama">
        <div class="flex h-20 items-center justify-between gap-4 px-5 sm:px-8 lg:px-10">

            {{-- Kiri: logo + menu --}}
            <div class="flex min-w-0 items-center gap-6 xl:gap-8">
                <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-3" aria-label="{{ $company->name }}, kembali ke beranda">
                    <x-logo-mark class="h-11 w-11 shrink-0" />
                    <span class="brand-name notranslate font-display text-xl font-bold tracking-tight" translate="no">{{ $company->name }}</span>
                </a>

                <div class="hidden items-center gap-1 xl:flex">
                    @foreach ($menu as $item)
                        @if ($item['dropdown'] ?? false)
                            {{-- Industri: dropdown berisi daftar industri unggulan --}}
                            <div class="relative" data-industry-menu>
                                <button type="button"
                                        class="nav-link inline-flex items-center gap-1"
                                        data-industry-toggle
                                        aria-expanded="false"
                                        aria-haspopup="true"
                                        @if (request()->routeIs($item['route'], $item['route'].'.*')) aria-current="page" @endif>
                                    {{ $item['label'] }}
                                    <x-icons.chevron-down class="h-3.5 w-3.5 transition-transform duration-200" data-industry-caret />
                                </button>

                                <div class="absolute left-0 top-full z-50 mt-2 hidden w-[22rem] overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card-hover"
                                     data-industry-panel>
                                    {{-- Judul "Industri" menuju halaman ringkasan seluruh industri. --}}
                                    <a href="{{ route('industries') }}"
                                       class="group/industri flex items-center justify-between gap-3 border-b border-ink-100 bg-ink-50/70 px-5 py-3 transition-colors hover:bg-brand-50/70">
                                        <span>
                                            <span class="block text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Berdasarkan Industri</span>
                                            <span class="mt-0.5 block font-display text-sm font-bold text-ink-900 transition-colors group-hover/industri:text-brand-600">Industri</span>
                                        </span>
                                        <span class="inline-flex items-center gap-1 text-[0.7rem] font-semibold text-brand-600">
                                            Lihat semua
                                            <x-icons.arrow-right class="h-3.5 w-3.5 transition-transform duration-200 group-hover/industri:translate-x-0.5" />
                                        </span>
                                    </a>

                                    <ul class="py-2">
                                        @foreach ($featuredIndustries as $industry)
                                            <li>
                                                <a href="{{ route('industries.show', $industry['slug']) }}"
                                                   class="block px-5 py-2 text-sm font-medium leading-snug text-ink-700 transition-colors hover:bg-brand-50/60 hover:text-brand-600">
                                                    {{ $industry['name'] }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>

                                    <a href="{{ route('industries') }}#lainnya"
                                       class="flex items-center gap-1.5 border-t border-ink-100 bg-ink-50/70 px-5 py-3 font-mono text-xs font-semibold text-brand-600 transition-colors hover:text-brand-700">
                                        dan lainnya
                                        <x-icons.arrow-right class="h-3.5 w-3.5" />
                                    </a>
                                </div>
                            </div>
                        @else
                            <a href="{{ route($item['route']) }}"
                               class="nav-link"
                               @if (request()->routeIs($item['route'])) aria-current="page" @endif>
                                {{ $item['label'] }}
                            </a>
                        @endif
                    @endforeach

                    {{-- Support Us: dropdown berisi kontak perusahaan --}}
                    <div class="relative" data-support-menu>
                        <button type="button"
                                class="nav-link"
                                data-support-toggle
                                aria-expanded="false"
                                aria-haspopup="true">
                            Support Us
                        </button>

                        <div class="absolute left-0 top-full z-50 mt-2 hidden w-80 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card-hover"
                             data-support-panel>
                            <div class="border-b border-ink-100 bg-ink-50/70 px-5 py-3">
                                <p class="font-display text-sm font-bold text-ink-900">Hubungi Kami</p>
                                <p class="mt-0.5 text-[0.7rem] text-ink-500">Tim kami siap membantu kebutuhan cetak Anda.</p>
                            </div>

                            <ul class="divide-y divide-ink-100">
                                @foreach ($contacts as $contact)
                                    <li>
                                        @php $tag = $contact['href'] ? 'a' : 'div'; @endphp

                                        <{{ $tag }}
                                            @if ($contact['href'])
                                                href="{{ $contact['href'] }}"
                                                data-cta="menu"
                                                @if ($contact['external']) target="_blank" rel="noopener noreferrer" @endif
                                            @endif
                                            class="flex items-start gap-3 px-5 py-3 transition-colors {{ $contact['href'] ? 'hover:bg-brand-50/60' : '' }}">
                                            <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-600/10 text-brand-600">
                                                <x-dynamic-component :component="'icons.'.$contact['icon']" class="h-4 w-4" />
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $contact['label'] }}</span>
                                                <span class="mt-0.5 block text-sm font-semibold leading-snug text-ink-800">{{ $contact['value'] }}</span>
                                            </span>
                                        </{{ $tag }}>
                                    </li>
                                @endforeach
                            </ul>

                            <a href="{{ route('about') }}#kontak" class="block border-t border-ink-100 bg-ink-50/70 px-5 py-3 text-center text-xs font-semibold text-brand-600 transition-colors hover:text-brand-700">
                                Lihat halaman kontak lengkap
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kanan: pilihan bahasa + Order Now (outline) + Sign In (primary).
                 Bahasa Inggris memakai terjemahan otomatis Google Translate
                 (resources/js/modules/language-switcher.js). --}}
            <div class="hidden shrink-0 items-center gap-2.5 xl:flex">
                <div class="lang-switch notranslate" translate="no" role="group" aria-label="Pilih bahasa" data-lang-switch>
                    <button type="button" data-lang="id" aria-pressed="true" title="Bahasa Indonesia">ID</button>
                    <span aria-hidden="true">/</span>
                    <button type="button" data-lang="en" aria-pressed="false" title="English (terjemahan otomatis)">EN</button>
                </div>

                <a href="{{ route('models') }}" class="nav-cta-outline">Order Now</a>

                @auth
                    <a href="{{ route($dashboardRoute) }}" class="nav-cta">Dashboard</a>

                    <form method="POST" action="{{ route('logout') }}"
                          data-confirm="Apakah Anda yakin ingin keluar dari akun?"
                          data-confirm-title="Konfirmasi Logout"
                          data-confirm-accept="Ya, Logout"
                          data-confirm-cancel="Batal">
                        @csrf
                        <button type="submit" class="nav-link">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="nav-cta">Sign In</a>
                @endauth
            </div>

            {{-- Pilihan bahasa tetap terlihat di layar kecil, di samping tombol menu. --}}
            <div class="ml-auto flex shrink-0 items-center xl:hidden">
                <div class="lang-switch notranslate" translate="no" role="group" aria-label="Pilih bahasa" data-lang-switch>
                    <button type="button" data-lang="id" aria-pressed="true" title="Bahasa Indonesia">ID</button>
                    <span aria-hidden="true">/</span>
                    <button type="button" data-lang="en" aria-pressed="false" title="English (terjemahan otomatis)">EN</button>
                </div>
            </div>

            <button type="button"
                    class="nav-toggle inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl xl:hidden"
                    data-navbar-toggle
                    aria-expanded="false"
                    aria-controls="navbar-menu"
                    aria-label="Buka menu navigasi">
                <x-icons.menu class="h-6 w-6" data-icon-open />
                <x-icons.close class="hidden h-6 w-6" data-icon-close />
            </button>
        </div>
    </nav>

    {{-- ================= MENU MOBILE ================= --}}
    <div id="navbar-menu" class="hidden xl:hidden" data-navbar-menu>
        <div class="px-5 pb-5 sm:px-8">
            <div class="rounded-2xl border border-ink-100 bg-white p-3 shadow-card">
                @foreach ($menu as $item)
                    @if ($item['dropdown'] ?? false)
                        {{-- Industri dibentangkan dengan <details> seperti Support Us --}}
                        <details class="rounded-xl">
                            <summary class="nav-link-mobile cursor-pointer list-none"
                                     @if (request()->routeIs($item['route'], $item['route'].'.*')) aria-current="page" @endif>
                                {{ $item['label'] }}
                                <x-icons.chevron-down class="h-4 w-4 opacity-50" />
                            </summary>

                            <ul class="mb-2 space-y-0.5 px-2 pb-1">
                                <li class="px-2 pb-1 pt-1 text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Berdasarkan Industri</li>
                                <li>
                                    <a href="{{ route('industries') }}"
                                       class="flex items-center justify-between rounded-lg bg-ink-50 px-2 py-2 font-display text-sm font-bold text-ink-900 transition-colors hover:bg-brand-50 hover:text-brand-600">
                                        Industri
                                        <span class="inline-flex items-center gap-1 text-[0.7rem] font-semibold text-brand-600">
                                            Lihat semua
                                            <x-icons.arrow-right class="h-3.5 w-3.5" />
                                        </span>
                                    </a>
                                </li>
                                @foreach ($featuredIndustries as $industry)
                                    <li>
                                        <a href="{{ route('industries.show', $industry['slug']) }}"
                                           class="block rounded-lg px-2 py-2 text-sm font-medium leading-snug text-ink-700 transition-colors hover:bg-brand-50 hover:text-brand-600">
                                            {{ $industry['name'] }}
                                        </a>
                                    </li>
                                @endforeach
                                <li>
                                    <a href="{{ route('industries') }}#lainnya"
                                       class="inline-flex items-center gap-1.5 rounded-lg px-2 py-2 font-mono text-xs font-semibold text-brand-600">
                                        dan lainnya
                                        <x-icons.arrow-right class="h-3.5 w-3.5" />
                                    </a>
                                </li>
                            </ul>
                        </details>
                    @else
                        <a href="{{ route($item['route']) }}"
                           class="nav-link-mobile"
                           @if (request()->routeIs($item['route'])) aria-current="page" @endif>
                            {{ $item['label'] }}
                            <x-icons.arrow-right class="h-4 w-4 opacity-50" />
                        </a>
                    @endif
                @endforeach

                {{-- Support Us dibentangkan langsung di menu mobile --}}
                <details class="mt-1 rounded-xl">
                    <summary class="nav-link-mobile cursor-pointer list-none">
                        Support Us
                    </summary>

                    <ul class="mb-2 space-y-1 px-2 pb-1">
                        @foreach ($contacts as $contact)
                            <li>
                                @php $tag = $contact['href'] ? 'a' : 'div'; @endphp

                                <{{ $tag }}
                                    @if ($contact['href'])
                                        href="{{ $contact['href'] }}"
                                        data-cta="menu"
                                        @if ($contact['external']) target="_blank" rel="noopener noreferrer" @endif
                                    @endif
                                    class="flex items-start gap-3 rounded-lg px-2 py-2">
                                    <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-brand-600/10 text-brand-600">
                                        <x-dynamic-component :component="'icons.'.$contact['icon']" class="h-3.5 w-3.5" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $contact['label'] }}</span>
                                        <span class="mt-0.5 block text-sm font-semibold leading-snug text-ink-800">{{ $contact['value'] }}</span>
                                    </span>
                                </{{ $tag }}>
                            </li>
                        @endforeach
                    </ul>
                </details>

                <div class="my-2 border-t border-ink-100"></div>

                <a href="{{ route('models') }}" class="btn-outline w-full">Order Now</a>

                @auth
                    <a href="{{ route($dashboardRoute) }}" class="btn-primary mt-2 w-full">Dashboard</a>

                    <form method="POST" action="{{ route('logout') }}" class="mt-2"
                          data-confirm="Apakah Anda yakin ingin keluar dari akun?"
                          data-confirm-title="Konfirmasi Logout"
                          data-confirm-accept="Ya, Logout"
                          data-confirm-cancel="Batal">
                        @csrf
                        <button type="submit" class="nav-link-mobile w-full justify-center">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn-primary mt-2 w-full">Sign In</a>
                @endauth
            </div>
        </div>
    </div>
</header>
