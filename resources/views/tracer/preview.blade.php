@extends('layouts.app')

@section('title', 'My Survey Preview')

@section('content')
<div class="tracer-wrapper">
    <div class="tracer-header">
        <h1>My Graduate Tracer Survey</h1>
        <p>A read-only preview of what you submitted{{ $survey->submitted_at ? ' on ' . $survey->submitted_at->format('M j, Y') : '' }}.</p>
    </div>

    <div class="mb-3">
        <a href="{{ route('user.dashboard') }}" class="btn-tracer-prev d-inline-block">&larr; Back to Dashboard</a>
        <a href="{{ route('user.survey') }}" class="btn-tracer-next d-inline-block">Update my answers</a>
    </div>

    @include('tracer.partials.survey-readonly', ['survey' => $survey])
</div>
@endsection
