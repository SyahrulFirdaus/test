<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

/**
 * Langkah pertama "Lupa Password": kirimkan tautan reset ke email pengguna.
 *
 * Hasilnya sengaja selalu sama apa pun keadaan emailnya, sehingga halaman ini
 * tidak dapat dipakai memeriksa email mana yang terdaftar.
 */
class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum benar.',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput()->withErrors([
                'email' => 'Tautan reset baru saja dikirim. Mohon tunggu sebentar sebelum meminta lagi.',
            ]);
        }

        return back()->with('status', 'Bila email tersebut terdaftar, tautan untuk membuat kata sandi baru sudah kami kirimkan. Periksa juga folder spam.');
    }
}
