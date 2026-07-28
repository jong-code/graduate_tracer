@extends('layouts.app')
@section('title', 'Analytics')

@section('content')
<div class="tracer-wrapper" style="max-width: 1000px;">
    <div class="tracer-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1>Analytics</h1>
            <p class="mb-0">Answer counts across all submitted Graduate Tracer surveys.</p>
        </div>
        <a href="{{ route('admin.analytics.export') }}" class="btn-tracer-submit">Export CSV</a>
    </div>

    @if (empty($charts))
        <div class="tracer-card text-center py-5">
            <p class="text-muted mb-0">No submitted surveys yet - results will appear here once graduates start submitting.</p>
        </div>
    @else
        <div class="tracer-card">
            <h2 class="h6 mb-1">CSV Preview</h2>
            <p class="text-muted small mb-3">Exactly what "Export CSV" downloads - same rows, same order.</p>
            <div class="table-responsive">
                <table class="table table-sm table-striped tracer-csv-preview mb-0">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Answer</th>
                            <th class="text-end">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($charts as $chart)
                            @foreach ($chart['labels'] as $i => $label)
                                <tr>
                                    <td>{{ $chart['question'] }}</td>
                                    <td>{{ $label }}</td>
                                    <td class="text-end">{{ $chart['counts'][$i] }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
