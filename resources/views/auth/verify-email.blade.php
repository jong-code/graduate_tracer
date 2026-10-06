@extends('layouts.app')
@section('title', 'Verify your email')

@section('content')
<div class="auth-center">
<div class="tracer-wrapper" style="max-width: 460px;">
    @include('partials.auth-brand')
    <div class="tracer-header text-center">
        <h1>Verify your email</h1>
        <p>We've sent a verification link to <strong>{{ auth()->user()->email }}</strong>. Click it to activate your account and continue to your dashboard.</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="tracer-card text-center">
        <p class="text-muted small mb-3" id="autoCheckStatus">Waiting for you to click the link - this page will update automatically once verified.</p>
        <p class="text-muted mb-3">Didn't get the email? Check your spam folder, or send a new one below.</p>
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-tracer-submit w-100">Resend verification email</button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="btn-tracer-prev w-100">Log out</button>
        </form>
    </div>
</div>
</div>

<script>
    // Polls quietly in the background so someone who verifies in another
    // tab (clicking the emailed link) doesn't have to come back here and
    // manually reload - the moment the check comes back verified, this
    // reloads the page itself, and EmailVerificationController@notice
    // takes care of redirecting them on to the dashboard from there.
    (function () {
        const statusText = document.getElementById('autoCheckStatus');
        const checkUrl = '{{ route('verification.status') }}';
        const intervalMs = 4000;

        const poll = setInterval(function () {
            fetch(checkUrl, { headers: { 'Accept': 'application/json' } })
                .then(res => res.json())
                .then(data => {
                    if (data.verified) {
                        clearInterval(poll);
                        statusText.textContent = "You're verified! Redirecting...";
                        statusText.classList.add('text-success');
                        window.location.reload();
                    }
                })
                .catch(() => {
                    // A transient network hiccup shouldn't stop retrying -
                    // just skip this round and try again next interval.
                });
        }, intervalMs);
    })();
</script>
@endsection
