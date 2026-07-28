@extends('layouts.app')
@section('title', 'Faculty Dashboard')

@section('content')
<div class="tracer-wrapper" style="max-width: 900px;">
    <div class="tracer-header">
        <h1>Aggregate Reports</h1>
        <p>Read-only view. No individual records, names, or contact details are shown here.</p>
    </div>

    <div class="tracer-card mb-3">
        <h2>{{ $totalSubmitted }}</h2>
        <p class="text-muted mb-0">Total surveys submitted</p>
    </div>

    <div class="tracer-card mb-3">
        <h2>Employment Status Breakdown</h2>
        <table class="table table-sm">
            <thead><tr><th>Status</th><th class="text-end">Count</th></tr></thead>
            <tbody>
                @forelse ($employmentBreakdown as $row)
                    <tr><td>{{ ucfirst(str_replace('_', ' ', $row->employment_status)) }}</td><td class="text-end">{{ $row->total }}</td></tr>
                @empty
                    <tr><td colspan="2" class="text-muted">No data yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="tracer-card mb-3">
        <h2>Submissions by Program</h2>
        <table class="table table-sm">
            <thead><tr><th>Program</th><th class="text-end">Count</th></tr></thead>
            <tbody>
                @forelse ($byProgram as $row)
                    <tr><td>{{ $row->name }}</td><td class="text-end">{{ $row->total }}</td></tr>
                @empty
                    <tr><td colspan="2" class="text-muted">No data yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="tracer-card">
        <h2>Curriculum Relevance (First Job)</h2>
        <table class="table table-sm">
            <thead><tr><th>Relevant?</th><th class="text-end">Count</th></tr></thead>
            <tbody>
                @forelse ($curriculumRelevance as $row)
                    <tr><td>{{ $row->curriculum_relevant ? 'Yes' : 'No' }}</td><td class="text-end">{{ $row->total }}</td></tr>
                @empty
                    <tr><td colspan="2" class="text-muted">No data yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
