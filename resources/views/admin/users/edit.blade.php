@extends('layouts.app')
@section('title', 'Edit User')

@section('content')
<div class="tracer-wrapper" style="max-width: 500px;">
    <div class="tracer-header"><h1>Edit User</h1></div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="tracer-card">
        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                    @foreach (['user' => 'User (Graduate/Alumni)', 'faculty' => 'Faculty (Read-only)', 'admin' => 'Admin'] as $val => $label)
                        <option value="{{ $val }}" {{ $user->role === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label d-block">Status</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="is_active" id="active_1" value="1" {{ $user->is_active ? 'checked' : '' }}>
                    <label class="form-check-label" for="active_1">Active</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="is_active" id="active_0" value="0" {{ !$user->is_active ? 'checked' : '' }}>
                    <label class="form-check-label" for="active_0">Inactive</label>
                </div>
            </div>
            <button type="submit" class="btn-tracer-submit">Save</button>
            <a href="{{ route('admin.users.index') }}" class="btn-tracer-prev">Cancel</a>
        </form>
    </div>
</div>
@endsection
