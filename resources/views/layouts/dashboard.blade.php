@php
    /**
     * Kerangka dashboard yang dipakai bersama admin dan pelanggan.
     *
     * Menu sidebar disusun di sini mengikuti role akun, sehingga halaman-halaman
     * di dalamnya cukup mengisi @section('content') tanpa mengurus navigasi.
     */
    $user = auth()->user();
    $isAdmin = $user->isAdmin();
    $isSuperAdmin = $user->isSuperAdmin();
    $isBusiness = ! $isAdmin && $user->isBusiness();

    /*
     * Menu pelanggan dibedakan menurut tipe akunnya.
     *
     * Personal mendapat menu seperlunya — penawaran, pembayaran, profil —
     * sedangkan Business mendapat menu yang lebih lengkap mengikuti alur kerja
     * perusahaan: quotation, produksi, payment term, dokumen, dan data
     * perusahaannya.
     *
     * Beberapa menu Business menunjuk bagian pada dashboard itu sendiri
     * (`anchor`) alih-alih halaman tersendiri, karena isinya memang tinggal di
     * sana — lebih baik menunjuk ke tempat yang benar-benar ada daripada
     * menyediakan halaman kosong.
     */
    $customerMenu = $isBusiness
        ? [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'grid', 'active' => 'dashboard'],
            ['label' => '3D Models', 'route' => 'models', 'icon' => 'cube', 'active' => 'models'],
            ['label' => 'Quotations', 'route' => 'dashboard.quotations.index', 'icon' => 'layers', 'active' => 'dashboard.quotations.*', 'unless_status' => true],
            // "Orders" adalah daftar penawaran yang sama, disaring pada tahap
            // produksi — di sistem ini pesanan memang penawaran yang sudah
            // dibayar, bukan entitas tersendiri.
            ['label' => 'Orders', 'route' => 'dashboard.quotations.index', 'params' => ['status' => \App\Support\QuotationStatus::PRODUCTION], 'icon' => 'printer', 'active' => 'dashboard.quotations.index', 'only_status' => \App\Support\QuotationStatus::PRODUCTION],
            ['label' => 'Payment Terms', 'route' => 'dashboard', 'anchor' => 'payment-terms', 'icon' => 'clock', 'active' => 'dashboard.payment-terms'],
            ['label' => 'Production', 'route' => 'dashboard', 'anchor' => 'production', 'icon' => 'scan', 'active' => 'dashboard.production'],
            ['label' => 'Documents', 'route' => 'dashboard', 'anchor' => 'documents', 'icon' => 'download', 'active' => 'dashboard.documents'],
            ['label' => 'Re-order', 'route' => 'dashboard', 'anchor' => 'reorder', 'icon' => 'reset', 'active' => 'dashboard.reorder'],
            ['label' => 'Company Profile', 'route' => 'dashboard.company-profile.edit', 'icon' => 'layers', 'active' => 'dashboard.company-profile.*'],
            ['label' => 'Profil Akun', 'route' => 'dashboard.profile.edit', 'icon' => 'user', 'active' => 'dashboard.profile.*'],
            ['label' => 'Alamat', 'route' => 'dashboard.addresses.index', 'icon' => 'map-pin', 'active' => 'dashboard.addresses.*'],
            ['label' => 'Notifications', 'route' => 'dashboard.notifications.index', 'icon' => 'bell', 'active' => 'dashboard.notifications.*'],
            ['label' => 'Ganti Password', 'route' => 'dashboard.password.edit', 'icon' => 'lock', 'active' => 'dashboard.password.*'],
        ]
        : [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'grid', 'active' => 'dashboard'],
            ['label' => '3D Models', 'route' => 'models', 'icon' => 'cube', 'active' => 'models'],
            ['label' => 'Penawaran Saya', 'route' => 'dashboard.quotations.index', 'icon' => 'layers', 'active' => 'dashboard.quotations.*'],
            ['label' => 'Alamat', 'route' => 'dashboard.addresses.index', 'icon' => 'map-pin', 'active' => 'dashboard.addresses.*'],
            ['label' => 'Notifikasi', 'route' => 'dashboard.notifications.index', 'icon' => 'bell', 'active' => 'dashboard.notifications.*'],
            ['label' => 'Profil', 'route' => 'dashboard.profile.edit', 'icon' => 'user', 'active' => 'dashboard.profile.*'],
            ['label' => 'Ganti Password', 'route' => 'dashboard.password.edit', 'icon' => 'lock', 'active' => 'dashboard.password.*'],
        ];

    /*
     * Menu pengelola.
     *
     * Admin memegang operasional harian; Superadmin memegang semua itu ditambah
     * pengelolaan sistem — Price List, User, Activity Log, dan Akun Admin.
     *
     * Keduanya punya WILAYAH ALAMAT sendiri: menu yang sama tersedia sebagai
     * /admin/permintaan maupun /superadmin/permintaan, jadi seluruh tautan di
     * sini dibangun dari awalan wilayahnya. Dengan begitu Superadmin yang
     * membuka Penawaran tetap berada di /superadmin sampai selesai.
     *
     * Pembatasan yang sebenarnya bukan di sini: /superadmin/* dijaga middleware
     * `superadmin`, sehingga menyembunyikan menu bukan satu-satunya lapisan.
     */
    $area = $isSuperAdmin ? 'superadmin.' : 'admin.';

    $staffMenu = [
        ['label' => 'Dashboard', 'route' => $area.'dashboard', 'icon' => 'grid', 'active' => $area.'dashboard'],
        ['label' => 'Penawaran', 'route' => $area.'quotations.index', 'icon' => 'layers', 'active' => $area.'quotations.*'],
        ['label' => 'Verifikasi Pembayaran', 'route' => $area.'payments.index', 'icon' => 'check', 'active' => $area.'payments.*'],
        ['label' => 'Payment Terms', 'route' => $area.'payment-terms.index', 'icon' => 'layers', 'active' => $area.'payment-terms.*'],
        ['label' => 'Notifikasi', 'route' => $area.'notifications.index', 'icon' => 'bell', 'active' => $area.'notifications.*'],
        ['label' => 'Profil', 'route' => $area.'profile.edit', 'icon' => 'user', 'active' => $area.'profile.*'],
        ['label' => 'Ganti Password', 'route' => $area.'password.edit', 'icon' => 'lock', 'active' => $area.'password.*'],
    ];

    if ($isSuperAdmin) {
        // Menu milik Superadmin disisipkan sebelum Notifikasi agar urutannya
        // mengikuti struktur yang disepakati; Akun Admin menutup daftar.
        array_splice($staffMenu, 4, 0, [
            ['label' => 'Price List', 'route' => 'superadmin.price-list.index', 'icon' => 'tag', 'active' => 'superadmin.price-list.*'],
            ['label' => 'User', 'route' => 'superadmin.users.index', 'icon' => 'users', 'active' => 'superadmin.users.*'],
            ['label' => 'Activity Log', 'route' => 'superadmin.activity-logs.index', 'icon' => 'shield', 'active' => 'superadmin.activity-logs.*'],
        ]);

        $staffMenu[] = ['label' => 'Akun Admin', 'route' => 'superadmin.admins.index', 'icon' => 'users', 'active' => 'superadmin.admins.*'];
    }

    $menu = $isAdmin ? $staffMenu : $customerMenu;

    $unreadNotifications = $user->unreadNotifications()->latest()->limit(8)->get();
    $unreadCount = $user->unreadNotifications()->count();

    $latestEndpoint = route($isAdmin ? $area.'notifications.latest' : 'dashboard.notifications.latest');
    $notificationsIndex = route($isAdmin ? $area.'notifications.index' : 'dashboard.notifications.index');
    $readAllRoute = route($isAdmin ? $area.'notifications.read-all' : 'dashboard.notifications.read-all');
    $logoutRoute = $isAdmin ? route('admin.logout') : route('logout');
