<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Pendaftaran akun pelanggan.
 *
 * Data pengiriman (telepon, kota, kode pos, alamat) diminta sejak awal agar
 * formulir penawaran tidak perlu menanyakannya lagi setiap kali — nilainya
 * langsung mengisi formulir "Minta Penawaran" pada halaman 3D Models.
 */
class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            ...$request->safe()->only(['name', 'phone', 'city', 'postal_code', 'address', 'email']),
            'role' => User::ROLE_USER,
            'password' => $request->string('password')->toString(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with('status', 'Akun Anda berhasil dibuat. Selamat datang, '.$user->name.'!');
    }
}
