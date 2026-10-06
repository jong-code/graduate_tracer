@extends('layouts.app')
@section('title', 'Register')

@section('content')
<div class="auth-center">
@include('partials.auth-panel')
<div class="tracer-wrapper" style="max-width: 460px;">
    @include('partials.auth-brand')
    <div class="tracer-header text-center">
        <h1>Create your account</h1>
        <p>New graduates register below. Tell us your academic program and school year so we can get your survey ready.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->has('email') && str_contains($errors->first('email'), 'has not been verified'))
        <div class="tracer-card mb-3">
            <p class="text-muted mb-2">Resend the verification link to that address:</p>
            <form method="POST" action="{{ route('verification.resend-guest') }}" class="d-flex gap-2 flex-wrap">
                @csrf
                <input type="email" name="email" class="form-control" style="max-width: 260px;" value="{{ old('email') }}" required>
                <button type="submit" class="btn-tracer-prev">Resend verification email</button>
            </form>
        </div>
    @endif

    <div class="tracer-card">
        <form method="POST" action="{{ route('register') }}" id="registerForm">
            @csrf
            <div class="mb-3">
                <label class="form-label">Last Name</label>
                <input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">First Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Middle Name (leave as blank if None)</label>
                <input type="text" name="middle_name" class="form-control" value="{{ old('middle_name') }}">
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="passwordInput" class="form-control" required>
                    <button type="button" class="btn btn-outline-secondary" id="togglePassword" tabindex="-1" aria-label="Show password">
                        <svg id="eyeIconOpen" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8ZM1.173 8a13.13 13.13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.16 13.16 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5ZM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/></svg>
                        <svg id="eyeIconClosed" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="display:none;"><path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 0 0-2.79.588l.77.771A5.944 5.944 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486l.708.709z"/><path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/><path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12-.708.708z"/></svg>
                    </button>
                </div>
                <div class="progress mt-2" style="height: 6px;">
                    <div id="passwordStrengthBar" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                </div>
                <small id="passwordStrengthLabel" class="text-muted"></small>
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <div class="input-group">
                    <input type="password" name="password_confirmation" id="passwordConfirmInput" class="form-control" required>
                    <button type="button" class="btn btn-outline-secondary" id="toggleConfirmPassword" tabindex="-1" aria-label="Show password">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8ZM1.173 8a13.13 13.13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.16 13.16 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5ZM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/></svg>
                    </button>
                </div>
                <small id="passwordMismatchFeedback" class="text-danger" style="display:none;">Passwords do not match.</small>
            </div>

            <hr>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Academic Program <span class="req" >*</span></label>
                    <select name="academic_program_id" class="form-select" style="width: 195px;" required>
                        <option value="" disabled {{ empty(old('academic_program_id')) ? 'selected' : '' }}>Select your program</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" {{ (string) old('academic_program_id') === (string) $program->id ? 'selected' : '' }}>
                                {{ $program->name }} ({{ $program->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">School Year <span class="req">*</span></label>
                    <select name="school_year_id" class="form-select" style="width: 190px;" required>
                        <option value="" disabled {{ empty(old('school_year_id')) ? 'selected' : '' }}>Select school year</option>
                        @foreach ($schoolYears as $schoolYear)
                            <option value="{{ $schoolYear->id }}"
                                {{ (string) old('school_year_id', $schoolYear->is_current ? (string) $schoolYear->id : '') === (string) $schoolYear->id ? 'selected' : '' }}>
                                {{ $schoolYear->label }}{{ $schoolYear->is_current ? ' (current)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <button type="submit" class="btn-tracer-submit w-100">Register</button>
        </form>
        <p class="text-center mt-3 mb-0">
            <a href="{{ route('login') }}">Already have an account? Log in</a>
        </p>
    </div>
</div>
</div>

<div class="modal fade" id="passwordMismatchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Passwords Don't Match</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Your Password and Confirm Password fields don't match. Please re-enter them so they're identical.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="weakPasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Weak Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="weakPasswordModalText" class="mb-0"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="continueWeakPasswordBtn">Continue Anyway</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    (function () {
        function wireToggle(buttonId, inputId, openIconId, closedIconId) {
            const btn = document.getElementById(buttonId);
            const input = document.getElementById(inputId);
            btn.addEventListener('click', function () {
                const showing = input.type === 'text';
                input.type = showing ? 'password' : 'text';
                btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
                if (openIconId && closedIconId) {
                    document.getElementById(openIconId).style.display = showing ? '' : 'none';
                    document.getElementById(closedIconId).style.display = showing ? 'none' : '';
                }
            });
        }
        wireToggle('togglePassword', 'passwordInput', 'eyeIconOpen', 'eyeIconClosed');
        wireToggle('toggleConfirmPassword', 'passwordConfirmInput', null, null);

        // ---- Password strength meter ----
        // A simple, dependency-free heuristic - not meant to be a rigorous
        // entropy calculation, just enough signal to nudge people away
        // from very weak passwords before they commit to one.
        function getPasswordStrength(pw) {
            if (!pw) return null;
            let score = 0;
            if (pw.length >= 8) score++;
            if (pw.length >= 12) score++;
            if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) score++;
            if (/\d/.test(pw)) score++;
            if (/[^A-Za-z0-9]/.test(pw)) score++;

            if (pw.length < 6) return 'weak';
            if (score <= 2) return 'weak';
            if (score <= 3) return 'moderate';
            return 'strong';
        }

        const passwordInput = document.getElementById('passwordInput');
        const bar = document.getElementById('passwordStrengthBar');
        const label = document.getElementById('passwordStrengthLabel');
        const STRENGTH_STYLE = {
            weak: { width: '33%', bg: '#dc3545', text: 'Weak password' },
            moderate: { width: '66%', bg: '#ffc107', text: 'Moderate password' },
            strong: { width: '100%', bg: '#198754', text: 'Strong password' },
        };

        function currentStrength() {
            return getPasswordStrength(passwordInput.value);
        }

        function renderStrength() {
            const strength = currentStrength();
            if (!strength) {
                bar.style.width = '0%';
                bar.style.backgroundColor = '';
                label.textContent = '';
                return;
            }
            const style = STRENGTH_STYLE[strength];
            bar.style.width = style.width;
            bar.style.backgroundColor = style.bg;
            label.textContent = style.text;
            label.style.color = style.bg;
        }

        passwordInput.addEventListener('input', renderStrength);

        // ---- Live "do the passwords match" check ----
        const passwordConfirmInput = document.getElementById('passwordConfirmInput');
        const mismatchFeedback = document.getElementById('passwordMismatchFeedback');

        function passwordsMatch() {
            return passwordInput.value === passwordConfirmInput.value;
        }

        // Only highlight once the person has actually typed something into
        // Confirm Password - flagging it red while it's still empty (before
        // they've had a chance to type anything) would be premature.
        function renderMismatch() {
            const shouldFlag = passwordConfirmInput.value.length > 0 && !passwordsMatch();
            passwordConfirmInput.classList.toggle('is-invalid', shouldFlag);
            mismatchFeedback.style.display = shouldFlag ? '' : 'none';
        }

        passwordInput.addEventListener('input', renderMismatch);
        passwordConfirmInput.addEventListener('input', renderMismatch);

        // ---- Modals: mismatch (hard stop) and weak/moderate (continue or cancel) ----
        const mismatchModalEl = document.getElementById('passwordMismatchModal');
        const weakModalEl = document.getElementById('weakPasswordModal');
        let mismatchModal, weakModal;

        const form = document.getElementById('registerForm');
        let bypassChecks = false;

        // See the equivalent helper in resources/views/tracer/dashboard.blade.php
        // for why this is a simulated "still working" percentage rather than
        // real upload progress - same reasoning applies here.
        function startFakeProgress(button, label) {
            let percent = 0;
            button.disabled = true;
            setInterval(() => {
                percent = Math.min(90, percent + Math.max(1, Math.round((90 - percent) * 0.15)));
                button.textContent = `${label} ${percent}%`;
            }, 150);
        }

        form.addEventListener('submit', function (e) {
            if (bypassChecks) return;

            if (!passwordsMatch()) {
                e.preventDefault();
                // Force the red highlight to show even if Confirm Password
                // was left untouched (e.g. still empty) when Register was
                // clicked.
                passwordConfirmInput.classList.add('is-invalid');
                mismatchFeedback.style.display = '';
                if (!mismatchModal) {
                    mismatchModal = new bootstrap.Modal(mismatchModalEl);
                }
                mismatchModal.show();
                return;
            }

            const strength = currentStrength();
            if (strength === 'weak' || strength === 'moderate') {
                e.preventDefault();
                document.getElementById('weakPasswordModalText').textContent =
                    `Your password strength is "${strength}". Will you continue?`;
                if (!weakModal) {
                    weakModal = new bootstrap.Modal(weakModalEl);
                }
                weakModal.show();
                return;
            }

            // Only reached once the passwords match and the password is
            // strong - i.e. the form is actually about to submit. Disable
            // the button here (not on 'click') so spam-clicking Register
            // can't fire duplicate submissions, while still leaving it
            // enabled to retry on either of the intercepted paths above.
            startFakeProgress(form.querySelector('button[type="submit"]'), 'Registering');
        });

        document.getElementById('continueWeakPasswordBtn').addEventListener('click', function () {
            bypassChecks = true;
            weakModal.hide();
            startFakeProgress(form.querySelector('button[type="submit"]'), 'Registering');
            form.submit();
        });
    })();
</script>
@endsection
