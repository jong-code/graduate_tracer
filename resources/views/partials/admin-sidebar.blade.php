{{-- One role-aware navigation for desktop and mobile. No duplicate links or logout forms. --}}
@php
    $viewer = auth()->user();
    $workspaceLabel = $viewer->isAdmin() ? 'Admin' : ($viewer->isFaculty() ? 'Faculty' : 'Graduate');
    $mainLinks = $viewer->isAdmin() ? [
        ['route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'icon' => 'dashboard', 'label' => 'Dashboard'],
        ['route' => 'admin.users.index', 'match' => 'admin.users.*', 'icon' => 'users', 'label' => 'Users'],
        ['route' => 'admin.programs.index', 'match' => 'admin.programs.*', 'icon' => 'programs', 'label' => 'Programs'],
        ['route' => 'admin.school-years.index', 'match' => 'admin.school-years.*', 'icon' => 'school_years', 'label' => 'School Years'],
        ['route' => 'admin.audit-logs.index', 'match' => 'admin.audit-logs.*', 'icon' => 'audit_logs', 'label' => 'Audit Logs'],
    ] : ($viewer->isFaculty() ? [
        ['route' => 'faculty.dashboard', 'match' => 'faculty.dashboard', 'icon' => 'dashboard', 'label' => 'Dashboard'],
    ] : [
        ['route' => 'user.dashboard', 'match' => 'user.dashboard', 'icon' => 'dashboard', 'label' => 'Dashboard'],
        ['route' => 'user.survey', 'match' => 'user.survey*', 'icon' => 'survey', 'label' => 'My Survey'],
        ['route' => 'user.profile.edit', 'match' => 'user.profile.*', 'icon' => 'profile', 'label' => 'My Profile'],
    ]);
    $toolLinks = [
        ['route' => 'admin.analytics', 'match' => 'admin.analytics*', 'icon' => 'analytics', 'label' => 'Analytics'],
        ['route' => 'admin.templates', 'match' => 'admin.templates*', 'icon' => 'templates', 'label' => 'Survey Templates'],
        ['route' => 'admin.integrations', 'match' => 'admin.integrations*', 'icon' => 'integrations', 'label' => 'Integrations'],
        ['route' => 'admin.map', 'match' => 'admin.map*', 'icon' => 'map', 'label' => 'View Map'],
    ];
@endphp
<div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
<nav class="tracer-sidebar" id="tracerSidebar" aria-label="{{ $workspaceLabel }} navigation">
    <div class="tracer-sidebar-header d-lg-none">
        <span>{{ $workspaceLabel }} Menu</span>
        <button type="button" class="tracer-sidebar-close" id="sidebarCloseBtn" aria-label="Close menu">&times;</button>
    </div>
    <div class="staff-sidebar-label">Main</div>
    @foreach ($mainLinks as $link)
        <a href="{{ route($link['route']) }}" class="tracer-sidebar-link {{ request()->routeIs($link['match']) ? 'active' : '' }}" @if(request()->routeIs($link['match'])) aria-current="page" @endif>
            <span class="nav-icon" aria-hidden="true">@include('partials.icons.'.$link['icon'])</span><span>{{ $link['label'] }}</span>
        </a>
    @endforeach
    @if ($viewer->isAdmin() || $viewer->isFaculty())
        <div class="staff-sidebar-label">Tools</div>
        @foreach ($toolLinks as $link)
            <a href="{{ route($link['route']) }}" class="tracer-sidebar-link {{ request()->routeIs($link['match']) ? 'active' : '' }}" @if(request()->routeIs($link['match'])) aria-current="page" @endif>
                <span class="nav-icon" aria-hidden="true">@include('partials.icons.'.$link['icon'])</span><span>{{ $link['label'] }}</span>
            </a>
        @endforeach
    @endif
    <div class="sidebar-purpose">
        <span class="sidebar-purpose-mark" aria-hidden="true">@include('partials.icons.programs')</span>
        <strong>Empowering our graduates</strong>
        <p>Track. Connect. Build a brighter future.</p>
    </div>
    <div id="sidebarAccountSlot" class="tracer-sidebar-slot tracer-sidebar-account"></div>
</nav>
