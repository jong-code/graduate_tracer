<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GraduateTracerSurvey;
use Illuminate\Support\Facades\DB;

/**
 * Replaces the old unbuilt "System Settings" stub. Aggregates answer counts
 * for every closed-choice question in the GTS survey (single-select columns
 * and multi-select child tables alike), for both the on-screen charts and
 * the CSV export - both are built from the same dataset so they can never
 * drift out of sync.
 */
class AnalyticsController extends Controller
{
    private const SEX_LABELS = ['male' => 'Male', 'female' => 'Female'];

    private const CIVIL_STATUS_LABELS = [
        'single' => 'Single', 'married' => 'Married', 'separated' => 'Separated',
        'single_parent' => 'Single Parent', 'widow_widower' => 'Widow or Widower',
    ];

    private const EMPLOYMENT_STATUS_LABELS = ['yes' => 'Yes', 'no' => 'No', 'never_employed' => 'Never Employed'];

    private const PRESENT_EMPLOYMENT_STATUS_LABELS = [
        'regular_permanent' => 'Regular or Permanent', 'temporary' => 'Temporary', 'casual' => 'Casual',
        'contractual' => 'Contractual', 'self_employed' => 'Self-employed',
    ];

    private const PLACE_OF_WORK_LABELS = ['local' => 'Local', 'abroad' => 'Abroad'];

    private const YES_NO_LABELS = ['1' => 'Yes', '0' => 'No'];

    private const ADVANCE_STUDY_REASON_LABELS = [
        'promotion' => 'For promotion', 'professional_development' => 'For professional development', 'others' => 'Others',
    ];

    private const NOT_EMPLOYED_REASON_LABELS = [
        'advance_study' => 'Advance or further study',
        'no_job_opportunity' => 'No job opportunity',
        'family_concern' => 'Family concern and decided not to find a job',
        'did_not_look' => 'Did not look for a job',
        'health' => 'Health-related reason(s)',
        'lack_experience' => 'Lack of work experience',
    ];

    private const COURSE_REASON_LABELS = [
        'high_grades_course' => 'High grades in the course/subject area(s)',
        'good_grades_hs' => 'Good grades in high school',
        'parents_influence' => 'Influence of parents or relatives',
        'peer_influence' => 'Peer influence',
        'role_model' => 'Inspired by a role model',
        'passion' => 'Strong passion for the profession',
        'immediate_employment' => 'Prospect for immediate employment',
        'prestige' => 'Status or prestige of the profession',
        'availability' => 'Availability of course offering in chosen institution',
        'career_advancement' => 'Prospect of career advancement',
        'affordable' => 'Affordable for the family',
        'attractive_compensation' => 'Prospect of attractive compensation',
        'abroad' => 'Opportunity for employment abroad',
        'no_particular_choice' => 'No particular choice or no better idea',
        'others' => 'Others',
    ];

    private const JOB_REASON_LABELS = [
        'salaries_benefits' => 'Salaries and benefits', 'career_challenge' => 'Career challenge',
        'special_skill' => 'Related to special skill', 'related_course' => 'Related to course or program of study',
        'proximity' => 'Proximity to residence', 'peer_influence' => 'Peer influence',
        'family_influence' => 'Family influence',
    ];

    private const COMPETENCY_LABELS = [
        'communication' => 'Communication skills', 'human_relations' => 'Human Relations skills',
        'entrepreneurial' => 'Entrepreneurial skills', 'it_skills' => 'Information Technology skills',
        'problem_solving' => 'Problem-solving skills', 'critical_thinking' => 'Critical Thinking skills',
    ];

    public function index()
    {
        $charts = $this->buildCharts();

        // Group into the four survey sections for the tile-based display -
        // same underlying dataset the CSV export uses, just organized
        // differently, so the two views can never drift out of sync.
        $sections = [
            'A' => ['title' => 'A. General Information', 'charts' => []],
            'B' => ['title' => 'B. Educational Background', 'charts' => []],
            'C' => ['title' => 'C. Training(s) / Advance Studies', 'charts' => []],
            'D' => ['title' => 'D. Employment Data', 'charts' => []],
        ];
        foreach ($charts as $chart) {
            $sections[$chart['section']]['charts'][] = $chart;
        }

        return view('admin.analytics.index', ['charts' => $charts, 'sections' => $sections,
            'totalRespondents' => GraduateTracerSurvey::forGraduateUsers()->whereNotNull('submitted_at')->count()]);
    }

