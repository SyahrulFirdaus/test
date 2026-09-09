<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
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
    public function __construct(private readonly ActivityLogger $activity) {}

    public function edit(Request $request): View
    {
        return view('dashboard.profile', [
            'user' => $request->user(),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $fields = ['name', 'email', 'phone', 'city', 'postal_code', 'address'];

        // Keadaan sebelum perubahan dibaca lebih dulu supaya jejak auditnya
        // dapat memperbandingkan keduanya. Nomor WhatsApp dan alamat ikut di
        // dalamnya sebagai kolom `phone` dan `address`.
        $before = $user->only($fields);

        $user->update($request->safe()->only($fields));

        $this->activity->logChanges(
            action: ActivityAction::PROFILE_UPDATE,
            before: $before,
            after: $user->only($fields),
            description: 'Memperbarui data profil akun.',
            subject: $user,
            actor: $user,
        );

        return back()->with('status', 'Profil berhasil diperbarui.');
    }
}
