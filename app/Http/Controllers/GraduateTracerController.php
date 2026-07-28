<?php

namespace App\Http\Controllers;

use App\Models\AcademicProgram;
use App\Models\Competency;
use App\Models\CourseReason;
use App\Models\EducationalBackground;
use App\Models\EmploymentData;
use App\Models\GeneralInformation;
use App\Models\GraduateTracerSurvey;
use App\Models\JobReason;
use App\Models\NotEmployedReason;
use App\Models\OtherGraduate;
use App\Models\ProfessionalExam;
use App\Models\SchoolYear;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GraduateTracerController extends Controller
{
    /**
     * Show the wizard, pre-filled from the normalized tables if the
     * graduate already has a survey on file.
     */
    public function dashboard(Request $request)
    {
        $survey = GraduateTracerSurvey::with([
            'generalInformation', 'educationalBackgrounds', 'professionalExams',
            'courseReasons', 'trainings', 'employmentData.notEmployedReasons',
            'employmentData.jobReasons', 'employmentData.competencies',
            'otherGraduates',
        ])->where('user_id', $request->user()->id)->first();

        $programs = AcademicProgram::where('is_active', true)->orderBy('name')->get();

        // Flattened into the same field names the old wide-table version
        // used, so the Blade template's old()/value bindings don't need to
        // know about the underlying table split.
        $prefill = $this->flattenForForm($survey);

        return view('tracer.dashboard', compact('survey', 'prefill', 'programs'));
    }

    /**
     * Read-only view of the graduate's own submitted survey. Unlike the
     * admin oversight flow, no reason/audit gate applies here - this is
     * the graduate looking at their own data.
     */
    public function preview(Request $request)
    {
        $survey = GraduateTracerSurvey::with([
            'generalInformation', 'educationalBackgrounds', 'professionalExams',
            'courseReasons', 'trainings', 'employmentData.notEmployedReasons',
            'employmentData.jobReasons', 'employmentData.competencies',
            'otherGraduates', 'academicProgram', 'schoolYear',
        ])->where('user_id', $request->user()->id)->first();

        if (! $survey || ! $survey->submitted_at) {
            return redirect()
                ->route('user.survey')
                ->with('status', 'You haven\'t submitted your survey yet.');
        }

        return view('tracer.preview', compact('survey'));
    }

    public function store(Request $request)
    {
        if (! $request->user()->consent_given) {
            return redirect()
                ->route('user.profile.edit')
                ->withErrors(['consent' => 'Please give consent before submitting the survey.']);
        }

        $validated = $request->validate([
            // A. General Information
            'academic_program_id' => ['required', 'exists:academic_programs,id'],
            'name' => ['required', 'string', 'max:255'],
            'permanent_address' => ['required', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile_number' => ['required', 'string', 'max:50'],
            'civil_status' => ['required', 'in:single,married,separated,single_parent,widow_widower'],
            'sex' => ['required', 'in:male,female'],
            'birthday' => ['required', 'date'],
            'region_of_origin' => ['required', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'residence_city_municipality' => ['required', 'string', 'max:100'],

            // B. Educational Background
            'education' => ['required', 'array', 'min:1'],
            'education.*.degree' => ['required', 'string', 'max:255'],
            'education.*.college_university' => ['required', 'string', 'max:255'],
            'education.*.year_graduated' => ['required', 'string', 'max:10'],
            'education.*.honors' => ['nullable', 'string', 'max:255'],
            'professional_exams' => ['nullable', 'array'],
            'professional_exams.*.name' => ['nullable', 'string', 'max:100'],
            'professional_exams.*.date_taken' => ['nullable', 'date'],
            'professional_exams.*.rating' => ['nullable', 'string', 'max:255'],
            'reasons_undergrad' => ['nullable', 'array'],
            'reasons_graduate' => ['nullable', 'array'],
            'reasons_other' => ['nullable', 'string', 'max:255'],

            // C. Trainings / Advance Studies
            'trainings' => ['nullable', 'array'],
            'trainings.*.title' => ['nullable', 'string', 'max:255'],
            'trainings.*.duration_credits' => ['nullable', 'string', 'max:255'],
            'trainings.*.institution' => ['nullable', 'string', 'max:255'],
            'advance_study_reason' => ['nullable', 'in:promotion,professional_development,others'],
            'advance_study_reason_other' => ['nullable', 'string', 'max:255'],

            // D. Employment Status + Job Details
            'employment_status' => ['required', 'in:yes,no,never_employed'],
            'reasons_not_employed' => ['nullable', 'array'],
            'reasons_not_employed_other' => ['nullable', 'string', 'max:255'],
            'present_employment_status' => ['nullable', 'in:regular_permanent,temporary,casual,contractual,self_employed'],
            'self_employed_skills' => ['nullable', 'string'],
            'present_occupation' => ['nullable', 'string', 'max:255'],
            'business_line' => ['nullable', 'string', 'max:255'],
            'place_of_work' => ['nullable', 'in:local,abroad'],
            'is_first_job' => ['nullable', 'boolean'],
            'first_job_related_to_course' => ['nullable', 'boolean'],
            'reasons_staying' => ['nullable', 'array'],
            'reasons_staying_other' => ['nullable', 'string', 'max:255'],
            'reasons_accepting' => ['nullable', 'array'],
            'reasons_accepting_other' => ['nullable', 'string', 'max:255'],
            'reasons_changing' => ['nullable', 'array'],
            'reasons_changing_other' => ['nullable', 'string', 'max:255'],
            'first_job_duration' => ['nullable', 'string', 'max:100'],
            'how_found_first_job' => ['nullable', 'string', 'max:255'],
            'time_to_land_first_job' => ['nullable', 'string', 'max:100'],
            'job_level_first' => ['nullable', 'string', 'max:100'],
            'job_level_current' => ['nullable', 'string', 'max:100'],
            'initial_gross_monthly_earning' => ['nullable', 'string', 'max:100'],
            'curriculum_relevant' => ['nullable', 'boolean'],
            'competencies_useful' => ['nullable', 'array'],
            'competencies_other' => ['nullable', 'string', 'max:255'],
            'curriculum_suggestions' => ['nullable', 'string'],

            // D (optional add-on): voluntary list of other graduates from
            // the same institution. Entirely optional, so every row and
            // every field within a row is nullable - a respondent can
            // leave the whole block blank.
            'other_graduates' => ['nullable', 'array'],
            'other_graduates.*.name' => ['nullable', 'string', 'max:255'],
            'other_graduates.*.address' => ['nullable', 'string', 'max:255'],
            'other_graduates.*.contact_number' => ['nullable', 'string', 'max:50'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $existingSurvey = GraduateTracerSurvey::where('user_id', $request->user()->id)->first();
            $currentSchoolYear = SchoolYear::where('is_current', true)->first();

            $survey = GraduateTracerSurvey::updateOrCreate(
                ['user_id' => $request->user()->id],
                [
                    'academic_program_id' => $validated['academic_program_id'],
                    // Keep the school year picked at registration if one is
                    // already on file; only fall back to whichever year is
                    // currently flagged as active for brand-new surveys.
                    'school_year_id' => $existingSurvey?->school_year_id ?? $currentSchoolYear?->id,
                    'submitted_at' => now(),
                    'advance_study_reason' => $validated['advance_study_reason'] ?? null,
                    'advance_study_reason_other' => $validated['advance_study_reason_other'] ?? null,
                ]
            );

            // ---- Section A ----
            GeneralInformation::updateOrCreate(
                ['survey_id' => $survey->id],
                [
                    'name' => $validated['name'],
                    'permanent_address' => $validated['permanent_address'],
                    'telephone' => $validated['telephone'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'mobile_number' => $validated['mobile_number'],
                    'civil_status' => $validated['civil_status'],
                    'sex' => $validated['sex'],
                    'birthday' => $validated['birthday'],
                    'region_of_origin' => $validated['region_of_origin'],
                    'province' => $validated['province'] ?? null,
                    'residence_city_municipality' => $validated['residence_city_municipality'],
                    // Derived, not trusted from the client: the GTS form's
                    // "Location of Residence" (Q11) is really just City vs
                    // Municipality, so we infer it from the place they picked
                    // rather than asking a separate, easy-to-desync question.
                    'residence_location' => str_contains($validated['residence_city_municipality'], 'City')
                        ? 'city'
                        : 'municipality',
                ]
            );

            // ---- Section B (repeatable + checkbox groups) ----
            EducationalBackground::where('survey_id', $survey->id)->delete();
            foreach ($validated['education'] as $row) {
                EducationalBackground::create([
                    'survey_id' => $survey->id,
                    'degree' => $row['degree'],
                    'college_university' => $row['college_university'],
                    'year_graduated' => $row['year_graduated'],
                    'honors' => $row['honors'] ?? '',
                ]);
            }

            ProfessionalExam::where('survey_id', $survey->id)->delete();
            foreach ($validated['professional_exams'] ?? [] as $row) {
                if (empty($row['name'])) {
                    continue;
                }
                ProfessionalExam::create([
                    'survey_id' => $survey->id,
                    'exam_name' => $row['name'],
                    'date_taken' => $row['date_taken'] ?? null,
                    'rating' => $row['rating'] ?? '',
                ]);
            }

            CourseReason::where('survey_id', $survey->id)->delete();
            foreach ($validated['reasons_undergrad'] ?? [] as $key) {
                CourseReason::create(['survey_id' => $survey->id, 'level' => 'undergraduate', 'reason_key' => $key]);
            }
            foreach ($validated['reasons_graduate'] ?? [] as $key) {
                CourseReason::create(['survey_id' => $survey->id, 'level' => 'graduate', 'reason_key' => $key]);
            }
            if (! empty($validated['reasons_other'])) {
                CourseReason::create([
                    'survey_id' => $survey->id, 'level' => 'undergraduate',
                    'reason_key' => 'other', 'other_text' => $validated['reasons_other'],
                ]);
            }

            // ---- Section C ----
            Training::where('survey_id', $survey->id)->delete();
            foreach ($validated['trainings'] ?? [] as $row) {
                if (empty($row['title'])) {
                    continue;
                }
                Training::create([
                    'survey_id' => $survey->id,
                    'title' => $row['title'],
                    'duration_credits' => $row['duration_credits'] ?? '',
                    'institution' => $row['institution'] ?? '',
                ]);
            }

            // ---- Section D ----
            $employmentData = EmploymentData::updateOrCreate(
                ['survey_id' => $survey->id],
                [
                    'employment_status' => $validated['employment_status'],
                    'present_employment_status' => $validated['present_employment_status'] ?? null,
                    'self_employed_skills' => $validated['self_employed_skills'] ?? null,
                    'present_occupation' => $validated['present_occupation'] ?? '',
                    'business_line' => $validated['business_line'] ?? '',
                    'place_of_work' => $validated['place_of_work'] ?? '',
                    'is_first_job' => $validated['is_first_job'] ?? null,
                    'first_job_related_to_course' => $validated['first_job_related_to_course'] ?? null,
                    'first_job_duration' => $validated['first_job_duration'] ?? '',
                    'how_found_first_job' => $validated['how_found_first_job'] ?? '',
                    'time_to_land_first_job' => $validated['time_to_land_first_job'] ?? '',
                    'job_level_first' => $validated['job_level_first'] ?? '',
                    'job_level_current' => $validated['job_level_current'] ?? '',
                    'initial_gross_monthly_earning' => $validated['initial_gross_monthly_earning'] ?? '',
                    'curriculum_relevant' => $validated['curriculum_relevant'] ?? null,
                    'curriculum_suggestions' => $validated['curriculum_suggestions'] ?? '',
                ]
            );

            NotEmployedReason::where('employment_data_id', $employmentData->id)->delete();
            foreach ($validated['reasons_not_employed'] ?? [] as $key) {
                NotEmployedReason::create(['employment_data_id' => $employmentData->id, 'reason_key' => $key]);
            }
            if (! empty($validated['reasons_not_employed_other'])) {
                NotEmployedReason::create([
                    'employment_data_id' => $employmentData->id,
                    'reason_key' => 'other', 'other_text' => $validated['reasons_not_employed_other'],
                ]);
            }

            JobReason::where('employment_data_id', $employmentData->id)->delete();
            $this->storeJobReasons($employmentData->id, 'staying', $validated['reasons_staying'] ?? [], $validated['reasons_staying_other'] ?? null);
            $this->storeJobReasons($employmentData->id, 'accepting', $validated['reasons_accepting'] ?? [], $validated['reasons_accepting_other'] ?? null);
            $this->storeJobReasons($employmentData->id, 'changing', $validated['reasons_changing'] ?? [], $validated['reasons_changing_other'] ?? null);

            Competency::where('employment_data_id', $employmentData->id)->delete();
            foreach ($validated['competencies_useful'] ?? [] as $key) {
                Competency::create(['employment_data_id' => $employmentData->id, 'competency_key' => $key]);
            }
            if (! empty($validated['competencies_other'])) {
                Competency::create([
                    'employment_data_id' => $employmentData->id,
                    'competency_key' => 'other', 'other_text' => $validated['competencies_other'],
                ]);
            }

            // ---- Section D (optional add-on): other graduates ----
            OtherGraduate::where('survey_id', $survey->id)->delete();
            foreach ($validated['other_graduates'] ?? [] as $row) {
                // Skip fully blank rows (e.g. an empty row left over from
                // the repeatable-row UI) - only persist rows the graduate
                // actually filled in something for.
                if (empty($row['name']) && empty($row['address']) && empty($row['contact_number'])) {
                    continue;
                }
                OtherGraduate::create([
                    'survey_id' => $survey->id,
                    'name' => $row['name'] ?? null,
                    'address' => $row['address'] ?? null,
                    'contact_number' => $row['contact_number'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('user.dashboard')
            ->with('status', 'Your Graduate Tracer Survey was submitted successfully.')
            ->with('clear_survey_draft', true);
    }

    private function storeJobReasons(int $employmentDataId, string $type, array $keys, ?string $otherText): void
    {
        foreach ($keys as $key) {
            JobReason::create([
                'employment_data_id' => $employmentDataId,
                'reason_type' => $type,
                'number' => JobReason::QUESTION_NUMBERS[$type], // always derived, never user-supplied
                'reason_key' => $key,
            ]);
        }
        if (! empty($otherText)) {
            JobReason::create([
                'employment_data_id' => $employmentDataId,
                'reason_type' => $type,
                'number' => JobReason::QUESTION_NUMBERS[$type],
                'reason_key' => 'other',
                'other_text' => $otherText,
            ]);
        }
    }

    /**
     * Flatten the normalized tables back into the wide-table-shaped array
     * the Blade wizard's old()/value bindings expect.
     */
    private function flattenForForm(?GraduateTracerSurvey $survey): array
    {
        if (! $survey) {
            return [];
        }

        $gi = $survey->generalInformation;
        $ed = $survey->employmentData;

        return array_filter([
            'academic_program_id' => $survey->academic_program_id,
            'name' => $gi?->name,
            'permanent_address' => $gi?->permanent_address,
            'telephone' => $gi?->telephone,
            'email' => $gi?->email,
            'mobile_number' => $gi?->mobile_number,
            'civil_status' => $gi?->civil_status,
            'sex' => $gi?->sex,
            'birthday' => $gi?->birthday?->format('Y-m-d'),
            'region_of_origin' => $gi?->region_of_origin,
            'province' => $gi?->province,
            'residence_city_municipality' => $gi?->residence_city_municipality,
            'residence_location' => $gi?->residence_location,
            'advance_study_reason' => $survey->advance_study_reason,
            'advance_study_reason_other' => $survey->advance_study_reason_other,
            'employment_status' => $ed?->employment_status,
            'present_employment_status' => $ed?->present_employment_status,
            'self_employed_skills' => $ed?->self_employed_skills,
            'present_occupation' => $ed?->present_occupation,
            'business_line' => $ed?->business_line,
            'place_of_work' => $ed?->place_of_work,
            'is_first_job' => $ed?->is_first_job,
            'first_job_related_to_course' => $ed?->first_job_related_to_course,
            'first_job_duration' => $ed?->first_job_duration,
            'how_found_first_job' => $ed?->how_found_first_job,
            'time_to_land_first_job' => $ed?->time_to_land_first_job,
            'job_level_first' => $ed?->job_level_first,
            'job_level_current' => $ed?->job_level_current,
            'initial_gross_monthly_earning' => $ed?->initial_gross_monthly_earning,
            'curriculum_relevant' => $ed?->curriculum_relevant,
            'curriculum_suggestions' => $ed?->curriculum_suggestions,
        ], fn ($v) => $v !== null);
    }
}
