@extends('layouts.app')
@section('title', 'Academic Programs')

@section('content')
<div class="tracer-wrapper" style="max-width: 700px;">
    <div class="tracer-header"><h1>Academic Programs</h1></div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="tracer-card mb-3">
        <h2>Add a Program</h2>
        <form method="POST" action="{{ route('admin.programs.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-5 mb-2"><input type="text" name="name" class="form-control" placeholder="Program name (e.g. BS Computer Science)" required></div>
                <div class="col-md-3 mb-2"><input type="text" name="code" class="form-control" placeholder="Code (e.g. BSCS)" required></div>
                <div class="col-md-4 mb-2"><input type="text" name="college" class="form-control" placeholder="College (optional)"></div>
            </div>
            <button type="submit" class="btn-tracer-submit">Add</button>
        </form>
    </div>

    <div class="tracer-card">
        <table class="table">
            <thead><tr><th>Name</th><th>Code</th><th>College</th><th>Status</th></tr></thead>
            <tbody>
                @foreach ($programs as $p)
                    <tr>
                        <td>{{ $p->name }}</td>
                        <td>{{ $p->code }}</td>
                        <td>{{ $p->college ?? '—' }}</td>
                        <td>{{ $p->is_active ? 'Active' : 'Inactive' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
