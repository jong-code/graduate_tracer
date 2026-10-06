{{--
    Auto-refresh indicator: reloads the page on a timer so admin/faculty
    dashboards stay reasonably current without a manual refresh.

    Usage: @include('partials.auto-refresh', ['seconds' => 180])
    `seconds` defaults to 180 (3 minutes) if omitted.

    Only include this on pages that are purely read-only "glance at data"
    screens. Never include it on a page with a form (survey wizard,
    profile, any create/edit screen) - reloading mid-fill would silently
    wipe out unsaved input.
--}}
@php
    $seconds = $seconds ?? 180;
@endphp
<div class="auto-refresh-indicator" id="autoRefreshIndicator">
    <span id="autoRefreshText"></span>
    <button type="button" id="autoRefreshToggle" class="auto-refresh-toggle-btn"></button>
</div>

<script>
(function () {
    const totalSeconds = {{ (int) $seconds }};
    // Scoped per browser tab (not persisted across visits) and per page
    // path, so pausing on one dashboard doesn't silently pause another.
    const storageKey = 'gts-auto-refresh-paused:' + window.location.pathname;

    const textEl = document.getElementById('autoRefreshText');
    const toggleBtn = document.getElementById('autoRefreshToggle');

    let remaining = totalSeconds;
    let paused = sessionStorage.getItem(storageKey) === '1';

    function formatTime(s) {
        const m = Math.floor(s / 60);
        const sec = s % 60;
        return m + ':' + String(sec).padStart(2, '0');
    }

    function render() {
        if (paused) {
            textEl.textContent = 'Auto-refresh paused';
            toggleBtn.textContent = 'Resume';
        } else {
            textEl.textContent = 'Refreshing in ' + formatTime(remaining);
            toggleBtn.textContent = 'Pause';
        }
    }

    toggleBtn.addEventListener('click', function () {
        paused = !paused;
        sessionStorage.setItem(storageKey, paused ? '1' : '0');
        if (!paused) {
            remaining = totalSeconds;
        }
        render();
    });

    render();

    setInterval(function () {
        // Don't burn down the countdown (or reload) while the admin isn't
        // even looking at this tab - resumes counting down, doesn't reset,
        // once it's visible again.
        if (paused || document.hidden || document.querySelector('.modal.show')) {
            return;
        }
        remaining -= 1;
        if (remaining <= 0) {
            window.location.reload();
            return;
        }
        render();
    }, 1000);
})();
</script>
