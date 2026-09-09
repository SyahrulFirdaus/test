<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use App\Support\PasswordPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Ganti kata sandi dari dalam dashboard.
 *
 * Dipakai user maupun admin; tampilannya mengikuti dashboard yang sedang
 * dibuka, tetapi aturan dan pemeriksaan kata sandi lamanya sama.
 */
class PasswordController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function edit(Request $request): View
    {
        return view($request->user()->isAdmin() ? 'admin.password' : 'dashboard.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', ...PasswordPolicy::rules()],
        ], [
            ...PasswordPolicy::messages(),
            'current_password.required' => 'Kata sandi lama wajib diisi.',
            'current_password.current_password' => 'Kata sandi lama tidak cocok.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi baru belum sama.',
            'password.different' => 'Kata sandi baru harus berbeda dari kata sandi lama.',
        ]);

        $request->user()->update([
            'password' => $request->string('password')->toString(),
        ]);

        // Yang dicatat hanya bahwa penggantian terjadi. Kata sandi lama maupun
        // baru tidak pernah ikut ke jejak audit dalam bentuk apa pun.
        $this->activity->log(
            action: ActivityAction::PASSWORD_UPDATE,
            description: 'Mengganti kata sandi akun.',
            subject: $request->user(),
            actor: $request->user(),
        );

        return back()->with('status', 'Kata sandi berhasil diperbarui.');
    }
}
