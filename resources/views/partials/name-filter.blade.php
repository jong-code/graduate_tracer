<form method="GET" action="{{ $action }}" class="name-filter mb-3" data-realtime-name-search data-results-target="{{ $target }}">
    <div class="name-filter-input">
        <label for="nameSearch" class="visually-hidden">Search by name or email</label>
        <span aria-hidden="true">&#8981;</span>
        <input id="nameSearch" type="search" name="search" class="form-control" placeholder="Search by name or email..." value="{{ request('search', '') }}" autocomplete="off" aria-describedby="nameSearchStatus">
    </div>
    @if ($userFilters ?? false)
        <select name="role" class="form-select" aria-label="Filter by role">
            <option value="">All Roles</option>
            @foreach (['user' => 'User', 'faculty' => 'Faculty', 'admin' => 'Admin'] as $value => $label)
                <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="status" class="form-select" aria-label="Filter by account status">
            <option value="">All Status</option>
            <option value="active" @selected(request('status') === 'active')>Active</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
        </select>
        <select name="verified" class="form-select" aria-label="Filter by email verification">
            <option value="">All Email Verification</option>
            <option value="yes" @selected(request('verified') === 'yes')>Verified</option>
            <option value="no" @selected(request('verified') === 'no')>Unverified</option>
        </select>
    @endif
    @if ($sortable ?? false)
        <select name="sort" class="form-select" aria-label="Sort by name">
            <option value="asc" @selected(request('sort', 'asc') === 'asc')>Name: A &rarr; Z</option>
            <option value="desc" @selected(request('sort') === 'desc')>Name: Z &rarr; A</option>
        </select>
    @endif
    <button class="btn btn-outline-secondary" data-search-submit>Search</button>
    <a href="{{ $action }}" class="btn btn-outline-secondary">Reset</a>
    <span id="nameSearchStatus" class="search-status" data-search-status role="status" aria-live="polite"></span>
</form>
