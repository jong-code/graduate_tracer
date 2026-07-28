@extends('layouts.app')

@section('title', 'Survey Preview')

@section('content')
<div class="tracer-wrapper">
    <div class="tracer-header">
        <h1>{{ $survey->generalInformation?->name ?? 'Graduate' }}'s Survey</h1>
        <p>A read-only preview{{ $survey->submitted_at ? ' - submitted ' . $survey->submitted_at->format('M j, Y') : '' }}.</p>
    </div>

    <div class="mb-3">
        <a href="{{ route('admin.templates') }}" class="btn-tracer-prev d-inline-block">&larr; Back to Survey Templates</a>
        <a href="{{ route('admin.templates.survey-export', $survey) }}" class="btn-tracer-next d-inline-block">Export as DOCX</a>
    </div>

    @include('tracer.partials.survey-readonly', ['survey' => $survey])
</div>
@endsection
