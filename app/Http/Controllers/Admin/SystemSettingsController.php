<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\GraduateTracerSurvey;
use App\Services\GtsDocxExportService;

/**
 * Backups is named in the admin role's permission list but needs concrete
 * requirements (destination/schedule) before it can be built for real -
 * stubbed here so the nav and route exist and the rest of the app doesn't
 * have to change shape later.
 *
 * Survey Templates is built out for real: it manages the docxtemplater
 * .docx template used to export a graduate's answers as a filled-in Word
 * document, and lets an admin preview/export any submitted survey with it
 * directly from this page (see GtsDocxExportService).
 *
 * The template lives in /public/templates rather than storage/app, per
 * request - it's just the blank form (no graduate data), so being
 * web-reachable at /templates/gts_template.docx isn't a data exposure,
 * just means it's technically downloadable by anyone with the URL.
 *
 * Integrations is also built out for real: it's the admin-facing view of
 * the free-load reward feature (see UserNumber / user.dashboard's GCash
 * tile) - listing every graduate's submitted GCash number and letting an
 * admin manually mark a reward as sent.
 */
class SystemSettingsController extends Controller
{
    private const TEMPLATE_RELATIVE_PATH = 'templates/gts_template.docx';

    public function templates()
    {
        $templatePath = public_path(self::TEMPLATE_RELATIVE_PATH);
        $exists = is_file($templatePath);

        $users = \App\Models\User::where('role', 'user')
            ->whereHas('survey', fn ($q) => $q->whereNotNull('submitted_at'))
            ->with('survey.academicProgram')
            ->orderBy('name')
            ->get();

        return view('admin.templates.index', [
            'templateExists' => $exists,
            'templateUpdatedAt' => $exists ? filemtime($templatePath) : null,
            'templateSizeKb' => $exists ? round(filesize($templatePath) / 1024) : null,
            'users' => $users,
        ]);
    }

    /**
     * Read-only HTML preview - same rendering used by the graduate's own
     * "Preview my survey" page - so an admin can quickly check a
     * submission without generating an actual DOCX (that's what the
     * separate Export action is for).
     */
    public function previewSurvey(GraduateTracerSurvey $survey)
    {
        AuditLog::record('survey_content_previewed', $survey);

        $survey->load([
            'generalInformation', 'educationalBackgrounds', 'professionalExams',
            'courseReasons', 'trainings', 'employmentData.notEmployedReasons',
            'employmentData.jobReasons', 'employmentData.competencies',
            'otherGraduates', 'academicProgram', 'schoolYear',
        ]);

        return view('admin.surveys.preview', compact('survey'));
    }

    /**
     * Same render, but forces a download rather than an inline open.
     */
    public function exportSurvey(GraduateTracerSurvey $survey, GtsDocxExportService $exportService)
    {
        AuditLog::record('survey_docx_exported', $survey);

        try {
            $path = $exportService->export($survey);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['export' => $e->getMessage()]);
        }

        $survey->loadMissing('generalInformation');
        $safeName = \Illuminate\Support\Str::slug($survey->generalInformation?->name ?? "survey-{$survey->id}");

        return response()->download($path, "gts_{$safeName}.docx")->deleteFileAfterSend(true);
    }

    /**
     * Lists every graduate alongside the GCash number they've submitted
     * (if any) for the free-load reward, and whether an admin has marked
     * that reward as sent. Users with no number on file yet still show up,
     * just with nothing to mark done.
     */
    public function integrations()
    {
        $users = \App\Models\User::where('role', 'user')
            ->with('userNumber')
            ->orderBy('name')
            ->get();

        return view('admin.integrations.index', compact('users'));
    }

    public function markNumberDone(\App\Models\UserNumber $userNumber)
    {
        $userNumber->update(['is_done' => true]);

        return redirect()->route('admin.integrations')->with('status', 'Marked as done.');
    }

    public function backups()
    {
        return view('admin.stubs.placeholder', ['title' => 'Backups']);
    }
}
