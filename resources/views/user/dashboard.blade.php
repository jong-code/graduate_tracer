@extends('layouts.app')
@section('title', 'My Dashboard')

@section('content')
<div class="tracer-wrapper user-page">
    <div class="tracer-header">
        <p class="workspace-eyebrow">Graduate workspace</p>
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
        <div class="graduate-status-header">
            <h2>Your graduate survey</h2>
            <span class="status-badge {{ $submissionStatus === 'submitted' ? 'success' : ($submissionStatus === 'draft' ? 'pending' : 'neutral') }}">
                {{ $submissionStatus === 'submitted' ? 'Submitted' : ($submissionStatus === 'draft' ? 'Draft in progress' : 'Not started') }}
            </span>
        </div>
        <div class="user-survey-progress" aria-label="Survey progress">
            @foreach (['Start', 'Draft saved', 'Submitted'] as $progressLabel)
                <div><span class="{{ ($submissionStatus === 'submitted' || ($submissionStatus === 'draft' && $loop->index < 2) || $loop->first) ? 'active' : '' }}">{{ $loop->iteration }}</span><small>{{ $progressLabel }}</small></div>
            @endforeach
        </div>
        <p class="text-muted small mb-3">
            <strong>Graduate Tracer Survey</strong> — This survey aims to gather information about the employment, career progression, and experiences of our graduates after completing their studies. The results will help the university assess the relevance and effectiveness of its academic programs and identify areas for improvement. Your responses will be treated with confidentiality and used for educational and program development purposes.
        </p>
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

            <button type="button" class="btn-tracer-submit" data-bs-toggle="modal" data-bs-target="#freeLoadRewardModal">{{ $userNumber ? 'Update GCash number' : 'Add GCash number' }}</button>

            @if ($userNumber?->is_done)
                <p class="text-success mt-2 mb-0">✓ Your reward has been sent.</p>
            @elseif ($userNumber)
                <p class="text-muted mt-2 mb-0">Your number is on file - the reward is being processed.</p>
            @endif
        </div>
    @endif
</div>
@if ($submissionStatus === 'submitted')
<div class="modal fade" id="freeLoadRewardModal" tabindex="-1" aria-labelledby="freeLoadRewardTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('user.gcash-number.save') }}">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="freeLoadRewardTitle">Free Load Reward</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Your survey is submitted. Enter your GCash mobile number to receive your free load reward.</p>
                    <label for="rewardNumber" class="form-label">GCash mobile number</label>
                    <input id="rewardNumber" inputmode="tel" autocomplete="tel" type="text" name="number" class="form-control" placeholder="e.g. 09171234567" value="{{ old('number', $userNumber?->number) }}" required maxlength="20">
                    @error('number')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Later</button>
                    <button type="submit" class="btn-tracer-submit">Save GCash number</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
@if ($submissionStatus === 'submitted' && ($errors->has('number') || (session('show_reward_modal') && ! $userNumber?->is_done)))
<script>
    bootstrap.Modal.getOrCreateInstance(document.getElementById('freeLoadRewardModal')).show();
</script>
@endif
@endsection