    public function export()
    {
        $charts = $this->buildCharts();
        $filename = 'gts_analytics_' . now()->format('Y_m_d_His') . '.csv';

        return response()->streamDownload(function () use ($charts) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Question', 'Answer', 'Count']);
            foreach ($charts as $chart) {
                foreach ($chart['labels'] as $i => $label) {
                    fputcsv($out, [$chart['question'], $label, $chart['counts'][$i]]);
                }
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array<int, array{key: string, question: string, labels: array<int, string>, counts: array<int, int>}>
     */
    private function buildCharts(): array
    {
        // Only completed responses count toward the analytics - partial
        // drafts shouldn't skew the totals.
        $surveyIds = GraduateTracerSurvey::forGraduateUsers()->whereNotNull('submitted_at')->pluck('id');

        $charts = [];

        $charts[] = $this->schoolYearChart($surveyIds);
        $charts[] = $this->fromColumn('sex', 'general_information', 'survey_id', $surveyIds, 'Sex', self::SEX_LABELS, 'A');
        $charts[] = $this->fromColumn('civil_status', 'general_information', 'survey_id', $surveyIds, 'Civil Status', self::CIVIL_STATUS_LABELS, 'A');

        foreach (['undergraduate' => 'Undergraduate', 'graduate' => 'Graduate'] as $level => $levelLabel) {
            $charts[] = $this->courseReasonChart($level, $levelLabel, $surveyIds);
        }

        $charts[] = $this->fromColumn('advance_study_reason', 'graduate_tracer_survey', 'id', $surveyIds, 'Reason for pursuing advance studies', self::ADVANCE_STUDY_REASON_LABELS, 'C');

        $charts[] = $this->fromColumn('employment_status', 'employment_data', 'survey_id', $surveyIds, 'Are you presently employed?', self::EMPLOYMENT_STATUS_LABELS, 'D');
        $charts[] = $this->fromColumn('present_employment_status', 'employment_data', 'survey_id', $surveyIds, 'Present Employment Status', self::PRESENT_EMPLOYMENT_STATUS_LABELS, 'D');
        $charts[] = $this->fromColumn('business_line', 'employment_data', 'survey_id', $surveyIds, 'Major Line of Business', [], 'D');
        $charts[] = $this->fromColumn('place_of_work', 'employment_data', 'survey_id', $surveyIds, 'Place of Work', self::PLACE_OF_WORK_LABELS, 'D');
        $charts[] = $this->fromColumn('is_first_job', 'employment_data', 'survey_id', $surveyIds, 'Is this your first job after college?', self::YES_NO_LABELS, 'D');
        $charts[] = $this->fromColumn('first_job_related_to_course', 'employment_data', 'survey_id', $surveyIds, 'Is your first job related to your course?', self::YES_NO_LABELS, 'D');
        $charts[] = $this->fromColumn('curriculum_relevant', 'employment_data', 'survey_id', $surveyIds, 'Was the curriculum relevant to your first job?', self::YES_NO_LABELS, 'D');

        $charts[] = $this->fromChildTable('not_employed_reasons_17', 'employment_data_id', 'employment_data', 'survey_id', $surveyIds, 'reason_key', 'Reason(s) why not yet employed', self::NOT_EMPLOYED_REASON_LABELS);
        $charts[] = $this->fromChildTable('competencies', 'employment_data_id', 'employment_data', 'survey_id', $surveyIds, 'competency_key', 'Competencies useful in first job', self::COMPETENCY_LABELS);

        foreach (['staying' => 'Reasons for staying on the job', 'accepting' => 'Reasons for accepting the job', 'changing' => 'Reasons for changing job'] as $type => $question) {
            $charts[] = $this->jobReasonChart($type, $question, $surveyIds);
        }

        return array_values(array_filter($charts, fn ($chart) => count($chart['labels']) > 0));
    }

    private function schoolYearChart($surveyIds): array
    {
        $rows = DB::table('graduate_tracer_survey as surveys')
            ->leftJoin('school_years', 'school_years.id', '=', 'surveys.school_year_id')
            ->whereIn('surveys.id', $surveyIds)
            ->select('school_years.label', DB::raw('count(distinct surveys.user_id) as total'))
            ->groupBy('school_years.label')
            ->orderByRaw('school_years.label IS NULL')
            ->orderByDesc('school_years.label')
            ->get();

        return [
            'key' => 'graduates_by_school_year',
            'question' => 'Graduates by School Year',
            'labels' => $rows->map(fn ($row) => filled($row->label) ? $row->label : 'Not specified')->all(),
            'counts' => $rows->map(fn ($row) => (int) $row->total)->all(),
            'section' => 'A',
        ];
    }

    private function fromColumn(string $column, string $table, string $foreignKey, $surveyIds, string $question, array $labelMap = [], string $section = 'D'): array
    {
        $rows = DB::table($table)
            ->whereIn($foreignKey, $surveyIds)
            ->select($column, DB::raw('count(*) as total'))
            ->groupBy($column)
            ->orderByDesc('total')
            ->get();

        $labels = [];
        $counts = [];
        foreach ($rows as $row) {
            $raw = $row->{$column};
            if ($raw === null || $raw === '') {
                continue;
            }
            $labels[] = $labelMap[$raw] ?? (string) $raw;
            $counts[] = (int) $row->total;
        }

        return ['key' => $table . '_' . $column, 'question' => $question, 'labels' => $labels, 'counts' => $counts, 'section' => $section];
    }

    private function fromChildTable(string $childTable, string $childForeignKey, string $parentTable, string $parentForeignKey, $surveyIds, string $groupColumn, string $question, array $labelMap): array
    {
        $rows = DB::table($childTable . ' as child')
            ->join($parentTable . ' as parent', 'parent.id', '=', 'child.' . $childForeignKey)
            ->whereIn('parent.' . $parentForeignKey, $surveyIds)
            ->select('child.' . $groupColumn . ' as answer_key', DB::raw('count(*) as total'))
            ->groupBy('answer_key')
            ->orderByDesc('total')
            ->get();

        $labels = [];
        $counts = [];
        foreach ($rows as $row) {
            $labels[] = $labelMap[$row->answer_key] ?? (string) $row->answer_key;
            $counts[] = (int) $row->total;
        }

        return ['key' => $childTable . '_' . $groupColumn, 'question' => $question, 'labels' => $labels, 'counts' => $counts, 'section' => 'D'];
    }

    private function jobReasonChart(string $reasonType, string $question, $surveyIds): array
    {
        $rows = DB::table('job_reasons_23to25 as jr')
            ->join('employment_data as ed', 'ed.id', '=', 'jr.employment_data_id')
            ->whereIn('ed.survey_id', $surveyIds)
            ->where('jr.reason_type', $reasonType)
            ->select('jr.reason_key', DB::raw('count(*) as total'))
            ->groupBy('jr.reason_key')
            ->orderByDesc('total')
            ->get();

        $labels = [];
        $counts = [];
        foreach ($rows as $row) {
            $labels[] = self::JOB_REASON_LABELS[$row->reason_key] ?? (string) $row->reason_key;
            $counts[] = (int) $row->total;
        }

        return ['key' => 'job_reasons_' . $reasonType, 'question' => $question, 'labels' => $labels, 'counts' => $counts, 'section' => 'D'];
    }

    private function courseReasonChart(string $level, string $levelLabel, $surveyIds): array
    {
        $rows = DB::table('course_reasons')
            ->whereIn('survey_id', $surveyIds)
            ->where('level', $level)
            ->select('reason_key', DB::raw('count(*) as total'))
            ->groupBy('reason_key')
            ->orderByDesc('total')
            ->get();

        $labels = [];
        $counts = [];
        foreach ($rows as $row) {
            $labels[] = self::COURSE_REASON_LABELS[$row->reason_key] ?? (string) $row->reason_key;
            $counts[] = (int) $row->total;
        }

        return ['key' => 'course_reasons_' . $level, 'question' => "Reasons for taking the course ({$levelLabel})", 'labels' => $labels, 'counts' => $counts, 'section' => 'B'];
    }
}
