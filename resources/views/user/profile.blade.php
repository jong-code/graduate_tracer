@extends('layouts.app')
@section('title', 'My Profile')

@section('content')
<div class="profile-workspace">
    <aside class="profile-summary">
        <div class="profile-avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1).mb_substr($user->last_name ?? '', 0, 1)) }}</div>
        <h2>{{ $user->name }}</h2>
        <p>{{ $user->email }}</p>
        <span class="status-badge {{ $user->is_active ? 'success' : 'neutral' }}">{{ $user->is_active ? 'Active account' : 'Inactive account' }}</span>
        <nav aria-label="Profile sections">
            <a href="#accountDetails">Account Details</a>
            @unless ($user->identity_verified_at)<a href="#identityVerification">Verify Identity</a>@endunless
            <a href="{{ route('user.dashboard') }}">Survey Status</a>
        </nav>
        <p class="profile-note">Keep your information up to date to ensure a smooth survey experience.</p>
    </aside>
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

    <div class="tracer-card mb-3" id="accountDetails">
        <h2>Account Details</h2>
        <form method="POST" action="{{ route('user.profile.update') }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label">Last Name</label>
                <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $user->last_name) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">First Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Middle Name (leave as blank if None)</label>
                <input type="text" name="middle_name" class="form-control" value="{{ old('middle_name', $user->middle_name) }}">
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
            </div>
            <button type="submit" class="btn-tracer-submit">Save changes</button>
        </form>
    </div>

    @unless ($user->identity_verified_at)
    <div class="tracer-card mb-3" id="identityVerification">
        <h2>Verify Identity</h2>
        <p class="text-muted">Re-enter your password to confirm you own this account.</p>
        <form method="POST" action="{{ route('user.profile.verify') }}">
            @csrf
            <div class="mb-3">
                <label for="identityPassword" class="form-label">Current password</label>
                <input id="identityPassword" type="password" name="password" autocomplete="current-password" class="form-control" placeholder="Current password" required>
            </div>
            <button type="submit" class="btn-tracer-submit">Verify my identity</button>
        </form>
    </div>
    @endunless
</div>
</div>
@endsection
