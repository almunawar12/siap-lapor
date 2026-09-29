<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordRequest;
use App\Http\Requests\ProfileRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('profile');
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->only(['name', 'email']));
        $user->save();

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(PasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->password = (string) $request->string('password');
        $user->must_change_password = false;
        $user->save();

        return back()->with('success', 'Kata sandi berhasil diperbarui.');
    }
}
