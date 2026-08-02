<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Halaman notifikasi yang dipakai bersama dashboard admin dan dashboard user.
 *
 * Keduanya membaca dari relasi `notifications` milik akun yang sedang masuk,
 * jadi satu akun tidak pernah dapat menyentuh notifikasi akun lain.
 *
 * Endpoint `latest()` ditarik berkala oleh browser (polling). Cara ini dipilih
 * agar pemberitahuan terasa langsung tanpa menuntut server WebSocket — cukup
 * dengan bawaan Laravel. Bila kelak broadcasting diaktifkan, tampilan lonceng
 * dan popupnya tidak perlu diubah, hanya sumber datanya.
 */
trait HandlesNotifications
{
    /** Nama view daftar notifikasi milik dashboard yang bersangkutan. */
    abstract protected function indexView(): string;

    public function index(Request $request): View
    {
        return view($this->indexView(), [
            'notifications' => $request->user()->notifications()->paginate(20),
            'unreadCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /** Ringkasan untuk ikon lonceng dan popup, ditarik berkala oleh browser. */
    public function latest(Request $request): JsonResponse
    {
        $unread = $request->user()
            ->unreadNotifications()
            ->limit(8)
            ->get();

        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'notifications' => $unread->map(fn (DatabaseNotification $notification) => [
                'id' => $notification->id,
                'type' => $notification->data['type'] ?? null,
                'title' => $notification->data['title'] ?? 'Notifikasi',
                'message' => $notification->data['message'] ?? '',
                'url' => $notification->data['url'] ?? null,
                'created_at' => $notification->created_at?->diffForHumans(),
            ])->all(),
        ]);
    }

    /** Tandai satu notifikasi terbaca lalu buka tautannya bila ada. */
    public function read(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()->notifications()->whereKey($notification)->firstOrFail();

        $record->markAsRead();

        $target = $record->data['url'] ?? null;

        return $target ? redirect()->to($target) : back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
