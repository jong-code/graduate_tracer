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
        $totalSubmitted = GraduateTracerSurvey::whereNotNull('submitted_at')->count();

        $employmentBreakdown = DB::table('employment_data')
            ->select('employment_status', DB::raw('count(*) as total'))
            ->groupBy('employment_status')
            ->get();

        $byProgram = DB::table('graduate_tracer_survey')
            ->join('academic_programs', 'academic_programs.id', '=', 'graduate_tracer_survey.academic_program_id')
            ->whereNotNull('graduate_tracer_survey.submitted_at')
            ->select('academic_programs.name', DB::raw('count(*) as total'))
            ->groupBy('academic_programs.name')
            ->get();

        $curriculumRelevance = DB::table('employment_data')
            ->select('curriculum_relevant', DB::raw('count(*) as total'))
            ->groupBy('curriculum_relevant')
            ->get();

        return view('faculty.dashboard', compact(
            'totalSubmitted', 'employmentBreakdown', 'byProgram', 'curriculumRelevance'
        ));
    }
}
