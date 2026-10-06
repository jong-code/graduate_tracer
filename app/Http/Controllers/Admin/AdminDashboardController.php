<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\GraduateTracerSurvey;
use App\Models\SchoolYear;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'totalUsers' => User::count(),
            'totalPrograms' => AcademicProgram::count(),
            'totalSchoolYears' => SchoolYear::count(),
            'totalSurveysSubmitted' => GraduateTracerSurvey::forGraduateUsers()->whereNotNull('submitted_at')->count(),
            // Deliberately NOT loading survey content/PII here -
            // see SurveyOversightController for the explicitly-authorized path.
        ]);
    }
}
