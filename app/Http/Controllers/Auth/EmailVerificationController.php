<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * "Please verify your email" holding page - shown instead of the
     * dashboard for any user who hits a 'verified'-gated route while
     * unverified (see routes/web.php and EnsureEmailIsVerified).
     */
    public function notice(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route($request->user()->dashboardRoute());
        }

        return view('auth.verify-email');
    }

    /**
     * Lightweight JSON check the verify-email page below polls in the
     * background - lets that page notice a verification that happened in
     * another tab (the person clicking the emailed link) without them
     * having to manually reload this one.
     */
    public function status(Request $request)
    {
        return response()->json([
            'verified' => $request->user()->hasVerifiedEmail(),
        ]);
    }

    /**
     * The link the user actually clicks in their inbox. EmailVerificationRequest
     * (Laravel's built-in FormRequest for this) validates the route's
     * signature and that the {id}/{hash} pair matches the authenticated
     * user before this method ever runs, so there's no manual hash
     * comparison to get wrong here.
     */
    public function verify(EmailVerificationRequest $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route($request->user()->dashboardRoute())
                ->with('status', 'Your email is already verified.');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->route($request->user()->dashboardRoute())
            ->with('status', 'Your email has been verified! Welcome aboard.');
    }

    /**
     * Resend for a user who is already logged in but not yet verified
     * (e.g. from the verify-email notice page).
     */
    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route($request->user()->dashboardRoute());
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'A new verification link has been sent to your email address.');
    }

    /**
     * Resend for a guest - used from the registration page when someone
     * tries to register an email that already exists but isn't verified
     * yet (see AuthController@register). Deliberately gives the exact same
     * response whether or not the address is registered, already verified,
     * or unverified - confirming any of those to an unauthenticated visitor
     * would leak whether an email has an account here at all.
     */
    public function resendGuest(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::findByEmail($validated['email']);

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('status', "If that email is registered and not yet verified, we've sent a new verification link.");
    }
}
