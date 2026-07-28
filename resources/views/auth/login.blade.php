@extends('layouts.app')
@section('title', 'Log in')

@section('content')
<div class="tracer-wrapper" style="max-width: 420px;">
    <div class="tracer-header text-center">
        <div class="d-flex align-items-center justify-content-center gap-3">
            <img src="{{ asset('images/nemsu-logo.png') }}" alt="NEMSU logo" class="tracer-header-logo">
            <div>
                <h1 class="mb-0">Welcome back</h1>
                <p class="mb-0">Log in to the Graduate Tracer System</p>
            </div>
            <img src="{{ asset('images/cite-logo.png') }}" alt="College of Information Technology Education logo" class="tracer-header-logo">
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="tracer-card">
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" name="remember" class="form-check-input" id="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>
            <button type="submit" class="btn-tracer-submit w-100">Log in</button>
        </form>
        <p class="text-center mt-3 mb-0">
            <a href="{{ route('register') }}">Register or claim your alumni account</a>
        </p>
    </div>
</div>
@endsection
