<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Laravel's built-in Password::sendResetLink()/reset() broker looks users
 * up with a plain `where('email', $value)` query, which can never match
 * users.email once it's encrypted (see User::$casts) - the same reason
 * AuthController@login can't use Auth::attempt(). This controller
 * re-implements the same token flow by hand: it still uses Laravel's own
 * password_reset_tokens table and the built-in ResetPassword notification
 * (available via CanResetPassword, which User already inherits from its
 * base Authenticatable class) - only the "find the account" step is
 * swapped for User::findByEmail()'s email_hash blind-index lookup.
 */
class PasswordResetController extends Controller
{
    /**
     * How long a reset link stays valid, in minutes. Laravel's own
     * config('auth.passwords.users.expire') would normally control this,
     * but that config is only consulted by the built-in broker we can't
     * use here - kept as a constant instead.
     */
    private const TOKEN_EXPIRE_MINUTES = 60;

    /**
     * Handles the modal form on the login page.
     */
    public function sendResetLink(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::findByEmail($validated['email']);

        if ($user) {
            $email = $user->email;
            $existing = DB::table('password_reset_tokens')->where('email', $email)->first();

            // Mirrors Laravel's own default 60-second resend throttle, so
            // an impatient double-click doesn't fire off a fresh
            // email/token every time.
            if (! $existing || Carbon::parse($existing->created_at)->lte(now()->subSeconds(60))) {
                $token = Str::random(64);

                DB::table('password_reset_tokens')->updateOrInsert(
                    ['email' => $email],
                    ['token' => Hash::make($token), 'created_at' => now()]
                );

                $user->sendPasswordResetNotification($token);
            }
        }

        // Same response whether or not the address is registered - saying
        // "no account with that email" would let a guest enumerate who's
        // registered here (same reasoning as
        // EmailVerificationController@resendGuest).
        return back()->with('status', "If that email is registered, we've sent a link to reset your password.");
    }

    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:64'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $invalidLink = ['email' => 'This password reset link is invalid or has expired. Please request a new one.'];

        $user = User::findByEmail($validated['email']);

        if (! $user) {
            return back()->withErrors($invalidLink)->withInput($request->only('email'));
        }

        $reset = DB::transaction(function () use ($user, $validated) {
            $tokens = DB::table('password_reset_tokens')->where('email', $user->email);
            $row = (clone $tokens)->lockForUpdate()->first();

            if (! $row || ! $row->created_at
                || Carbon::parse($row->created_at)->lte(now()->subMinutes(self::TOKEN_EXPIRE_MINUTES))
                || ! Hash::check($validated['token'], $row->token)) {
                return false;
            }

            $user->forceFill([
                'password' => Hash::make($validated['password']),
                'remember_token' => Str::random(60),
            ])->save();
            $tokens->delete();

            return true;
        });

        if (! $reset) {
            return back()->withErrors($invalidLink)->withInput($request->only('email'));
        }

        return redirect()->route('login')->with('status', 'Your password has been reset. Please log in.');
    }
}
