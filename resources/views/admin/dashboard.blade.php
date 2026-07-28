@extends('layouts.app')
@section('title', 'Admin Dashboard')

@section('content')
<div class="tracer-wrapper" style="max-width: 900px;">
    <div class="tracer-header">
        <h1>System Administration</h1>
        <p>Manage users, programs, school years, and system configuration. Survey content is not shown here by design.</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="tracer-card text-center py-3">
                <h2 class="mb-0">{{ $totalUsers }}</h2>
                <p class="text-muted small mb-0">Users</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="tracer-card text-center py-3">
                <h2 class="mb-0">{{ $totalPrograms }}</h2>
                <p class="text-muted small mb-0">Programs</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="tracer-card text-center py-3">
                <h2 class="mb-0">{{ $totalSchoolYears }}</h2>
                <p class="text-muted small mb-0">School Years</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="tracer-card text-center py-3">
                <h2 class="mb-0">{{ $totalSurveysSubmitted }}</h2>
                <p class="text-muted small mb-0">Surveys Submitted</p>
            </div>
        </div>
    </div>

    <div class="tracer-card">
        <h2>Configuration</h2>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.users.index') }}" class="btn-tracer-prev">Manage Users</a>
            <a href="{{ route('admin.programs.index') }}" class="btn-tracer-prev">Academic Programs</a>
            <a href="{{ route('admin.school-years.index') }}" class="btn-tracer-prev">School Years</a>
            <a href="{{ route('admin.analytics') }}" class="btn-tracer-prev">Analytics</a>
            <a href="{{ route('admin.templates') }}" class="btn-tracer-prev">Survey Templates</a>
            <a href="{{ route('admin.integrations') }}" class="btn-tracer-prev">Integrations</a>
            <a href="{{ route('admin.backups') }}" class="btn-tracer-prev">Backups</a>
            <a href="{{ route('admin.audit-logs.index') }}" class="btn-tracer-prev">Audit Logs</a>
        </div>
    </div>
</div>
@endsection
