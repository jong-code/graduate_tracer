<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('user.profile', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $request->user()->id],
        ]);

        $request->user()->update($validated);

        return back()->with('status', 'Profile updated.');
    }

    /**
     * Lightweight identity verification: re-confirm the password to prove
     * account ownership. Swap for OTP/email-link verification if the school
     * needs a stronger identity check later.
     */
    public function verifyIdentity(Request $request)
    {
        $request->validate(['password' => ['required']]);

        if (! Hash::check($request->input('password'), $request->user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $request->user()->update(['identity_verified_at' => now()]);

        return back()->with('status', 'Identity verified.');
    }

    public function giveConsent(Request $request)
    {
        $request->user()->update([
            'consent_given' => true,
            'consent_given_at' => now(),
        ]);

        return back()->with('status', 'Thank you — consent recorded.');
    }

    public function withdrawConsent(Request $request)
    {
        $request->user()->update([
            'consent_given' => false,
            'consent_given_at' => null,
        ]);

        return back()->with('status', 'Your consent has been withdrawn.');
    }
}
