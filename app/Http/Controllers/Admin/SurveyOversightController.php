<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\GraduateTracerSurvey;
use App\Services\GtsDocxExportService;
use Illuminate\Http\Request;

/**
 * Per the admin role definition: "should not routinely inspect survey
 * content unless explicitly authorized." This controller is that one
 * explicit, audited exception - it is intentionally NOT linked from the
 * main admin dashboard nav. Every view is logged with the stated reason.
 */
class SurveyOversightController extends Controller
{
    public function show(Request $request, GraduateTracerSurvey $survey)
    {
        $request->validate([
            'reason' => ['required', 'string', 'min:10'],
        ]);

        AuditLog::record('survey_content_viewed', $survey, [
            'reason' => $request->input('reason'),
        ]);

        $survey->load(['user', 'academicProgram', 'schoolYear', 'otherGraduates']);

        return view('admin.surveys.show', compact('survey'));
    }

    public function reasonForm(GraduateTracerSurvey $survey)
    {
        return view('admin.surveys.reason', compact('survey'));
    }

    /**
     * Generates a filled-in DOCX for one survey, using the currently
     * configured template (Admin > Survey Templates). Goes through the
     * same reason + audit-log gate as viewing raw content - a portable
     * exported file is at least as sensitive as an on-screen view.
     */
    public function exportDocx(Request $request, GraduateTracerSurvey $survey, GtsDocxExportService $exportService)
    {
        $request->validate([
            'reason' => ['required', 'string', 'min:10'],
        ]);

        AuditLog::record('survey_docx_exported', $survey, [
            'reason' => $request->input('reason'),
        ]);

        try {
            $path = $exportService->export($survey);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['export' => $e->getMessage()]);
        }

        $survey->loadMissing('generalInformation');
        $safeName = \Illuminate\Support\Str::slug($survey->generalInformation?->name ?? "survey-{$survey->id}");

        return response()->download($path, "gts_{$safeName}.docx")->deleteFileAfterSend(true);
    }
}
