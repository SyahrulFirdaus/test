@php
    // Halaman 3D Models tidak lagi menjadi butir menu tersendiri — tombol
    // "Order Now" di kanan navbar sudah menuju ke sana.
    $menu = [
        ['label' => 'Home', 'route' => 'home'],
        ['label' => 'Services', 'route' => 'services'],
        ['label' => 'Technologies', 'route' => 'technologies'],
        ['label' => 'About', 'route' => 'about'],
    ];

    // Admin diarahkan ke dashboardnya sendiri, bukan dashboard pelanggan.
    $dashboardRoute = auth()->check() && auth()->user()->isAdmin() ? 'admin.dashboard' : 'dashboard';

    // Kontak yang ditampilkan menu "Support Us"; baris kosong dilewati.
    $contacts = collect([
        [
            'icon' => 'whatsapp',
            'label' => 'WhatsApp',
            'value' => $company->whatsapp,
            'href' => $company->whatsapp_link,
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
            'href' => $company->phone ? 'tel:'.preg_replace('/\s+/', '', $company->phone) : null,
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

<header class="site-header" data-navbar>

    {{-- ================= TOP BAR =================
         Slogan perusahaan, satu tingkat lebih kecil daripada menu navbar.
         Selalu berlatar putih sehingga tidak ikut menyesuaikan hero gelap. --}}
    <div class="border-b border-ink-100 bg-white">
        <div class="flex h-9 items-center justify-between gap-4 px-5 sm:px-8 lg:px-10">
            <p class="truncate text-[0.7rem] font-medium tracking-wide text-ink-500 sm:text-xs">
                3D Printing Service &amp; Engineering Solutions
            </p>

            @if ($company->whatsapp_link)
                <a href="{{ $company->whatsapp_link }}"
                   target="_blank"
                   rel="noopener noreferrer"
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
                <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-3" aria-label="{{ $company->name }} — kembali ke beranda">
                    <x-logo-mark class="h-11 w-11 shrink-0" />
                    <span class="brand-name font-display text-xl font-bold tracking-tight">{{ $company->name }}</span>
                </a>

                <div class="hidden items-center gap-1 lg:flex">
                    @foreach ($menu as $item)
                        <a href="{{ route($item['route']) }}"
                           class="nav-link"
                           @if (request()->routeIs($item['route'])) aria-current="page" @endif>
                            {{ $item['label'] }}
                        </a>
                    @endforeach

                    {{-- Support Us: dropdown berisi kontak perusahaan --}}
                    <div class="relative" data-support-menu>
                        <button type="button"
                                class="nav-link inline-flex items-center gap-1.5"
                                data-support-toggle
                                aria-expanded="false"
                                aria-haspopup="true">
                            Support Us
                            <x-icons.arrow-down class="h-3.5 w-3.5 transition-transform duration-200" data-support-caret />
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

            {{-- Kanan: Order Now (outline) + Sign In (primary) --}}
            <div class="hidden shrink-0 items-center gap-2.5 lg:flex">
                <a href="{{ route('models') }}" class="nav-cta-outline">Order Now</a>

                @auth
                    <a href="{{ route($dashboardRoute) }}" class="nav-cta">Dashboard</a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="nav-link">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="nav-cta">Sign In</a>
                @endauth
            </div>

            <button type="button"
                    class="nav-toggle inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl lg:hidden"
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
    <div id="navbar-menu" class="hidden lg:hidden" data-navbar-menu>
        <div class="px-5 pb-5 sm:px-8">
            <div class="rounded-2xl border border-ink-100 bg-white p-3 shadow-card">
                @foreach ($menu as $item)
                    <a href="{{ route($item['route']) }}"
                       class="nav-link-mobile"
                       @if (request()->routeIs($item['route'])) aria-current="page" @endif>
                        {{ $item['label'] }}
                        <x-icons.arrow-right class="h-4 w-4 opacity-50" />
                    </a>
                @endforeach

                {{-- Support Us dibentangkan langsung di menu mobile --}}
                <details class="mt-1 rounded-xl">
                    <summary class="nav-link-mobile cursor-pointer list-none">
                        Support Us
                        <x-icons.arrow-down class="h-4 w-4 opacity-50" />
                    </summary>

                    <ul class="mb-2 space-y-1 px-2 pb-1">
                        @foreach ($contacts as $contact)
                            <li>
                                @php $tag = $contact['href'] ? 'a' : 'div'; @endphp

                                <{{ $tag }}
                                    @if ($contact['href'])
                                        href="{{ $contact['href'] }}"
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

                    <form method="POST" action="{{ route('logout') }}" class="mt-2">
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
