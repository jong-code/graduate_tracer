<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
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

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Those credentials do not match our records.'])->onlyInput('email');
        }

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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'academic_program_id' => ['required', 'exists:academic_programs,id'],
            'school_year_id' => ['required', 'exists:school_years,id'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user',
        ]);

        GraduateTracerSurvey::create([
            'user_id' => $user->id,
            'academic_program_id' => $validated['academic_program_id'],
            'school_year_id' => $validated['school_year_id'],
        ]);

        Auth::login($user);

        return redirect()->route('user.dashboard')->with('status', 'Welcome! Please verify your identity and review consent before continuing.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
