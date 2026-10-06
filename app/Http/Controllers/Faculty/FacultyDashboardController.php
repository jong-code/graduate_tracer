<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\GraduateTracerSurvey;
use Illuminate\Support\Facades\DB;

class FacultyDashboardController extends Controller
{
    /**
     * IMPORTANT: This controller must never return individual survey rows,
     * names, emails, or other identifying fields to the faculty view.
     * Only COUNT()/GROUP BY aggregates belong here.
     */
    public function index()
    {
        $submittedIds = GraduateTracerSurvey::forGraduateUsers()->whereNotNull('submitted_at')->pluck('id');
        $totalSubmitted = $submittedIds->count();

        $employmentBreakdown = DB::table('employment_data')
            ->whereIn('survey_id', $submittedIds)
            ->select('employment_status', DB::raw('count(*) as total'))
            ->groupBy('employment_status')
            ->get();

        $byProgram = DB::table('graduate_tracer_survey')
            ->join('academic_programs', 'academic_programs.id', '=', 'graduate_tracer_survey.academic_program_id')
            ->whereIn('graduate_tracer_survey.id', $submittedIds)
            ->select('academic_programs.name', DB::raw('count(*) as total'))
            ->groupBy('academic_programs.name')
            ->orderByDesc('total')
            ->get();

        $curriculumRelevance = DB::table('employment_data')
            ->whereIn('survey_id', $submittedIds)
            ->whereNotNull('curriculum_relevant')
            ->select('curriculum_relevant', DB::raw('count(*) as total'))
            ->groupBy('curriculum_relevant')
            ->get();

        return view('faculty.dashboard', compact(
            'totalSubmitted', 'employmentBreakdown', 'byProgram', 'curriculumRelevance'
        ));
    }
}