@endphp

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Tema dipasang sebelum apa pun digambar supaya halaman tidak sempat
         berkedip putih dulu saat pengguna memilih mode gelap. Karena itu
         skripnya inline di <head>, bukan di bundel yang dimuat belakangan. --}}
    <script>
        (function () {
            try {
                var saved = localStorage.getItem("nusama-theme");
                var dark = saved ? saved === "dark" : window.matchMedia("(prefers-color-scheme: dark)").matches;

                if (dark) {
                    document.documentElement.setAttribute("data-theme", "dark");
                }
            } catch (e) {
                /* Mode penyamaran atau penyimpanan diblokir: tetap terang. */
            }
        })();
    </script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', 'Dashboard') &middot; {{ $isSuperAdmin ? 'Superadmin' : ($isAdmin ? 'Admin' : 'Akun') }} {{ $company->name }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/dashboard.js'])
</head>
<body class="min-h-screen bg-ink-50">

    {{-- Latar gelap saat sidebar dibuka di layar kecil --}}
    <div class="fixed inset-0 z-40 hidden bg-ink-950/50 lg:hidden" data-sidebar-backdrop></div>

    {{-- ================= SIDEBAR ================= --}}
    <aside class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-ink-100 bg-white transition-transform duration-300 ease-out lg:translate-x-0"
           data-sidebar>
        <div class="flex h-16 shrink-0 items-center gap-3 border-b border-ink-100 px-5">
            <x-logo-mark class="h-9 w-9 shrink-0" />
            <span class="flex min-w-0 flex-col leading-tight">
                <span class="truncate font-display text-sm font-bold text-ink-900">{{ $company->name }}</span>
                <span class="text-[0.6rem] font-semibold uppercase tracking-[0.18em] text-brand-600">
                    {{ $isAdmin ? 'Dashboard Admin' : ($isBusiness ? 'Business Account' : 'Dashboard Akun') }}
                </span>
            </span>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto p-4" aria-label="Navigasi dashboard">
            @foreach ($menu as $item)
                @php
                    $isActive = request()->routeIs($item['active']);

                    /*
                     * "Quotations" dan "Orders" menunjuk halaman yang sama dengan
                     * penyaring berbeda, jadi keduanya dibedakan lewat penyaring
                     * status yang sedang aktif — bukan hanya nama route-nya.
                     */
                    if ($isActive && isset($item['only_status'])) {
                        $isActive = request()->query('status') === $item['only_status'];
                    } elseif ($isActive && ($item['unless_status'] ?? false)) {
                        $isActive = request()->query('status') !== \App\Support\QuotationStatus::PRODUCTION;
                    }

                    // Menu dapat menunjuk halaman lain, halaman yang sama dengan
                    // penyaring, atau satu bagian pada dashboard.
                    $href = route($item['route'], $item['params'] ?? [])
                        .(isset($item['anchor']) ? '#'.$item['anchor'] : '');
                @endphp
                <a href="{{ $href }}"
                   class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-colors
                          {{ $isActive ? 'bg-brand-600 text-white shadow-[0_10px_24px_-14px_rgba(149,39,29,0.95)]' : 'text-ink-600 hover:bg-brand-50 hover:text-brand-700' }}"
                   @if ($isActive) aria-current="page" @endif>
                    <x-dynamic-component :component="'icons.'.$item['icon']" class="h-5 w-5 shrink-0" />
                    <span class="flex-1">{{ $item['label'] }}</span>

                    @if ($item['icon'] === 'bell' && $unreadCount > 0)
                        <span class="inline-flex min-w-[1.5rem] justify-center rounded-full px-2 py-0.5 text-[0.65rem] font-bold
                                     {{ $isActive ? 'bg-white text-brand-700' : 'bg-brand-600 text-white' }}">
                            {{ $unreadCount }}
                        </span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="shrink-0 border-t border-ink-100 p-4">
            <a href="{{ route('home') }}" class="viewer-tool w-full justify-center">Lihat Website</a>
        </div>
    </aside>

    {{-- ================= KONTEN ================= --}}
    <div class="lg:pl-72">

        <header class="sticky top-0 z-30 border-b border-ink-100 bg-white/90 backdrop-blur">
            <div class="flex h-16 items-center justify-between gap-3 px-5 sm:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-ink-200 text-ink-700 lg:hidden"
                            data-sidebar-toggle
                            aria-expanded="false"
                            aria-label="Buka menu dashboard">
                        <x-icons.menu class="h-5 w-5" />
                    </button>

                    <h1 class="truncate font-display text-base font-bold text-ink-900 sm:text-lg">@yield('title', 'Dashboard')</h1>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    {{-- Mode gelap. Pilihannya milik perangkat ini saja —
                         disimpan di localStorage, tidak ikut ke akun — jadi
                         satu akun dapat tampil berbeda di laptop dan di ponsel. --}}
                    <button type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-ink-200 text-ink-600 transition-colors hover:border-brand-600 hover:text-brand-700"
                            data-theme-toggle
                            aria-pressed="false"
                            aria-label="Aktifkan mode gelap"
                            title="Aktifkan mode gelap">
                        <x-icons.sun class="h-5 w-5" data-theme-icon="light" />
                        <x-icons.moon class="hidden h-5 w-5" data-theme-icon="dark" />
                    </button>

                    {{-- Ikon lonceng: jumlah yang belum dibaca diperbarui berkala
                         oleh resources/js/dashboard.js --}}
                    <div class="relative"
                         data-notification-bell="{{ $latestEndpoint }}"
                         data-seen="{{ $unreadNotifications->pluck('id')->implode(',') }}">
                        <button type="button"
                                class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-ink-200 text-ink-700 transition-colors hover:border-brand-300 hover:text-brand-600"
                                data-bell-toggle
                                aria-label="Notifikasi">
                            <x-icons.bell class="h-5 w-5" />
                            <span class="absolute -right-1.5 -top-1.5 inline-flex min-w-[1.25rem] justify-center rounded-full bg-brand-600 px-1.5 py-0.5 text-[0.6rem] font-bold text-white {{ $unreadCount ? '' : 'hidden' }}"
                                  data-bell-badge>{{ $unreadCount }}</span>
                        </button>

                        <div class="absolute right-0 z-40 mt-2 hidden w-80 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card-hover sm:w-96"
                             data-bell-panel>
                            <div class="flex items-center justify-between border-b border-ink-100 bg-ink-50/70 px-4 py-3">
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Notifikasi</p>

                                <form method="POST" action="{{ $readAllRoute }}">
                                    @csrf
                                    <button type="submit" class="text-xs font-semibold text-brand-600 hover:text-brand-700">Tandai terbaca</button>
                                </form>
                            </div>

                            <div class="max-h-80 overflow-y-auto" data-bell-list>
                                @forelse ($unreadNotifications as $notification)
                                    <a href="{{ $notification->data['url'] ?? $notificationsIndex }}"
                                       class="block border-b border-ink-100 px-4 py-3 transition-colors last:border-0 hover:bg-brand-50/50">
                                        <p class="text-sm font-bold text-ink-900">{{ $notification->data['title'] ?? 'Notifikasi' }}</p>
                                        <p class="mt-0.5 text-xs leading-relaxed text-ink-500">{{ $notification->data['message'] ?? '' }}</p>
                                        <p class="mt-1 text-[0.65rem] text-ink-400">{{ $notification->created_at->diffForHumans() }}</p>
                                    </a>
                                @empty
                                    <p class="px-4 py-6 text-center text-sm text-ink-400">Tidak ada notifikasi baru.</p>
                                @endforelse
                            </div>

                            <a href="{{ $notificationsIndex }}" class="block border-t border-ink-100 bg-ink-50/70 px-4 py-3 text-center text-xs font-semibold text-brand-600 hover:text-brand-700">
                                Lihat semua notifikasi
                            </a>
                        </div>
                    </div>

                    <span class="hidden items-center gap-2.5 rounded-xl border border-ink-200 px-3 py-2 sm:flex">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-brand-600 text-[0.65rem] font-bold text-white">
                            {{ $user->initials }}
                        </span>
                        <span class="max-w-[10rem] truncate text-sm font-semibold text-ink-700">{{ $user->name }}</span>
                    </span>

                    <form method="POST" action="{{ $logoutRoute }}">
                        @csrf
                        <button type="submit" class="viewer-tool">Logout</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="px-5 py-8 sm:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-xl border border-brand-200 bg-brand-50 px-5 py-4 text-sm font-semibold text-brand-800">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-brand-200 bg-brand-50 px-5 py-4 text-sm text-brand-800">
                    <p class="font-semibold">Periksa kembali isian berikut:</p>
                    <ul class="mt-2 list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    {{-- Popup notifikasi real-time --}}
    <div class="pointer-events-none fixed bottom-6 right-6 z-[60] flex flex-col gap-3" data-notification-toasts></div>

    @stack('scripts')

</body>
</html>
