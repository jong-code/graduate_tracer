<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\GraduateProgram;
use App\Models\GraduateTracerSurvey;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // users.email is encrypted (see User::$casts), so it can't be
        // matched with Auth::attempt()'s usual `where('email', ...)` -
        // look the account up via its email_hash blind index instead,
        // then verify the password and log in manually.
        $user = User::findByEmail($credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['email' => 'Those credentials do not match our records.'])->onlyInput('email');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            return back()->withErrors(['email' => 'This account has been deactivated.']);
        }

        return redirect()->route($user->dashboardRoute());
    }

    public function showRegister()
    {
        $programs = AcademicProgram::where('is_active', true)->orderBy('name')->get();
        $schoolYears = SchoolYear::orderByDesc('label')->get();

        return view('auth.register', compact('programs', 'schoolYears'));
    }

    /**
     * Graduates register with their academic program and school year up
     * front, so a survey shell is ready and pre-filled by the time they
     * reach the full Graduate Tracer Survey form.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'last_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                function ($attribute, $value, $fail) {
                    // Deliberately not the shared App\Rules\UniqueEncryptedEmail
                    // rule used elsewhere (ProfileController,
                    // UserManagementController) - registration needs to
                    // distinguish an already-verified account (reject
                    // outright) from an already-registered-but-unverified
                    // one (different message, offers a resend below),
                    // which that simpler rule doesn't attempt.
                    //
                    // users.email is encrypted (see User::$casts), so the
                    // usual unique:users,email rule can't see through the
                    // ciphertext to detect a duplicate - check the
                    // deterministic email_hash blind index instead. This
                    // is the first line of defense only; the unique index
                    // on email_hash (see the encryption migration) is what
                    // actually stops two simultaneous requests from both
                    // slipping past this check and creating duplicate
                    // accounts - see the catch block below.
                    $existing = User::findByEmail($value);

                    if (! $existing) {
                        return;
                    }

                    if ($existing->hasVerifiedEmail()) {
                        $fail('This email is already registered. Please log in instead.');
                    } else {
                        $fail('This email is already registered but has not been verified. Please check your email for the verification link, or request a new one below.');
                    }
                },
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
            'academic_program_id' => ['required', 'exists:academic_programs,id'],
            'school_year_id' => ['required', 'exists:school_years,id'],
        ]);

        try {
            $user = User::create([
                'name' => $validated['name'],
                'last_name' => $validated['last_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'user',
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Only reachable if two registration requests for the same
            // email raced past the validation check above at the same
            // time - the email_hash unique index is the actual guard
            // here, this just turns the resulting DB error into a normal
            // validation message instead of a 500.
            if ($e->getCode() === '23000') {
                return back()
                    ->withErrors(['email' => 'This email is already registered. Please log in instead.'])
                    ->withInput($request->except('password', 'password_confirmation'));
            }

            throw $e;
        }

        GraduateTracerSurvey::create([
            'user_id' => $user->id,
            'academic_program_id' => $validated['academic_program_id'],
            'school_year_id' => $validated['school_year_id'],
        ]);

        // Standalone historical record of "registered under this program,
        // this school year" - kept separate from the survey above (which
        // already carries the same two IDs for its own purposes).
        GraduateProgram::create([
            'user_id' => $user->id,
            'academic_program_id' => $validated['academic_program_id'],
            'school_year_id' => $validated['school_year_id'],
        ]);

        $user->sendEmailVerificationNotification();

        Auth::login($user);

        return redirect()->route('user.dashboard')->with('status', 'Welcome! Please check your email to verify your account before continuing.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
