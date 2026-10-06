@extends('layouts.app')
@section('title', 'Analytics')

@section('content')
<div class="tracer-wrapper" style="max-width: 1000px;">
    <div class="tracer-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1>Analytics</h1>
            <p class="mb-0">Answer counts across all submitted Graduate Tracer surveys.</p>
        </div>
        <button type="button" class="btn-tracer-submit" data-bs-toggle="modal" data-bs-target="#csvPreviewModal">Export CSV</button>
    </div>

    @if (empty($charts))
        <div class="tracer-card text-center py-5">
            <p class="text-muted mb-0">No submitted surveys yet - results will appear here once graduates start submitting.</p>
        </div>
    @else
        @php
            // Charts listed here render as pie charts instead of bar rows -
            // matched against each chart's unique 'key' (see
            // AnalyticsController@buildCharts), not its question text, so
            // renamed questions can't silently fall back to a bar chart.
            $pieChartKeys = [
                'general_information_sex',
                'general_information_civil_status',
                'graduate_tracer_survey_advance_study_reason',
                'employment_data_employment_status',
                'employment_data_place_of_work',
                'employment_data_is_first_job',
                'employment_data_first_job_related_to_course',
                'employment_data_curriculum_relevant',
            ];
            // Monochromatic ramp (same 28deg hue as the rest of the palette)
            // used to tell pie slices apart - cycles if a chart somehow has
            // more answer options than colors.
            $pieRamp = [
                'var(--tracer-400)', 'var(--tracer-700)', 'var(--tracer-200)', 'var(--tracer-800)',
                'var(--tracer-300)', 'var(--tracer-900)', 'var(--tracer-100)', 'var(--tracer-600)',
            ];
        @endphp
        <div class="analytics-workspace">
        <aside class="analytics-summary">
            <span>Total respondents</span>
            <strong>{{ number_format($totalRespondents) }}</strong>
            <p>Completed Graduate Tracer submissions</p>
            <hr>
            @foreach ($sections as $section)
                <div class="analytics-summary-row"><span>{{ $section['title'] }}</span><b>{{ count($section['charts']) }}</b></div>
            @endforeach
        </aside>
        <div class="analytics-content">
        <div class="analytics-tabs" aria-label="Survey sections">
            @foreach ($sections as $sectionKey => $section)
                @continue(empty($section['charts']))
                <button type="button" class="analytics-tab" data-analytics-section="{{ $sectionKey }}" aria-pressed="false">{{ $section['title'] }} <span>{{ count($section['charts']) }}</span></button>
            @endforeach
        </div>
        @foreach ($sections as $sectionKey => $section)
            @continue(empty($section['charts']))
            <section class="analytics-section" data-section-panel="{{ $sectionKey }}" aria-label="{{ $section['title'] }}">

                @foreach ($section['charts'] as $chart)
                    @php
                        $total = array_sum($chart['counts']);
                        $isPie = in_array($chart['key'], $pieChartKeys) && $total > 0;
                    @endphp
                    <article class="tracer-card analytics-chart">
                        <div class="analytics-chart-heading"><h3 class="h6 mb-0">{{ $chart['question'] }}</h3><span class="analytics-count">{{ $total }}</span></div>
                        @if ($chart['key'] === 'graduates_by_school_year')
                            <p class="text-muted small">Graduates with completed surveys, grouped by their survey school year.</p>
                        @endif

                        @if ($isPie)
                            @php
                                $stops = [];
                                $cumulative = 0;
                                foreach ($chart['counts'] as $i => $count) {
                                    $slicePercent = $count / $total * 100;
                                    $color = $pieRamp[$i % count($pieRamp)];
                                    $stops[] = "{$color} {$cumulative}% " . ($cumulative + $slicePercent) . '%';
                                    $cumulative += $slicePercent;
                                }
                            @endphp
                            <div class="d-flex align-items-center flex-wrap" style="gap: 1.5rem;">
                                <div class="tracer-pie" aria-label="{{ $total }} answers" style="background: conic-gradient({{ implode(', ', $stops) }});"><div class="tracer-pie-center"><strong>{{ $total }}</strong><small>Total</small></div></div>
                                <div class="tracer-pie-legend">
                                    @foreach ($chart['labels'] as $i => $label)
                                        <div class="d-flex align-items-center mb-1" style="gap: .5rem;">
                                            <span class="tracer-pie-swatch" style="background: {{ $pieRamp[$i % count($pieRamp)] }};"></span>
                                            <span class="small">{{ $label }}</span>
                                            <span class="small text-muted ms-auto">{{ $chart['counts'][$i] }} ({{ round($chart['counts'][$i] / $total * 100) }}%)</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            @php $max = max($chart['counts']) ?: 1; @endphp
                            @foreach ($chart['labels'] as $i => $label)
                                <div class="d-flex align-items-center mb-2" style="gap: .75rem;">
                                    <div class="text-truncate small text-muted" style="width: 240px; flex-shrink: 0;" title="{{ $label }}">{{ $label }}</div>
                                    <div class="flex-grow-1 rounded-1" style="height: 20px; background: var(--tracer-neutral-100);">
                                        <div class="rounded-1 tracer-bar" style="height: 20px; width: {{ round($chart['counts'][$i] / $max * 100) }}%;"></div>
                                    </div>
                                    <div class="small fw-semibold text-end" style="width: 36px; flex-shrink: 0;">{{ $chart['counts'][$i] }}</div>
                                </div>
                            @endforeach
                        @endif
                    </article>
                @endforeach
            </section>
        @endforeach
        </div>
        </div>

    @endif
</div>

<div class="modal fade" id="csvPreviewModal" tabindex="-1" aria-labelledby="csvPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="csvPreviewModalLabel">CSV Preview</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
            <p class="text-muted small mb-3">Review the survey answer counts before downloading your CSV file.</p>
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
                        @if (empty($charts))
                            <tr><td colspan="3" class="text-center text-muted">No submitted survey results to preview. The CSV will contain column headers only.</td></tr>
                        @endif
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
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="{{ route('admin.analytics.export') }}" class="btn-tracer-submit">Download CSV</a>
            </div>
        </div>
    </div>
</div>

<style>
    .tracer-bar {
        background: linear-gradient(90deg, var(--tracer-600), var(--tracer-400));
        min-width: 2px;
    }

    .tracer-csv-preview td:nth-child(-n+2) {
        white-space: normal;
        min-width: 180px;
    }

    .tracer-pie {
        width: 140px;
        height: 140px;
        min-width: 140px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .tracer-pie-legend {
        flex: 1;
        min-width: 200px;
    }

    .tracer-pie-swatch {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 3px;
        flex-shrink: 0;
    }

    @media (max-width: 575.98px) {
        .tracer-pie {
            width: 110px;
            height: 110px;
            min-width: 110px;
        }
    }
</style>

@include('partials.auto-refresh', ['seconds' => 180])
@endsection

@section('scripts')
<script>
(() => {
    const buttons = Array.from(document.querySelectorAll('[data-analytics-section]'));
    const panels = Array.from(document.querySelectorAll('[data-section-panel]'));
    function selectSection(button) {
        buttons.forEach(item => item.setAttribute('aria-pressed', String(item === button)));
        panels.forEach(panel => panel.hidden = panel.dataset.sectionPanel !== button.dataset.analyticsSection);
    }
    buttons.forEach(button => button.addEventListener('click', () => selectSection(button)));
    if (buttons.length) selectSection(buttons[0]);
})();
</script>
@endsection
