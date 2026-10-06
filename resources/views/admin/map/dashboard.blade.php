@extends('layouts.app')
@section('title', 'Admin Dashboard')

@section('content')
<div class="tracer-wrapper admin-page" style="max-width: 900px;">
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
                <div class="admin-stat-value" data-count-target="{{ $totalUsers }}">0</div>
                <p class="admin-stat-label mb-0">Users</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="tracer-card text-center py-3">
                <div class="admin-stat-value" data-count-target="{{ $totalPrograms }}">0</div>
                <p class="admin-stat-label mb-0">Programs</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="tracer-card text-center py-3">
                <div class="admin-stat-value" data-count-target="{{ $totalSchoolYears }}">0</div>
                <p class="admin-stat-label mb-0">School Years</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="tracer-card text-center py-3">
                <div class="admin-stat-value" data-count-target="{{ $totalSurveysSubmitted }}">0</div>
                <p class="admin-stat-label mb-0">Surveys Submitted</p>
            </div>
        </div>
    </div>

    <div class="tracer-card">
        <h2>Configuration</h2>
        <div class="admin-quicklink-grid">
            <a href="{{ route('admin.users.index') }}" class="admin-quicklink">
                <span class="admin-quicklink-icon">@include('partials.icons.users')</span>
                Manage Users
            </a>
            <a href="{{ route('admin.programs.index') }}" class="admin-quicklink">
                <span class="admin-quicklink-icon">@include('partials.icons.programs')</span>
                Academic Programs
            </a>
            <a href="{{ route('admin.school-years.index') }}" class="admin-quicklink">
                <span class="admin-quicklink-icon">@include('partials.icons.school_years')</span>
                School Years
            </a>
            <a href="{{ route('admin.analytics') }}" class="admin-quicklink">
                <span class="admin-quicklink-icon">@include('partials.icons.analytics')</span>
                Analytics
            </a>
            <a href="{{ route('admin.templates') }}" class="admin-quicklink">
                <span class="admin-quicklink-icon">@include('partials.icons.templates')</span>
                Survey Templates
            </a>
            <a href="{{ route('admin.integrations') }}" class="admin-quicklink">
                <span class="admin-quicklink-icon">@include('partials.icons.integrations')</span>
                Integrations
            </a>
            <a href="{{ route('admin.map') }}" class="admin-quicklink">
                <span class="admin-quicklink-icon">@include('partials.icons.map')</span>
                View Map
            </a>
            <a href="{{ route('admin.audit-logs.index') }}" class="admin-quicklink">
                <span class="admin-quicklink-icon">@include('partials.icons.audit_logs')</span>
                Audit Logs
            </a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        document.querySelectorAll('.admin-stat-value[data-count-target]').forEach(function (el) {
            var target = parseInt(el.dataset.countTarget, 10) || 0;
            if (reduceMotion || target === 0) {
                el.textContent = target.toLocaleString();
                return;
            }
            var duration = 700;
            var start = null;
            var step = function (timestamp) {
                if (start === null) start = timestamp;
                var progress = Math.min((timestamp - start) / duration, 1);
                var eased = 1 - Math.pow(1 - progress, 3);
                el.textContent = Math.round(eased * target).toLocaleString();
                if (progress < 1) window.requestAnimationFrame(step);
            };
            window.requestAnimationFrame(step);
        });
    });
</script>

@include('partials.auto-refresh', ['seconds' => 180])
@endsection
