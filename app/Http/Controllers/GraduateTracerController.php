<?php

namespace App\Http\Controllers;

use App\Models\AcademicProgram;
use App\Models\Address;
use App\Models\Competency;
use App\Models\CourseReason;
use App\Models\EducationalBackground;
use App\Models\EmploymentData;
use App\Models\GeneralInformation;
use App\Models\GraduateTracerSurvey;
use App\Models\GraduateProgram;
use App\Models\JobReason;
use App\Models\Location;
use App\Models\NotEmployedReason;
use App\Models\OtherGraduate;
use App\Models\ProfessionalExam;
use App\Models\SchoolYear;
use App\Models\SelfEmployedSkill;
use App\Models\Training;
use App\Services\BusinessLineCatalog;
use App\Services\CollegeSkillsCatalog;
use App\Services\OccupationCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GraduateTracerController extends Controller
{
    private function courseReasonKeys(): array
    {
        return ['high_grades_course', 'good_grades_hs', 'parents_influence', 'peer_influence',
            'role_model', 'passion', 'immediate_employment', 'prestige', 'availability',
            'career_advancement', 'affordable', 'attractive_compensation', 'abroad', 'no_particular_choice'];
    }

    /**
     * Show the wizard, pre-filled from the normalized tables if the
     * graduate already has a survey on file.
     */
    public function dashboard(Request $request)
    {
        $survey = GraduateTracerSurvey::with([
            'generalInformation.address', 'educationalBackgrounds', 'professionalExams',
            'courseReasons', 'trainings', 'employmentData.notEmployedReasons',
            'employmentData.jobReasons', 'employmentData.competencies',
            'otherGraduates', 'academicProgram', 'schoolYear',
        ])->where('user_id', $request->user()->id)->first();

        $programs = AcademicProgram::where('is_active', true)->orderBy('name')->get();
        $schoolYears = SchoolYear::orderByDesc('label')->get();

        // The graduate's Academic Program on the survey is locked to what
        // they picked at registration (see AuthController::register(),
        // which writes the graduate_program record) - it's shown read-only
        // in Section A rather than as an editable dropdown, since a
        // graduate's program shouldn't drift from what they originally
        // registered under. Resolved once here (not only in the
        // first-education-defaults branch below) so it's available
        // whether or not the survey has been saved yet.
        $registration = $request->user()->graduatePrograms()
            ->with(['academicProgram', 'schoolYear'])
            ->latest('id')
            ->first();
        $registeredProgram = $registration?->academicProgram ?? $survey?->academicProgram;

        // Graduate/MS/MA/PhD reason-for-taking-the-course checkboxes (Section
        // B) only make sense for someone actually enrolled in a graduate
        // program - gate them off the registered program's code/name rather
        // than trusting a client-side toggle, since this is rendered
        // server-side as `disabled` below.
        $isGraduateProgram = false;
        if ($registeredProgram) {
            $codeUpper = mb_strtoupper($registeredProgram->code ?? '');
            $nameUpper = mb_strtoupper($registeredProgram->name ?? '');
            $isGraduateProgram = (bool) preg_match('/^(MS|MA|PHD)/', $codeUpper)
                || (bool) preg_match('/^(MS|MA|PHD)/', $nameUpper);
        }

        // Birthday cap: the graduate must have been born early enough to
        // plausibly have graduated in the registered school year - the
        // second (later) year of the "YYYY-YYYY" label minus 22 years, per
        // the school's rule of thumb. Applied both here (as the date
        // input's `max`) and again server-side in store() below.
        $schoolYearForCap = $registration?->schoolYear ?? $survey?->schoolYear;
        $maxBirthYear = $this->maxBirthYearFromSchoolYearLabel($schoolYearForCap?->label);

        // Flattened into the same field names the old wide-table version
        // used, so the Blade template's old()/value bindings don't need to
        // know about the underlying table split.
        $prefill = $this->flattenForForm($survey);

        // Always the registration's program, never the posted/old value -
        // this is what actually gets displayed and (re)submitted, so the
        // field can't be tampered with client-side.
        $prefill['academic_program_id'] = $registeredProgram?->id;

        // Auto-fill Full Name and E-mail from the registered account on a
        // first-time survey (no saved data yet) - still an editable plain
        // input, not locked, since the name on the diploma or a preferred
        // contact email may differ slightly from the account's. Once the
        // graduate has actually saved a survey, their entered value always
        // wins over the account default.
        $prefill['name'] = $prefill['name'] ?? $request->user()->name;
        $prefill['last_name'] = $prefill['last_name'] ?? $request->user()->last_name;
        $prefill['middle_name'] = $prefill['middle_name'] ?? $request->user()->middle_name;
        $prefill['email'] = $prefill['email'] ?? $request->user()->email;

        // Section B, Educational Attainment: default the first row's
        // Degree/Specialization, Year Graduated, and College/University
        // from what the graduate selected at registration - the
        // graduate_program table (see AuthController::register()), not
        // the survey's own academic_program_id/school_year_id, since
        // those two can in principle diverge later while
        // graduate_program stays the original registration record. Only
        // relevant while there's no educational_background saved yet (an
        // empty array here means the Blade template renders one blank
        // starter row, which is exactly where these defaults belong).
        // College/University still defaults to NEMSU but stays a plain
        // editable text input - some graduates transferred in a prior
        // degree from elsewhere.
        $firstEducationDefaults = [];
        if (! $survey || $survey->educationalBackgrounds->isEmpty()) {
            $firstEducationDefaults = [
                'degree' => $registration?->academicProgram?->name ?? $survey->academicProgram?->name ?? '',
                'year_graduated' => $registration?->schoolYear?->label ?? $survey->schoolYear?->label ?? '',
                'college_university' => 'North Eastern Mindanao State University',
            ];
        }

        return view('tracer.dashboard', compact('survey', 'prefill', 'programs', 'schoolYears', 'firstEducationDefaults', 'registeredProgram', 'isGraduateProgram', 'maxBirthYear'));
    }

    /**
     * Read-only view of the graduate's own submitted survey. Unlike the
     * admin oversight flow, no reason/audit gate applies here - this is
     * the graduate looking at their own data.
     */
    public function preview(Request $request)
    {
        $survey = GraduateTracerSurvey::with([
            'generalInformation.address', 'educationalBackgrounds', 'professionalExams',
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
        $education = $request->input('education');
        if (is_array($education)) $request->merge(['education' => array_values($education)]);
        // An extra blank skill row is optional; preserve any typed or selected entries.
        $skills = $request->input('self_employed_skills');
        if (is_array($skills)) {
            $request->merge(['self_employed_skills' => array_values(array_filter($skills,
                fn ($row) => ! is_array($row) || filled($row['name'] ?? null) || filled($row['id'] ?? null)))]);
        }
        // Same birthday cap as dashboard() above, resolved independently
        // here since store() doesn't build $registration/$survey until
        // after validation - this is the real enforcement (the `max` on
        // the date input is just client-side convenience).
        $selectedYearLabel = $request->input('education.0.year_graduated');
        $maxBirthYear = $this->maxBirthYearFromSchoolYearLabel(is_string($selectedYearLabel) ? $selectedYearLabel : null);

        $birthdayRules = ['required', 'date'];
        if ($maxBirthYear) {
            $birthdayRules[] = "before_or_equal:{$maxBirthYear}-12-31";
        }

        $validated = $request->validate([
            // A. General Information
            //
            // academic_program_id is deliberately NOT validated/read from
            // the request here - it's locked to whatever the graduate
            // picked at registration (see below) so it can't be edited or
            // tampered with via a forged POST. The dashboard view no
            // longer renders it as an editable field at all.
            'last_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'current_street' => ['nullable', 'string', 'max:255'],
            'current_barangay' => ['required', 'string', 'max:255'],
            'current_municipality' => ['required', 'string', 'max:255'],
            'current_province' => ['nullable', 'string', 'max:255'],
            'permanent_street' => ['nullable', 'string', 'max:255'],
            'permanent_barangay' => ['required', 'string', 'max:255'],
            'permanent_municipality' => ['required', 'string', 'max:255'],
            'permanent_province' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]*$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile_number' => [
                'required', 'string', 'max:20',
                function ($attribute, $value, $fail) {
                    $stripped = preg_replace('/\s+/', '', $value);
                    if (! preg_match('/^(?:\+639|09)\d{9}$/', $stripped)) {
                        $fail('Enter a valid PH mobile number, e.g. 0917 123 4567 or +63 917 123 4567.');
                    }
                },
            ],
            'civil_status' => ['required', 'in:single,married,separated,single_parent,widow_widower'],
            'sex' => ['required', 'in:male,female'],
            'birthday' => $birthdayRules,
            'region_of_origin' => ['required', 'string', 'max:100'],
            'residence_location' => ['required', 'in:city,municipality'],

            // B. Educational Background
            'education' => ['required', 'array', 'min:1', 'max:10'],
            'education.*.degree' => ['required', 'string', 'max:255'],
            'education.*.college_university' => ['required', 'string', 'max:255'],
            'education.*.year_graduated' => ['required', 'string', 'max:10', Rule::exists('school_years', 'label')],
            'education.*.honors' => ['nullable', 'string', 'max:255'],
            'professional_exams' => ['nullable', 'array'],
            'professional_exams.*.name' => ['nullable', 'string', 'max:100'],
            'professional_exams.*.date_taken' => ['nullable', 'date'],
            'professional_exams.*.rating' => ['nullable', 'string', 'max:255'],
            'reasons_undergrad' => ['nullable', 'array'],
            'reasons_graduate' => ['nullable', 'array'],
            'reasons_undergrad.*' => ['string', Rule::in($this->courseReasonKeys())],
            'reasons_graduate.*' => ['string', Rule::in($this->courseReasonKeys())],
            'reasons_other' => ['nullable', 'string', 'max:255'],

            // C. Trainings / Advance Studies
            'trainings' => ['required', 'array', 'min:1'],
            'trainings.0.title' => ['required', 'string', 'max:255'],
            'trainings.0.duration_credits' => ['required', 'string', 'max:255'],
            'trainings.0.institution' => ['required', 'string', 'max:255'],
            'trainings.*.title' => ['nullable', 'string', 'max:255'],
            'trainings.*.duration_credits' => ['nullable', 'string', 'max:255'],
            'trainings.*.institution' => ['nullable', 'string', 'max:255'],
            'advance_study_reason' => ['required', 'in:promotion,professional_development,others'],
            'advance_study_reason_other' => ['nullable', 'string', 'max:255'],

            // D. Employment Status + Job Details
            //
            // These conditional requirements mirror the client-side JS in
            // resources/views/tracer/dashboard.blade.php exactly - a field
            // is only required here when its containing block would
            // actually be visible to the user, based on their other
            // answers. Client-side `required` can always be bypassed
            // (disabled JS, a raw POST, etc.), so this is the actual
            // enforcement; the JS is just there for immediate feedback.
            'employment_status' => ['required', 'in:yes,no,never_employed'],
            'reasons_not_employed' => [
                Rule::requiredIf(fn () => in_array($request->input('employment_status'), ['no', 'never_employed'])),
                'array',
            ],
            'reasons_not_employed_other' => ['nullable', 'string', 'max:255'],
            'reasons_not_employed.*' => ['string', Rule::in(['advance_study', 'no_job_opportunity', 'family_concern', 'did_not_look', 'health', 'lack_experience'])],
            'present_employment_status' => [
                Rule::requiredIf(fn () => $request->input('employment_status') === 'yes'),
                'nullable', 'in:regular_permanent,temporary,casual,contractual,self_employed',
            ],
            'self_employed_skills' => [
                Rule::requiredIf(fn () => $request->input('present_employment_status') === 'self_employed'),
                'array',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('present_employment_status') === 'self_employed' && count($value ?? []) < 1) {
                        $fail('Please select at least one skill.');
                    }
                },
            ],
            // Catalog selections must have valid ids; custom names have no id.
            'self_employed_skills.*.id' => [
                'nullable', 'string',
                function ($attribute, $value, $fail) {
                    if (! CollegeSkillsCatalog::find($value)) {
                        $fail('One of the selected skills is not recognized. Please choose again from the suggestions.');
                    }
                },
            ],
            'self_employed_skills.*.name' => ['required', 'string', 'max:200'],
            'present_occupation_id' => ['nullable', 'string'],
            'present_occupation' => [
                Rule::requiredIf(fn () => $request->input('employment_status') === 'yes'),
                'nullable', 'string', 'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('employment_status') !== 'yes' || blank($value)) {
                        return;
                    }
                    $occupationId = $request->input('present_occupation_id');
                    if (blank($occupationId)) return; // A typed answer is allowed without a catalog selection.
                    $occupation = blank($occupationId) ? null : OccupationCatalog::find($occupationId);
                    if (! $occupation) {
                        $fail('Please select an occupation from the suggestions.');

                        return;
                    }
                    $employmentTypeId = OccupationCatalog::employmentTypeIdFor($request->input('present_employment_status'));
                    if ($employmentTypeId && ! OccupationCatalog::isCompatible($occupation, $employmentTypeId)) {
                        $fail('The selected occupation is not available for the chosen employment status. Please choose another.');
                    }
                },
            ],
            'business_line_id' => ['nullable', 'string'],
            'business_line' => [
                Rule::requiredIf(fn () => $request->input('employment_status') === 'yes'),
                'nullable', 'string', 'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('employment_status') !== 'yes' || blank($value)) {
                        return;
                    }
                    $businessLineId = $request->input('business_line_id');
                    if (filled($businessLineId) && ! BusinessLineCatalog::find($businessLineId)) {
                        $fail('Please select a business line from the suggestions.');
                    }
                },
            ],
            'place_of_work' => [
                Rule::requiredIf(fn () => $request->input('employment_status') === 'yes'),
                'nullable', 'in:local,abroad',
            ],
            'is_first_job' => [
                Rule::requiredIf(fn () => $request->input('employment_status') === 'yes'),
                'nullable', 'boolean',
            ],
            'first_job_related_to_course' => [
                Rule::requiredIf(fn () => $request->input('is_first_job') === '1'),
                'nullable', 'boolean',
            ],
            'reasons_staying' => [
                Rule::requiredIf(fn () => $request->input('is_first_job') === '1'),
                'array',
            ],
            'reasons_staying_other' => ['nullable', 'string', 'max:255'],
            'reasons_accepting' => [
                Rule::requiredIf(fn () => $request->input('is_first_job') === '1' && $request->input('first_job_related_to_course') === '1'),
                'array',
            ],
            'reasons_accepting_other' => ['nullable', 'string', 'max:255'],
            'reasons_changing' => [
                Rule::requiredIf(fn () => $request->input('is_first_job') === '0'
                    || ($request->input('is_first_job') === '1' && $request->input('first_job_related_to_course') === '0')),
                'array',
            ],
            'reasons_changing_other' => ['nullable', 'string', 'max:255'],
            'first_job_duration' => ['nullable', 'string', 'max:100'],
            'how_found_first_job' => ['nullable', 'string', 'max:255'],
            'time_to_land_first_job' => ['nullable', 'string', 'max:100'],
            'job_level_first' => ['nullable', 'string', 'max:100'],
            'job_level_current' => ['nullable', 'string', 'max:100'],
            'initial_gross_monthly_earning' => ['nullable', 'string', 'max:100'],
            // Hidden (and therefore optional) when "Never Employed" is
            // selected, or when the graduate is currently employed AND
            // their present job IS their first job - see
            // #firstJobDetailsBlock / updateFirstJobDetailsVisibility() in
            // the Blade view for the client-side mirror of this rule.
            'curriculum_relevant' => [
                Rule::requiredIf(function () use ($request) {
                    $employmentStatus = $request->input('employment_status');
                    $isFirstJob = $request->input('is_first_job');

                    return $employmentStatus !== 'never_employed'
                        && ! ($employmentStatus === 'yes' && $isFirstJob === '1');
                }),
                'nullable', 'boolean',
            ],
            'competencies_useful' => [
                Rule::requiredIf(fn () => $request->input('curriculum_relevant') === '1'),
                'array',
            ],
            'competencies_other' => ['nullable', 'string', 'max:255'],
            'competencies_useful.*' => ['string', Rule::in(['communication', 'human_relations', 'entrepreneurial', 'it_skills', 'problem_solving', 'critical_thinking'])],
            'curriculum_suggestions' => ['nullable', 'string'],

            // D (optional add-on): voluntary list of other graduates from
            // the same institution. Entirely optional, so every row and
            // every field within a row is nullable - a respondent can
            // leave the whole block blank.
            'other_graduates' => ['nullable', 'array'],
            'other_graduates.*.name' => ['nullable', 'string', 'max:255'],
            'other_graduates.*.address' => ['nullable', 'string', 'max:255'],
            'other_graduates.*.contact_number' => ['nullable', 'string', 'max:50'],

            // Disclosure & Consent (Section D) - the checkbox only ever
            // reaches the server checked once the browser's Geolocation
            // API has actually returned a fix (see the JS in this same
            // view), so requiring the coordinates here is the real
            // enforcement of "no location, no consent, no submission".
            'consent_agreement' => ['required', 'accepted'],
            'consent_latitude' => ['required', 'numeric', 'between:-90,90'],
            'consent_longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $validated['mobile_number'] = $this->normalizePhMobile($validated['mobile_number']);

        // Use canonical names for catalog selections; preserve custom typed answers.
        if (! empty($validated['present_occupation_id']) && ($occupation = OccupationCatalog::find($validated['present_occupation_id']))) {
            $validated['present_occupation'] = $occupation['name'];
        }
        if (! empty($validated['business_line_id']) && ($businessLine = BusinessLineCatalog::find($validated['business_line_id']))) {
            $validated['business_line'] = $businessLine['name'];
        }

        DB::transaction(function () use ($validated, $request) {
            $existingSurvey = GraduateTracerSurvey::where('user_id', $request->user()->id)->first();
            $schoolYear = SchoolYear::where('label', $validated['education'][0]['year_graduated'])->firstOrFail();

            // Source of truth for the survey's academic program: the
            // graduate_program record created at registration
            // (AuthController::register()), never the request payload -
            // see the validation rules above for why.
            $registeredProgramId = $request->user()->graduatePrograms()
                ->latest('id')
                ->value('academic_program_id');

            $survey = GraduateTracerSurvey::updateOrCreate(
                ['user_id' => $request->user()->id],
                [
                    'academic_program_id' => $registeredProgramId ?? $existingSurvey?->academic_program_id,
                    'school_year_id' => $schoolYear->id,
                    'submitted_at' => now(),
                    'advance_study_reason' => $validated['advance_study_reason'] ?? null,
                    'advance_study_reason_other' => $validated['advance_study_reason_other'] ?? null,
                ]
            );

            // The first degree determines the graduate's graduation cohort.
            // Update their own latest registration and survey together.
            $registration = $request->user()->graduatePrograms()->latest('id')->first();
            if ($registration) {
                $registration->update(['school_year_id' => $schoolYear->id]);
            } else {
                GraduateProgram::create(['user_id' => $request->user()->id,
                    'academic_program_id' => $survey->academic_program_id, 'school_year_id' => $schoolYear->id]);
            }

            // ---- Section A ----
            $generalInformation = GeneralInformation::updateOrCreate(
                ['survey_id' => $survey->id],
                [
                    'name' => $validated['name'],
                    'last_name' => $validated['last_name'],
                    'middle_name' => $validated['middle_name'] ?? null,
                    'telephone' => $validated['telephone'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'mobile_number' => $validated['mobile_number'],
                    'civil_status' => $validated['civil_status'],
                    'sex' => $validated['sex'],
                    'birthday' => $validated['birthday'],
                    'region_of_origin' => $validated['region_of_origin'],
                    'residence_location' => $validated['residence_location'],
                ]
            );

            Address::updateOrCreate(
                ['general_information_id' => $generalInformation->id],
                [
                    'current_street' => $validated['current_street'] ?? null,
                    'current_barangay' => $validated['current_barangay'] ?? null,
                    'current_municipality' => $validated['current_municipality'],
                    'current_province' => $validated['current_province'] ?? null,
                    'permanent_street' => $validated['permanent_street'] ?? null,
                    'permanent_barangay' => $validated['permanent_barangay'] ?? null,
                    'permanent_municipality' => $validated['permanent_municipality'],
                    'permanent_province' => $validated['permanent_province'] ?? null,
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

            // Self-employed skills (child table - see SelfEmployedSkill):
            // always clear existing rows first so a status change away
            // from Self-employed can't leave stale skills behind, then
            // re-insert only when actually self-employed, using canonical
            // catalog names or the validated custom answer.
            SelfEmployedSkill::where('employment_data_id', $employmentData->id)->delete();
            if (($validated['present_employment_status'] ?? null) === 'self_employed') {
                $savedSkills = [];
                foreach ($validated['self_employed_skills'] ?? [] as $row) {
                    $skill = CollegeSkillsCatalog::find($row['id'] ?? '');
                    $name = $skill['name'] ?? trim($row['name']);
                    $key = mb_strtolower($name);
                    if (isset($savedSkills[$key])) continue;
                    $savedSkills[$key] = true;
                    SelfEmployedSkill::create([
                        'employment_data_id' => $employmentData->id,
                        'skill_name' => $name,
                    ]);
                }
            }

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

            Location::updateOrCreate(
                ['employment_data_id' => $employmentData->id],
                [
                    'latitude' => $validated['consent_latitude'],
                    'longitude' => $validated['consent_longitude'],
                ]
            );

            // Record the disclosure/consent acceptance on the account too,
            // now that it's captured inline on the survey itself rather
            // than as a separate profile step.
            $request->user()->update([
                'consent_given' => true,
                'consent_given_at' => now(),
            ]);

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
            ->with('show_reward_modal', true)
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
    /**
     * "YYYY-YYYY" school year label -> (second year - 22), the school's
     * rule of thumb for the latest plausible birth year for a graduate of
     * that year. Returns null if the label can't be parsed (e.g. no
     * registration on file yet), in which case no cap is applied.
     */
    private function maxBirthYearFromSchoolYearLabel(?string $label): ?int
    {
        if (! $label || ! preg_match('/(\d{4})\D+(\d{4})/', $label, $m)) {
            return null;
        }

        return ((int) $m[2]) - 22;
    }

    /**
     * Normalizes an already-validated PH mobile number (see the
     * mobile_number rule above - this is only ever called after that
     * regex has passed) to the compact "+639XXXXXXXXX" form for storage,
     * regardless of which accepted format (with/without spaces, domestic
     * 09.. vs international +63 9..) the graduate typed.
     */
    private function normalizePhMobile(string $raw): string
    {
        $stripped = preg_replace('/\s+/', '', $raw);

        if (str_starts_with($stripped, '09')) {
            return '+63'.substr($stripped, 1);
        }

        return $stripped;
    }

    private function flattenForForm(?GraduateTracerSurvey $survey): array
    {
        if (! $survey) {
            return [];
        }

        $gi = $survey->generalInformation;
        $ed = $survey->employmentData;
        $address = $gi?->address;
        $occupation = $ed?->present_occupation ? OccupationCatalog::findByName($ed->present_occupation) : null;
        $employmentType = OccupationCatalog::employmentTypeIdFor($ed?->present_employment_status);
        if ($occupation && $employmentType && ! OccupationCatalog::isCompatible($occupation, $employmentType)) {
            $occupation = null; // Preserve an out-of-list typed answer as custom when editing.
        }

        return array_filter([
            'academic_program_id' => $survey->academic_program_id,
            'name' => $gi?->name,
            'last_name' => $gi?->last_name,
            'middle_name' => $gi?->middle_name,
            'current_street' => $address?->current_street,
            'current_barangay' => $address?->current_barangay,
            'current_municipality' => $address?->current_municipality,
            'current_province' => $address?->current_province,
            'permanent_street' => $address?->permanent_street,
            'permanent_barangay' => $address?->permanent_barangay,
            'permanent_municipality' => $address?->permanent_municipality,
            'permanent_province' => $address?->permanent_province,
            'telephone' => $gi?->telephone,
            'email' => $gi?->email,
            'mobile_number' => $gi?->mobile_number,
            'civil_status' => $gi?->civil_status,
            'sex' => $gi?->sex,
            'birthday' => $gi?->birthday?->format('Y-m-d'),
            'region_of_origin' => $gi?->region_of_origin,
            'residence_location' => $gi?->residence_location,
            'advance_study_reason' => $survey->advance_study_reason,
            'advance_study_reason_other' => $survey->advance_study_reason_other,
            'employment_status' => $ed?->employment_status,
            'present_employment_status' => $ed?->present_employment_status,
            'self_employed_skills' => $ed
                ? $ed->selfEmployedSkills->map(fn ($row) => [
                    'id' => CollegeSkillsCatalog::findByName($row->skill_name)['id'] ?? '',
                    'name' => $row->skill_name,
                ])->values()->all()
                : [],
            'present_occupation' => $ed?->present_occupation,
            'present_occupation_id' => $occupation['id'] ?? null,
            'business_line' => $ed?->business_line,
            'business_line_id' => $ed?->business_line
                ? (BusinessLineCatalog::findByName($ed->business_line)['id'] ?? null)
                : null,
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
