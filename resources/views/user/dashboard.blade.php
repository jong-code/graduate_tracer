@extends('layouts.app')
@section('title', 'My Dashboard')

@section('content')
<div class="tracer-wrapper">
    <div class="tracer-header">
        <h1>Welcome, {{ auth()->user()->name }}</h1>
        <p>Here's the status of your Graduate Tracer Survey</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('clear_survey_draft'))
        <script>
            // The survey was actually saved server-side - now it's safe to
            // drop the local draft. This only runs on a confirmed success,
            // never just because a submit was attempted (see dashboard.blade.php
            // in resources/views/tracer for why that distinction matters).
            try { localStorage.removeItem('gts_draft_{{ auth()->id() }}'); } catch (e) {}
        </script>
    @endif

    <div class="tracer-card mb-3">
        <h2>Survey Status</h2>
        @if ($submissionStatus === 'submitted')
            <p class="text-success fw-semibold mb-2">✓ Submitted on {{ $survey->submitted_at->format('M j, Y') }}</p>
            <a href="{{ route('user.survey') }}" class="btn-tracer-next d-inline-block">Update my answers</a>
            <a href="{{ route('user.survey.preview') }}" class="btn-tracer-prev d-inline-block">Preview my survey</a>
        @elseif ($submissionStatus === 'draft')
            <p class="text-warning fw-semibold mb-2">Draft saved — not yet submitted</p>
            <a href="{{ route('user.survey') }}" class="btn-tracer-next d-inline-block">Continue survey</a>
        @else
            <p class="text-muted mb-2">You haven't started your survey yet.</p>
            <a href="{{ route('user.survey') }}" class="btn-tracer-next d-inline-block">Start survey</a>
        @endif
    </div>

    <div class="tracer-card mb-3">
        <h2>Consent</h2>
        @if (auth()->user()->consent_given)
            <p class="text-success mb-2">You've given consent for your data to be used for tracer research (as of {{ auth()->user()->consent_given_at->format('M j, Y') }}).</p>
            <form method="POST" action="{{ route('user.profile.consent.withdraw') }}">
                @csrf
                <button type="submit" class="btn-tracer-prev">Withdraw consent</button>
            </form>
        @else
            <p class="text-muted mb-2">You have not yet given consent. This is required before you can submit your survey.</p>
            <form method="POST" action="{{ route('user.profile.consent.give') }}">
                @csrf
                <button type="submit" class="btn-tracer-submit">Give consent</button>
            </form>
        @endif
    </div>

    <div class="tracer-card">
        <h2>Identity Verification</h2>
        @if (auth()->user()->identity_verified_at)
            <p class="text-success mb-0">✓ Verified on {{ auth()->user()->identity_verified_at->format('M j, Y') }}</p>
        @else
            <p class="text-muted mb-2">Please verify your identity from your <a href="{{ route('user.profile.edit') }}">profile page</a>.</p>
        @endif
    </div>

    @if ($submissionStatus === 'submitted')
        <div class="tracer-card mt-3">
            <h2>Free Load Reward</h2>
            <p class="text-muted">Please input your GCash number to receive your free load reward.</p>

            @error('number')
                <div class="alert alert-danger py-2">{{ $message }}</div>
            @enderror

            <form method="POST" action="{{ route('user.gcash-number.save') }}" class="d-flex gap-2 flex-wrap">
                @csrf
                <input type="text" name="number" class="form-control" style="max-width: 260px;" placeholder="e.g. 09171234567" value="{{ old('number', $userNumber?->number) }}" required maxlength="20">
                <button type="submit" class="btn-tracer-submit">{{ $userNumber ? 'Update' : 'Submit' }}</button>
            </form>

            @if ($userNumber?->is_done)
                <p class="text-success mt-2 mb-0">✓ Your reward has been sent.</p>
            @elseif ($userNumber)
                <p class="text-muted mt-2 mb-0">Your number is on file - the reward is being processed.</p>
            @endif
        </div>
    @endif
</div>
@endsection
