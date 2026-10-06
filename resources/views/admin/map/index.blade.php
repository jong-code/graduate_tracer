@extends('layouts.app')
@section('title', 'View Map')

@section('content')
<div class="tracer-wrapper" style="max-width: 1000px;">
    <div class="tracer-header">
        <h1>Graduate Location Map</h1>
        <p>Locations recorded at survey submission time, via each graduate's Disclosure &amp; Consent Agreement.</p>
    </div>

    <div class="tracer-card mb-3">
        <div class="d-flex flex-wrap align-items-center gap-2 justify-content-between">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <label for="schoolYearFilter" class="form-label mb-0">School Year</label>
                <select id="schoolYearFilter" class="form-select" style="min-width: 180px;">
                    <option value="">All school years</option>
                    @foreach ($schoolYears as $sy)
                        <option value="{{ $sy->id }}">{{ $sy->label }}</option>
                    @endforeach
                </select>
            </div>
            <p class="text-muted small mb-0" id="mapCount"></p>
        </div>
        <p class="small mb-0 mt-2" id="adminLocationStatus"></p>
    </div>

    <div class="tracer-card p-0 overflow-hidden">
        <div id="map" style="height: 560px;"></div>
    </div>
    <p class="text-muted small mt-2 mb-0">Click a marker to see its distance from your current location and open that graduate's survey preview. Locations refresh automatically every 3 minutes.</p>
</div>

<div class="modal fade" id="locationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="locationModalTitle">Graduate location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1"><strong id="locationModalProgram"></strong></p>
                <p class="text-muted small mb-3" id="locationModalSchoolYear"></p>
                <p class="mb-0" id="locationModalDistance"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="#" id="locationModalPreviewLink" target="_blank" rel="noopener" class="btn-tracer-submit">View survey preview</a>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
{{-- This page initializes its modal before the shared layout footer loads. --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
(function () {
    // Centered roughly on the Philippines/Caraga region by default - just
    // a starting viewport, markers still plot wherever they actually are.
    const map = L.map('map').setView([9.5, 125.5], 7);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        referrerPolicy: 'strict-origin-when-cross-origin',
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);

    let markers = L.layerGroup().addTo(map);
    let adminCoords = null; // { lat, lng } once geolocation succeeds - kept for distance math only, never shown as its own marker
    let currentPoints = [];

    const filterEl = document.getElementById('schoolYearFilter');
    const countEl = document.getElementById('mapCount');
    const adminStatusEl = document.getElementById('adminLocationStatus');
    const locationModal = new bootstrap.Modal(document.getElementById('locationModal'));

    // ---- Haversine formula: great-circle distance between two lat/lng
    // points, in kilometers. ----
    function haversineKm(lat1, lng1, lat2, lng2) {
        const R = 6371; // Earth's mean radius, km
        const toRad = deg => deg * Math.PI / 180;
        const dLat = toRad(lat2 - lat1);
        const dLng = toRad(lng2 - lng1);
        const a = Math.sin(dLat / 2) ** 2 +
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function formatDistance(km) {
        return km < 1 ? Math.round(km * 1000) + ' m away' : km.toFixed(1) + ' km away';
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    // ---- Request the admin's own location, silently, purely to have
    // something to measure distance from when a marker is clicked. No
    // marker or pin for the admin's own position is ever shown on the map. ----
    function requestAdminLocation() {
        if (!('geolocation' in navigator)) {
            adminStatusEl.innerHTML = '<span class="text-muted">Your browser does not support location services, so distances can\'t be calculated.</span>';
            return;
        }

        adminStatusEl.innerHTML = '<span class="text-muted">Requesting your location to calculate distances…</span>';

        navigator.geolocation.getCurrentPosition(
            function (position) {
                adminCoords = { lat: position.coords.latitude, lng: position.coords.longitude };
                adminStatusEl.innerHTML = '<span class="text-success">Location enabled - click a marker to see its distance from you.</span>';
            },
            function () {
                adminCoords = null;
                adminStatusEl.innerHTML = '<span class="text-muted">Location is off, so distances can\'t be shown. ' +
                    '<a href="#" id="retryAdminLocation">Turn on location and try again</a>.</span>';
                document.getElementById('retryAdminLocation').addEventListener('click', function (e) {
                    e.preventDefault();
                    requestAdminLocation();
                });
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    }

    function openLocationModal(point) {
        document.getElementById('locationModalTitle').textContent = point.name;
        document.getElementById('locationModalProgram').textContent = point.program;
        document.getElementById('locationModalSchoolYear').textContent = 'S.Y. ' + point.school_year;
        document.getElementById('locationModalDistance').innerHTML = adminCoords
            ? 'Distance from you: <strong>' + formatDistance(haversineKm(adminCoords.lat, adminCoords.lng, point.lat, point.lng)) + '</strong>'
            : '<span class="text-muted">Turn on your location to see the distance to this graduate.</span>';
        document.getElementById('locationModalPreviewLink').href = point.preview_url;
        locationModal.show();
    }

    function renderMarkers() {
        markers.clearLayers();

        currentPoints.forEach(point => {
            const marker = L.marker([point.lat, point.lng]);
            marker.bindTooltip(escapeHtml(point.name) + ' - ' + escapeHtml(point.program));
            marker.on('click', function () {
                openLocationModal(point);
            });
            markers.addLayer(marker);
        });

        countEl.textContent = currentPoints.length + (currentPoints.length === 1 ? ' location' : ' locations') + ' shown';
    }

    function loadLocations() {
        const schoolYearId = filterEl.value;
        const url = new URL(@json(route('admin.map.locations')));
        if (schoolYearId) {
            url.searchParams.set('school_year_id', schoolYearId);
        }

        fetch(url)
            .then(response => response.json())
            .then(points => {
                currentPoints = points;
                renderMarkers();

                if (points.length > 0) {
                    map.fitBounds(L.latLngBounds(points.map(p => [p.lat, p.lng])), { padding: [30, 30], maxZoom: 14 });
                }
            });
    }

    filterEl.addEventListener('change', loadLocations);
    requestAdminLocation();
    loadLocations();

    // Lighter-weight than a full page reload (used on the other admin
    // dashboards) - this page has map pan/zoom state and an already-granted
    // location permission that a full reload would needlessly disturb, so
    // it just re-fetches marker data on the same timer instead.
    setInterval(loadLocations, 180000);
})();
</script>
@endsection
