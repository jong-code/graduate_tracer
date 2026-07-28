@extends('layouts.app')
@section('title', $title)

@section('content')
<div class="tracer-wrapper" style="max-width: 700px;">
    <div class="tracer-header"><h1>{{ $title }}</h1></div>
    <div class="tracer-card text-center py-5">
        <p class="text-muted mb-0">This section is scaffolded but not yet built out — it needs concrete requirements (which settings, template types, integration targets, or backup schedule/destination) before implementation.</p>
    </div>
</div>
@endsection
