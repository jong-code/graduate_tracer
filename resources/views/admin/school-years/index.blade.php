@extends('layouts.app')
@section('title', 'School Years')

@section('content')
<div class="tracer-wrapper" style="max-width: 600px;">
    <div class="tracer-header"><h1>School Years</h1></div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="tracer-card mb-3">
        <form method="POST" action="{{ route('admin.school-years.store') }}" class="d-flex gap-2">
            @csrf
            <input type="text" name="label" class="form-control" placeholder="e.g. 2025-2026" required>
            <button type="submit" class="btn-tracer-submit text-nowrap">Add</button>
        </form>
    </div>

    <div class="tracer-card">
        <table class="table">
            <thead><tr><th>Label</th><th>Current</th><th></th></tr></thead>
            <tbody>
                @foreach ($schoolYears as $sy)
                    <tr>
                        <td>{{ $sy->label }}</td>
                        <td>{{ $sy->is_current ? '✓ Current' : '' }}</td>
                        <td class="text-end">
                            @unless ($sy->is_current)
                                <form method="POST" action="{{ route('admin.school-years.set-current', $sy) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Set as current</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
