@extends('layouts.app')
@section('title', 'My Profile')

@section('content')
<div class="tracer-wrapper">
    <div class="tracer-header">
        <h1>My Profile</h1>
        <p>Manage your own account details</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="tracer-card mb-3">
        <h2>Account Details</h2>
        <form method="POST" action="{{ route('user.profile.update') }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
            </div>
            <button type="submit" class="btn-tracer-submit">Save changes</button>
        </form>
    </div>

    @unless ($user->identity_verified_at)
    <div class="tracer-card mb-3">
        <h2>Verify Identity</h2>
        <p class="text-muted">Re-enter your password to confirm you own this account.</p>
        <form method="POST" action="{{ route('user.profile.verify') }}">
            @csrf
            <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Current password" required>
            </div>
            <button type="submit" class="btn-tracer-submit">Verify my identity</button>
        </form>
    </div>
    @endunless

    <div class="tracer-card">
        <h2>Consent</h2>
        @if ($user->consent_given)
            <p class="text-success">Consent given on {{ $user->consent_given_at->format('M j, Y') }}.</p>
            <form method="POST" action="{{ route('user.profile.consent.withdraw') }}">
                @csrf
                <button type="submit" class="btn-tracer-prev">Withdraw consent</button>
            </form>
        @else
            <form method="POST" action="{{ route('user.profile.consent.give') }}">
                @csrf
                <button type="submit" class="btn-tracer-submit">Give consent</button>
            </form>
        @endif
    </div>
</div>
@endsection
