@extends('layouts.app')
@section('title', 'Access Survey Content')

@section('content')
<div class="tracer-wrapper" style="max-width: 500px;">
    <div class="tracer-header"><h1>Access Restricted</h1></div>

    <div class="alert alert-warning">
        This action is logged. Only view an individual's survey content when explicitly authorized (e.g. a complaint, a data-correction request, or a compliance review).
    </div>

    @error('export')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="tracer-card">
        <form method="POST" action="{{ route('admin.surveys.show', $survey) }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Reason for access <span class="text-danger">*</span></label>
                <textarea name="reason" class="form-control" rows="3" required minlength="10" placeholder="e.g. Verifying a data-correction request submitted by the graduate on..."></textarea>
            </div>
            <button type="submit" class="btn-tracer-submit">View Content</button>
            <button type="submit" formaction="{{ route('admin.surveys.export-docx', $survey) }}" class="btn-tracer-prev">Export as DOCX</button>
        </form>
    </div>
</div>
@endsection
