@extends('layouts.app')
@section('title', 'Reset password')

@section('content')
<div class="auth-center">
<div class="tracer-wrapper" style="max-width: 420px;">
    @include('partials.auth-brand')
    <div class="tracer-header text-center">
        <h1 class="mb-0">Reset your password</h1>
        <p class="mb-0">Choose a new password for your account.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="tracer-card">
        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input autocomplete="username" type="email" name="email" class="form-control" value="{{ old('email', $email) }}" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">New password</label>
                <input autocomplete="new-password" type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm new password</label>
                <input autocomplete="new-password" type="password" name="password_confirmation" class="form-control" required>
            </div>
            <button type="submit" class="btn-tracer-submit w-100">Reset password</button>
        </form>
    </div>
</div>
</div>
@endsection
