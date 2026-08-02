@php
    // Rute notifikasi berbeda antara area admin dan area pelanggan, tetapi
    // tampilan daftarnya persis sama.
    $isAdmin = auth()->user()->isAdmin();
    $readRoute = $isAdmin ? 'admin.notifications.read' : 'dashboard.notifications.read';
    $readAllRoute = $isAdmin ? 'admin.notifications.read-all' : 'dashboard.notifications.read-all';
@endphp

<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Notifikasi</h2>
        <p class="mt-2 text-sm text-ink-500">
            {{ $unreadCount > 0 ? $unreadCount.' notifikasi belum dibaca.' : 'Semua notifikasi sudah dibaca.' }}
        </p>
    </div>

    @if ($unreadCount > 0)
        <form method="POST" action="{{ route($readAllRoute) }}">
            @csrf
            <button type="submit" class="viewer-tool">Tandai semua terbaca</button>
        </form>
    @endif
</div>

<div class="mt-8 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
    <ul class="divide-y divide-ink-100">
        @forelse ($notifications as $notification)
            @php $unread = $notification->read_at === null; @endphp

            <li class="flex flex-wrap items-start gap-4 px-6 py-5 {{ $unread ? 'bg-brand-50/40' : '' }}">
                <span class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $unread ? 'bg-brand-600 text-white' : 'bg-ink-100 text-ink-500' }}">
                    <x-icons.bell class="h-5 w-5" />
                </span>

                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-ink-900">{{ $notification->data['title'] ?? 'Notifikasi' }}</p>
                    <p class="mt-1 text-sm leading-relaxed text-ink-500">{{ $notification->data['message'] ?? '' }}</p>
                    <p class="mt-1.5 text-[0.65rem] text-ink-400">
                        {{ $notification->created_at->translatedFormat('d F Y, H:i') }} WIB
                        @if (! empty($notification->data['tracking_number']))
                            &middot; <span class="font-mono">{{ $notification->data['tracking_number'] }}</span>
                        @endif
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    @if ($unread)
                        <span class="rounded-full bg-brand-600 px-2.5 py-0.5 text-[0.6rem] font-bold text-white">Baru</span>
                    @endif

                    <form method="POST" action="{{ route($readRoute, $notification->id) }}">
                        @csrf
                        <button type="submit" class="viewer-tool">
                            {{ ! empty($notification->data['url']) ? 'Buka' : 'Tandai Dibaca' }}
                        </button>
                    </form>
                </div>
            </li>
        @empty
            <li class="px-6 py-16 text-center">
                <p class="font-semibold text-ink-700">Belum ada notifikasi.</p>
                <p class="mt-1.5 text-sm text-ink-400">
                    Pemberitahuan akan muncul di sini setiap kali ada perkembangan pada penawaran.
                </p>
            </li>
        @endforelse
    </ul>
</div>

@if ($notifications->hasPages())
    <div class="mt-6">{{ $notifications->links() }}</div>
@endif
