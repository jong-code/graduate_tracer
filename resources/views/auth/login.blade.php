@extends('layouts.app')
@section('title', 'Log in')

@section('content')
<div class="auth-center">
@include('partials.auth-panel')
<div class="tracer-wrapper" style="max-width: 420px;">
    @include('partials.auth-brand')
    <div class="tracer-header text-center">
        <div class="d-flex flex-column align-items-center justify-content-center gap-3">
            <img src="{{ asset('images/nemsu-logo.png') }}" alt="NEMSU logo" class="tracer-header-logo tracer-header-logo-lg">
            <div>
                <h1 class="mb-0">Welcome back</h1>
                <p class="mb-0">Log in to the Graduate Tracer System</p>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="tracer-card">
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="loginEmail">Email address</label>
                <input id="loginEmail" autocomplete="username" type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="mb-2">
                <label class="form-label" for="passwordInput">Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="passwordInput" autocomplete="current-password" class="form-control" required>
                    <button type="button" class="btn btn-outline-secondary" id="togglePassword" aria-label="Show password">
                        <svg id="eyeIconOpen" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8ZM1.173 8a13.13 13.13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.16 13.16 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5ZM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/></svg>
                        <svg id="eyeIconClosed" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="display:none;"><path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 0 0-2.79.588l.77.771A5.944 5.944 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486l.708.709z"/><path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/><path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12-.708.708z"/></svg>
                    </button>
                </div>
            </div>
            <p class="text-end mb-3">
                <a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal" class="small">Forgot password?</a>
            </p>
            <div class="form-check mb-3">
                <input type="checkbox" name="remember" class="form-check-input" id="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>
            <button type="submit" class="btn-tracer-submit w-100">Log in</button>
        </form>
        <p class="auth-helper">Your answers help improve programs for future graduates.</p>
        <p class="text-center mt-3 mb-0">
            <a href="{{ route('register') }}">Register or claim your alumni account</a>
        </p>
    </div>
</div>
</div>

<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="forgotPasswordModalLabel">Reset your password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Enter the email address on your account and we'll send you a link to change your password.</p>
                    <div class="mb-2">
                        <label class="form-label" for="resetEmail">Email address</label>
                        <input id="resetEmail" autocomplete="email" type="email" name="email" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-tracer-prev" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-tracer-submit">Send reset link</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    (function () {
        const btn = document.getElementById('togglePassword');
        const input = document.getElementById('passwordInput');
        const eyeOpen = document.getElementById('eyeIconOpen');
        const eyeClosed = document.getElementById('eyeIconClosed');

        btn.addEventListener('click', function () {
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            eyeOpen.style.display = showing ? '' : 'none';
            eyeClosed.style.display = showing ? 'none' : '';
        });
    })();
</script>
@endsection
