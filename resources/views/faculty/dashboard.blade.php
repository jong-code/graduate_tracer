@extends('layouts.app')
@section('title', 'Faculty Dashboard')

@section('content')
@php
    $employmentTotal = $employmentBreakdown->sum('total');
    $employedTotal = $employmentBreakdown->where('employment_status', 'yes')->sum('total');
    $relevanceTotal = $curriculumRelevance->sum('total');
    $relevantTotal = $curriculumRelevance->filter(fn ($row) => (bool) $row->curriculum_relevant)->sum('total');
    $employmentLabels = ['yes' => 'Employed', 'no' => 'Not currently employed', 'never_employed' => 'Never employed'];
    $tools = [
        ['route' => 'admin.analytics', 'icon' => 'analytics', 'title' => 'Analytics', 'description' => 'Explore survey results and export reports.'],
        ['route' => 'admin.templates', 'icon' => 'templates', 'title' => 'Survey Templates', 'description' => 'Preview surveys and download documents.'],
        ['route' => 'admin.integrations', 'icon' => 'integrations', 'title' => 'Integrations', 'description' => 'Manage graduate contact follow-ups.'],
        ['route' => 'admin.map', 'icon' => 'map', 'title' => 'View Map', 'description' => 'Explore graduate locations on the map.'],
    ];
@endphp
<div class="tracer-wrapper faculty-page">
    <header class="faculty-page-header">
        <div>
            <p class="faculty-eyebrow">Faculty workspace</p>
            <h1>Graduate outcomes at a glance</h1>
            <p class="faculty-intro">Explore completed surveys, compare programs, and access your reporting tools.</p>
        </div>
        <a href="{{ route('admin.analytics') }}" class="btn faculty-primary-action">View analytics <span aria-hidden="true">&rarr;</span></a>
    </header>

    <div class="faculty-scope-note">Reports include completed surveys from accounts currently assigned the User role. Drafts and Admin or Faculty accounts are excluded.</div>

    <section class="faculty-kpi-grid" aria-label="Survey highlights">
        <article class="faculty-stat">
            <span class="faculty-stat-label">Completed surveys</span>
            <strong>{{ number_format($totalSubmitted) }}</strong>
            <p>Graduate survey submissions</p>
        </article>
        <article class="faculty-stat">
            <span class="faculty-stat-label">Currently employed</span>
            <strong>{{ $employmentTotal ? round($employedTotal / $employmentTotal * 100).'%' : '—' }}</strong>
            <p>{{ number_format($employedTotal) }} of {{ number_format($employmentTotal) }} employment responses</p>
        </article>
        <article class="faculty-stat">
            <span class="faculty-stat-label">Curriculum relevance</span>
            <strong>{{ $relevanceTotal ? round($relevantTotal / $relevanceTotal * 100).'%' : '—' }}</strong>
            <p>{{ number_format($relevantTotal) }} of {{ number_format($relevanceTotal) }} first-job responses</p>
        </article>
    </section>

    <section class="faculty-tools-section" aria-labelledby="faculty-tools-heading">
        <h2 id="faculty-tools-heading">Your tools</h2>
        <div class="faculty-tools-grid">
            @foreach ($tools as $tool)
                <a href="{{ route($tool['route']) }}" class="faculty-tool-card">
                    <span class="faculty-tool-icon" aria-hidden="true">@include('partials.icons.'.$tool['icon'])</span>
                    <h3>{{ $tool['title'] }}</h3>
                    <p>{{ $tool['description'] }}</p>
                    <span class="faculty-tool-arrow" aria-hidden="true">&rarr;</span>
                </a>
            @endforeach
        </div>
    </section>

    <div class="faculty-report-grid">
        <section class="faculty-report-card" aria-labelledby="faculty-programs-heading">
            <div class="faculty-report-heading">
                <h2 id="faculty-programs-heading">Submissions by program</h2>
                <span>{{ $byProgram->count() }} programs</span>
            </div>
            <p class="faculty-report-description">Share of all completed graduate surveys, ordered by submission count.</p>
            @forelse ($byProgram as $row)
                @php($share = $totalSubmitted ? round($row->total / $totalSubmitted * 100) : 0)
                <div class="faculty-breakdown-row">
                    <div class="faculty-breakdown-label"><span>{{ $row->name }}</span><strong>{{ number_format($row->total) }} <small>({{ $share }}%)</small></strong></div>
                    <div class="faculty-progress" aria-hidden="true"><span style="width: {{ $share }}%"></span></div>
                </div>
            @empty
                <div class="faculty-empty-state"><strong>No completed surveys yet</strong><p>Program comparisons will appear when graduates submit their surveys.</p></div>
            @endforelse
        </section>

        <div class="faculty-report-stack">
            <section class="faculty-report-card" aria-labelledby="faculty-employment-heading">
                <h2 id="faculty-employment-heading">Employment status</h2>
                <p class="faculty-report-description">Based on {{ number_format($employmentTotal) }} employment responses.</p>
                @forelse ($employmentBreakdown as $row)
                    @php($share = $employmentTotal ? round($row->total / $employmentTotal * 100) : 0)
                    <div class="faculty-breakdown-row">
                        <div class="faculty-breakdown-label"><span>{{ $employmentLabels[$row->employment_status] ?? 'Not specified' }}</span><strong>{{ number_format($row->total) }} <small>({{ $share }}%)</small></strong></div>
                        <div class="faculty-progress" aria-hidden="true"><span style="width: {{ $share }}%"></span></div>
                    </div>
                @empty
                    <div class="faculty-empty-state"><p>No employment responses available yet.</p></div>
                @endforelse
            </section>
            <section class="faculty-report-card" aria-labelledby="faculty-relevance-heading">
                <h2 id="faculty-relevance-heading">Curriculum &amp; first job</h2>
                <p class="faculty-report-description">Was the graduate's curriculum relevant to their first job? Unanswered responses are excluded.</p>
                @if ($relevanceTotal)
                    <div class="faculty-relevance-grid">
                        <div><span>Relevant</span><strong>{{ number_format($relevantTotal) }}</strong></div>
                        <div><span>Not relevant</span><strong>{{ number_format($relevanceTotal - $relevantTotal) }}</strong></div>
                    </div>
                @else
                    <div class="faculty-empty-state"><p>No curriculum relevance responses available yet.</p></div>
                @endif
            </section>
        </div>
    </div>
    <p class="faculty-report-footer">Dashboard summaries do not display individual graduate names or contact details.</p>
</div>
@endsection
