<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Rules\UniqueEncryptedEmail;
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
            'last_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required', 'email',
                new UniqueEncryptedEmail(ignoreUserId: $request->user()->id),
            ],
        ]);

        $user = $request->user();
        $emailChanged = strtolower($validated['email']) !== strtolower($user->email);

        $user->update($validated);

        if ($emailChanged) {
            // User::booted() already reset email_verified_at to null for
            // us when it saw the email column change - send a fresh link
            // for the new address so the account doesn't sit permanently
            // unverified.
            $user->sendEmailVerificationNotification();

            return back()->with('status', 'Profile updated. Please check your new email address to verify it.');
        }

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
}
