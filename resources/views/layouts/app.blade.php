<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ff781a">
    <title>@yield('title', 'Graduate Tracer System')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="{{ asset('css/tracer.css') }}?v={{ filemtime(public_path('css/tracer.css')) }}" rel="stylesheet">
</head>
<body class="{{ auth()->check() ? 'role-'.auth()->user()->role : 'guest-page' }} page-{{ str_replace('.', '-', request()->route()?->getName() ?? 'home') }}">
    <a href="#mainContent" class="skip-link">Skip to main content</a>
    @auth
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
        <div class="container-fluid px-3">
            @if (in_array(auth()->user()->role, ['admin', 'faculty'], true))
                <button type="button" class="sidebar-toggle-btn" id="sidebarToggleBtn" aria-label="Toggle menu" aria-controls="tracerSidebar" aria-expanded="false">
                    <span class="nav-icon">@include('partials.icons.menu')</span>
                </button>
            @endif
            <a class="navbar-brand fw-bold" href="{{ route(auth()->user()->isAdmin() ? 'admin.dashboard' : (auth()->user()->isFaculty() ? 'faculty.dashboard' : 'user.dashboard')) }}">
                <span class="brand-mark" aria-hidden="true">@include('partials.icons.programs')</span>Graduate Tracer
            </a>
            <div class="d-flex align-items-center gap-3 ms-auto nav-actions">
                @if (auth()->user()->isUser())
                    <div class="graduate-nav d-flex align-items-center gap-2">
                        @foreach ([['user.dashboard', 'dashboard', 'Dashboard'], ['user.survey', 'survey', 'My Survey'], ['user.profile.edit', 'profile', 'My Profile']] as [$navRoute, $navIcon, $navLabel])
                            <a href="{{ route($navRoute) }}" class="nav-pill-link {{ request()->routeIs($navRoute) ? 'active' : '' }}">
                                <span class="nav-icon">@include('partials.icons.'.$navIcon)</span><span>{{ $navLabel }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
                @php
                    $userName = auth()->user()->name;
                    $userRoleLabel = ucfirst(auth()->user()->role);
                    $userInitials = collect(preg_split('/\s+/', trim($userName)))
                        ->filter()
                        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
                        ->take(2)->implode('');
                @endphp
                <div id="navAccountSection" class="d-flex align-items-center gap-3">
                    <span class="text-muted small nav-user-label nav-user-full">{{ $userName }} ({{ $userRoleLabel }})</span>
                    <span class="nav-user-label nav-user-initials" title="{{ $userName }} ({{ $userRoleLabel }})" aria-label="{{ $userName }} ({{ $userRoleLabel }})">{{ $userInitials }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary nav-pill-link nav-logout-btn">
                            <span class="nav-icon">@include('partials.icons.logout')</span><span class="nav-label">Log out</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
    @endauth

    <div class="tracer-body">
        @auth
            @if (in_array(auth()->user()->role, ['admin', 'faculty'], true))
                @include('partials.admin-sidebar')
            @endif
        @endauth

        <main class="tracer-page" id="mainContent" tabindex="-1">
            @yield('content')
            <footer class="page-footer">Graduate Tracer &middot; NEMSU &middot; Track. Connect. Grow.</footer>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    @auth
    @if (in_array(auth()->user()->role, ['admin', 'faculty'], true))
    <script>
        (function () {
            const toggleBtn = document.getElementById('sidebarToggleBtn');
            const closeBtn = document.getElementById('sidebarCloseBtn');
            const sidebar = document.getElementById('tracerSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');

            function openSidebar() {
                sidebar.inert = false;
                sidebar.classList.add('sidebar-open');
                backdrop.classList.add('sidebar-open');
                toggleBtn.setAttribute('aria-expanded', 'true');
                document.body.classList.add('menu-open');
                closeBtn.focus();
            }

            function closeSidebar() {
                sidebar.classList.remove('sidebar-open');
                backdrop.classList.remove('sidebar-open');
                toggleBtn.setAttribute('aria-expanded', 'false');
                sidebar.inert = mobileQuery.matches;
                document.body.classList.remove('menu-open');
            }

            toggleBtn.addEventListener('click', function () {
                sidebar.classList.contains('sidebar-open') ? closeSidebar() : openSidebar();
            });
            closeBtn.addEventListener('click', function () { closeSidebar(); toggleBtn.focus(); });
            backdrop.addEventListener('click', function () { closeSidebar(); toggleBtn.focus(); });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Tab' && mobileQuery.matches && sidebar.classList.contains('sidebar-open')) {
                    const focusable = Array.from(sidebar.querySelectorAll('a[href], button:not([disabled])'))
                        .filter(element => element.getClientRects().length);
                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];
                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                }
                if (event.key === 'Escape' && sidebar.classList.contains('sidebar-open')) {
                    closeSidebar();
                    toggleBtn.focus();
                }
            });

            // Move the single account/logout section into the mobile menu.
            // Primary links stay in the same sidebar at every breakpoint.
            const navActions = document.querySelector('.nav-actions');
            const navAccountSection = document.getElementById('navAccountSection');
            const sidebarAccountSlot = document.getElementById('sidebarAccountSlot');
            const mobileQuery = window.matchMedia('(max-width: 991.98px)');

            function applyResponsiveNav(e) {
                if (e.matches) {
                    sidebarAccountSlot.appendChild(navAccountSection);
                    closeSidebar();
                } else {
                    closeSidebar();
                    navActions.appendChild(navAccountSection);
                }
            }

            applyResponsiveNav(mobileQuery);
            mobileQuery.addEventListener('change', applyResponsiveNav);
        })();
    </script>
    @endif
    @endauth
    <script src="{{ asset('js/tracer-ui.js') }}?v={{ filemtime(public_path('js/tracer-ui.js')) }}" defer></script>
    @yield('scripts')
</body>
</html>
