@extends('layouts.app')
@section('title', 'Add User')

@section('content')
<div class="tracer-wrapper" style="max-width: 500px;">
    <div class="tracer-header"><h1>Add User</h1></div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="tracer-card">
        <form method="POST" action="{{ route('admin.users.store') }}">
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
                <label class="form-label">Temporary Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                    <option value="user">User (Graduate/Alumni)</option>
                    <option value="faculty">Faculty (Read-only)</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="btn-tracer-submit">Create User</button>
            <a href="{{ route('admin.users.index') }}" class="btn-tracer-prev">Cancel</a>
        </form>
    </div>
</div>
@endsection
