<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function edit(Request $request): View
    {
        return view('admin.profile', [
            'user' => $request->user(),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $fields = ['name', 'email', 'phone', 'city', 'postal_code', 'address'];

        $before = $user->only($fields);

        $user->update($request->safe()->only($fields));

        $this->activity->logChanges(
            action: ActivityAction::PROFILE_UPDATE,
            before: $before,
            after: $user->only($fields),
            description: 'Memperbarui data profil admin.',
            subject: $user,
            actor: $user,
        );

        return back()->with('status', 'Profil berhasil diperbarui.');
    }
}
