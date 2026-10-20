<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('member.profile', ['user' => $request->user()]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->update($request->profileData());

        if ($request->hasFile('avatar')) {
            $user->replaceAvatar($request->file('avatar'));
        }

        return back()->with('status', 'Data diri berhasil disimpan.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update(['password' => $validated['password']]);

        return back()->with('status', 'Password berhasil diganti.');
    }
}
