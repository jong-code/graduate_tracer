@extends('layouts.app')
@section('title', 'Admin Dashboard')

@section('content')
<div class="tracer-wrapper admin-page">
    <header class="tracer-header">
        <p class="workspace-eyebrow">Admin workspace</p>
        <h1>Graduate Tracer Dashboard</h1>
        <p>System overview: graduates, academic programs, and survey activity.</p>
    </header>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    <section class="workspace-metrics" aria-label="System summary">
        @foreach ([
            ['label' => 'Accounts', 'count' => $totalUsers, 'route' => 'admin.users.index', 'action' => 'Manage accounts'],
            ['label' => 'Academic programs', 'count' => $totalPrograms, 'route' => 'admin.programs.index', 'action' => 'View programs'],
            ['label' => 'School years', 'count' => $totalSchoolYears, 'route' => 'admin.school-years.index', 'action' => 'Manage batches'],
            ['label' => 'Completed surveys', 'count' => $totalSurveysSubmitted, 'route' => 'admin.analytics', 'action' => 'Explore outcomes'],
        ] as $metric)
            <a href="{{ route($metric['route']) }}" class="workspace-metric">
                <span>{{ $metric['label'] }}</span><strong>{{ number_format($metric['count']) }}</strong>
                <small>{{ $metric['action'] }} <span aria-hidden="true">&rarr;</span></small>
            </a>
        @endforeach
    </section>

    <h2 class="h5 fw-bold mb-3">Manage your workspace</h2>
    <div class="workspace-shortcuts">
        @foreach ([
            ['route' => 'admin.users.index', 'icon' => 'users', 'title' => 'Users', 'description' => 'Manage graduate accounts, roles, and account status.'],
            ['route' => 'admin.programs.index', 'icon' => 'programs', 'title' => 'Programs', 'description' => 'Organize departments and academic programs.'],
            ['route' => 'admin.school-years.index', 'icon' => 'school_years', 'title' => 'School Years', 'description' => 'Manage graduation batches and the current year.'],
            ['route' => 'admin.audit-logs.index', 'icon' => 'audit_logs', 'title' => 'Audit Logs', 'description' => 'Review recorded administrative activity.'],
            ['route' => 'admin.analytics', 'icon' => 'analytics', 'title' => 'Analytics', 'description' => 'Explore completed graduate survey results.'],
            ['route' => 'admin.templates', 'icon' => 'templates', 'title' => 'Survey Templates', 'description' => 'Preview submissions and export survey documents.'],
            ['route' => 'admin.integrations', 'icon' => 'integrations', 'title' => 'Integrations', 'description' => 'Track graduate rewards and contact follow-ups.'],
            ['route' => 'admin.map', 'icon' => 'map', 'title' => 'View Map', 'description' => 'Explore locations recorded with graduate consent.'],
        ] as $link)
            <a href="{{ route($link['route']) }}" class="workspace-shortcut">
                <span class="nav-icon" aria-hidden="true">@include('partials.icons.'.$link['icon'])</span>
                <strong>{{ $link['title'] }}</strong><p>{{ $link['description'] }}</p>
            </a>
        @endforeach
    </div>
</div>
@include('partials.auto-refresh', ['seconds' => 180])
@endsection
