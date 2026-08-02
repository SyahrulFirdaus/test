<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Profil pelanggan.
 *
 * Data di sini menjadi nilai bawaan formulir penawaran, sehingga
 * memperbaruinya cukup dilakukan satu kali di satu tempat.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('dashboard.profile', [
            'user' => $request->user(),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->safe()->only([
            'name', 'email', 'phone', 'city', 'postal_code', 'address',
        ]));

        return back()->with('status', 'Profil berhasil diperbarui.');
    }
}
