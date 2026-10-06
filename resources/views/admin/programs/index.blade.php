@extends('layouts.app')
@section('title', 'Academic Programs')

@section('content')
<div class="tracer-wrapper" style="max-width: 800px;">
    <div class="tracer-header"><h1>Academic Programs</h1><p>Organize departments and the programs available to graduates.</p></div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="tracer-card mb-3">
        <h2>Departments</h2>
        <form method="POST" action="{{ route('admin.departments.store') }}" class="mb-3">
            @csrf
            <div class="row">
                <div class="col-md-7 mb-2"><label class="form-label">Name</label><input type="text" name="name" class="form-control" placeholder="Department name (e.g. College of Information and Technology Education)" required></div>
                <div class="col-md-3 mb-2"><label class="form-label">Code</label><input type="text" name="code" class="form-control" placeholder="Code (e.g. CITE)" required></div>
                <div class="col-md-2 mb-2"><button type="submit" class="btn-tracer-submit w-100">Add department</button></div>
            </div>
        </form>

        @if ($departments->isEmpty())
            <p class="text-muted mb-0">No departments yet - add one above before adding programs under it.</p>
        @else
            <div class="table-responsive" role="region" aria-label="Records" tabindex="0">
        <table class="table table-sm mb-0">
                <thead><tr><th>Name</th><th>Code</th></tr></thead>
                <tbody>
                    @foreach ($departments as $d)
                        <tr><td>{{ $d->name }}</td><td>{{ $d->code }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <div class="tracer-card mb-3">
        <h2>Add a Program</h2>
        <form method="POST" action="{{ route('admin.programs.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-4 mb-2"><label class="form-label">Name</label><input type="text" name="name" class="form-control" placeholder="Program name (e.g. BS Computer Science)" required></div>
                <div class="col-md-3 mb-2"><label class="form-label">Code</label><input type="text" name="code" class="form-control" placeholder="Code (e.g. BSCS)" required></div>
                <div class="col-md-5 mb-2">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-select">
                        <option value="">No department</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->code }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="btn-tracer-submit">Add program</button>
        </form>
    </div>

    <div class="tracer-card">
        <div class="table-responsive" role="region" aria-label="Records" tabindex="0">
        <table class="table">
            <thead><tr><th>Name</th><th>Code</th><th>Department</th><th>Status</th></tr></thead>
            <tbody>
                @foreach ($programs as $p)
                    <tr>
                        <td>{{ $p->name }}</td>
                        <td>{{ $p->code }}</td>
                        <td>{{ $p->department->name ?? '—' }}</td>
                        <td>{{ $p->is_active ? 'Active' : 'Inactive' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
