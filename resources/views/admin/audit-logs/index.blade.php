@extends('layouts.app')
@section('title', 'Audit Logs')

@section('content')
<div class="tracer-wrapper" style="max-width: 900px;">
    <div class="tracer-header"><h1>Audit Logs</h1><p>Review recorded system activity and administrative actions.</p></div>

    <div class="tracer-card">
        <div class="table-responsive" role="region" aria-label="Records" tabindex="0">
        <table class="table table-sm">
            <thead><tr><th>When</th><th>Actor</th><th>Action</th><th>Subject</th><th>Details</th></tr></thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td>{{ $log->created_at->format('M j, Y g:i A') }}</td>
                        <td>{{ $log->user->name ?? 'System' }}</td>
                        <td>{{ $log->action }}</td>
                        <td>{{ class_basename($log->subject_type) }} #{{ $log->subject_id }}</td>
                        <td class="small text-muted">{{ json_encode($log->meta) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">No entries yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        {{ $logs->links() }}
    </div>
</div>

@include('partials.auto-refresh', ['seconds' => 120])
@endsection
