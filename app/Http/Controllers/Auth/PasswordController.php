<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * Ganti kata sandi dari dalam dashboard.
 *
 * Dipakai user maupun admin; tampilannya mengikuti dashboard yang sedang
 * dibuka, tetapi aturan dan pemeriksaan kata sandi lamanya sama.
 */
class PasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view($request->user()->isAdmin() ? 'admin.password' : 'dashboard.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)],
        ], [
            'current_password.required' => 'Kata sandi lama wajib diisi.',
            'current_password.current_password' => 'Kata sandi lama tidak cocok.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi baru belum sama.',
            'password.different' => 'Kata sandi baru harus berbeda dari kata sandi lama.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
        ]);

        $request->user()->update([
            'password' => $request->string('password')->toString(),
        ]);

        return back()->with('status', 'Kata sandi berhasil diperbarui.');
    }
}
