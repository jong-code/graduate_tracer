<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Graduate Tracer System')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/tracer.css') }}" rel="stylesheet">
</head>
<body>
    @auth
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
        <div class="container-fluid px-3">
            <span class="navbar-brand fw-bold" style="color: var(--tracer-orange, #f0a058);">Graduate Tracer</span>
            <div class="d-flex align-items-center gap-3 ms-auto">
                @if (auth()->user()->isUser())
                    <a href="{{ route('user.dashboard') }}" class="nav-pill-link {{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
                        <span class="nav-icon">@include('partials.icons.dashboard')</span><span class="nav-label">Dashboard</span>
                    </a>
                    <a href="{{ route('user.survey') }}" class="nav-pill-link {{ request()->routeIs('user.survey') ? 'active' : '' }}">
                        <span class="nav-icon">@include('partials.icons.survey')</span><span class="nav-label">Survey</span>
                    </a>
                    <a href="{{ route('user.profile.edit') }}" class="nav-pill-link {{ request()->routeIs('user.profile.edit') ? 'active' : '' }}">
                        <span class="nav-icon">@include('partials.icons.profile')</span><span class="nav-label">Profile</span>
                    </a>
                @elseif (auth()->user()->isFaculty())
                    <a href="{{ route('faculty.dashboard') }}" class="nav-pill-link {{ request()->routeIs('faculty.dashboard') ? 'active' : '' }}">
                        <span class="nav-icon">@include('partials.icons.dashboard')</span><span class="nav-label">Dashboard</span>
                    </a>
                @elseif (auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="nav-pill-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <span class="nav-icon">@include('partials.icons.dashboard')</span><span class="nav-label">Dashboard</span>
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="nav-pill-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <span class="nav-icon">@include('partials.icons.users')</span><span class="nav-label">Users</span>
                    </a>
                    <a href="{{ route('admin.programs.index') }}" class="nav-pill-link {{ request()->routeIs('admin.programs.*') ? 'active' : '' }}">
                        <span class="nav-icon">@include('partials.icons.programs')</span><span class="nav-label">Programs</span>
                    </a>
                    <a href="{{ route('admin.school-years.index') }}" class="nav-pill-link {{ request()->routeIs('admin.school-years.*') ? 'active' : '' }}">
                        <span class="nav-icon">@include('partials.icons.school_years')</span><span class="nav-label">School Years</span>
                    </a>
                    <a href="{{ route('admin.audit-logs.index') }}" class="nav-pill-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">
                        <span class="nav-icon">@include('partials.icons.audit_logs')</span><span class="nav-label">Audit Logs</span>
                    </a>
                @endif
                <span class="text-muted small nav-user-label">{{ auth()->user()->name }} ({{ ucfirst(auth()->user()->role) }})</span>
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary nav-pill-link nav-logout-btn">
                        <span class="nav-icon">@include('partials.icons.logout')</span><span class="nav-label">Log out</span>
                    </button>
                </form>
            </div>
        </div>
    </nav>
    @endauth

    @yield('content')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
