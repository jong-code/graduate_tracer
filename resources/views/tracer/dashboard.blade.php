@extends('layouts.app')

@section('title', 'Graduate Tracer Survey')

@section('content')
<div class="tracer-wrapper">

    <div class="tracer-header">
        <h1>Graduate Tracer Survey</h1>
        <p>Please complete this questionnaire as accurately and frankly as possible.</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="alert alert-info d-none" id="draftBanner">
        <span id="draftBannerText">We restored your unsaved answers from last time.</span>
        <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="discardDraftBtn">Discard and start over</button>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Please check the highlighted fields:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Stepper: A - D (clickable once a step has been reached, so users
         can jump back to fix a mistake before the final submit) -->
    <div class="tracer-stepper" id="tracerStepper">
        @foreach (['A' => 'General Information', 'B' => 'Educational Background', 'C' => 'Trainings', 'D' => 'Employment Data'] as $letter => $label)
            <div class="tracer-step-circle" data-step-index="{{ $loop->index }}" role="button" tabindex="0"
                 aria-label="Go to section {{ $letter }}: {{ $label }}">{{ $letter }}</div>
            @if (!$loop->last)
                <div class="tracer-step-line" data-line-index="{{ $loop->index }}"></div>
            @endif
        @endforeach
    </div>

    <form action="{{ route('user.survey.store') }}" method="POST" id="tracerForm" novalidate data-draft-key="gts_draft_{{ auth()->id() }}">
        @csrf

        <div class="tracer-card">

            {{-- ============================================================ --}}
            {{-- SECTION A: GENERAL INFORMATION --}}
            {{-- ============================================================ --}}
            <div class="tracer-section active" data-section-index="0" id="step-a">
                <h2>A. General Information</h2>
                <p class="tracer-card-sub">Tell us a bit about yourself</p>

                <div class="mb-3">
                    <label class="form-label">Academic Program <span class="req">*</span></label>
                    <select name="academic_program_id" class="form-select" required>
                        <option value="" disabled {{ empty(old('academic_program_id', $prefill['academic_program_id'] ?? '')) ? 'selected' : '' }}>Select your program</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" {{ (string) old('academic_program_id', $prefill['academic_program_id'] ?? '') === (string) $program->id ? 'selected' : '' }}>
                                {{ $program->name }} ({{ $program->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Full Name <span class="req">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Juan Dela Cruz"
                           value="{{ old('name', $prefill['name'] ?? '') }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Permanent Address <span class="req">*</span></label>
                    <input type="text" name="permanent_address" class="form-control" placeholder="House No., Street, Barangay, Municipality, Province"
                           value="{{ old('permanent_address', $prefill['permanent_address'] ?? '') }}" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">E-mail Address</label>
                        <input type="email" name="email" class="form-control" placeholder="you@example.com"
                               value="{{ old('email', $prefill['email'] ?? '') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Telephone / Contact Number</label>
                        <input type="text" name="telephone" class="form-control" placeholder="e.g. (086) 123 4567"
                               value="{{ old('telephone', $prefill['telephone'] ?? '') }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mobile Number <span class="req">*</span></label>
                    <input type="text" name="mobile_number" class="form-control" placeholder="e.g. 09XX XXX XXXX"
                           value="{{ old('mobile_number', $prefill['mobile_number'] ?? '') }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Civil Status <span class="req">*</span></label>
                    <div class="tracer-check-grid">
                        @foreach (['single' => 'Single', 'married' => 'Married', 'separated' => 'Separated', 'single_parent' => 'Single Parent', 'widow_widower' => 'Widow or Widower'] as $val => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="civil_status" id="cs_{{ $val }}"
                                       value="{{ $val }}" required
                                       {{ old('civil_status', $prefill['civil_status'] ?? '') === $val ? 'checked' : '' }}>
                                <label class="form-check-label" for="cs_{{ $val }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label d-block">Sex <span class="req">*</span></label>
                        @foreach (['male' => 'Male', 'female' => 'Female'] as $val => $label)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="sex" id="sex_{{ $val }}"
                                       value="{{ $val }}" required
                                       {{ old('sex', $prefill['sex'] ?? '') === $val ? 'checked' : '' }}>
                                <label class="form-check-label" for="sex_{{ $val }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Birthday <span class="req">*</span></label>
                        <input type="date" name="birthday" class="form-control"
                               value="{{ old('birthday', $prefill['birthday'] ?? '') }}" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Region of Origin <span class="req">*</span></label>
                    <select name="region_of_origin" id="geoRegion" class="form-select" required data-selected="{{ old('region_of_origin', $prefill['region_of_origin'] ?? '') }}">
                        <option value="" disabled selected>Loading regions...</option>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3" id="geoProvinceWrap">
                        <label class="form-label">Province</label>
                        <select name="province" id="geoProvince" class="form-select" data-selected="{{ old('province', $prefill['province'] ?? '') }}">
                            <option value="" selected>Select region first</option>
                        </select>
                        <div class="form-text">Leave as-is if your region has no provinces (e.g. NCR).</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">City / Municipality of Residence <span class="req">*</span></label>
                        <select name="residence_city_municipality" id="geoCity" class="form-select" required data-selected="{{ old('residence_city_municipality', $prefill['residence_city_municipality'] ?? '') }}">
                            <option value="" selected>Select region first</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- SECTION B: EDUCATIONAL BACKGROUND --}}
            {{-- ============================================================ --}}
            <div class="tracer-section" data-section-index="1" id="step-b">
                <h2>B. Educational Background</h2>
                <p class="tracer-card-sub">Your baccalaureate degree and credentials</p>

                <label class="form-label">Educational Attainment (Baccalaureate Degree only) <span class="req">*</span></label>
                <div id="educationRows">
                    <div class="tracer-repeat-row">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <input type="text" name="education[0][degree]" class="form-control form-control-sm" placeholder="Degree & Specialization" list="degreeOptions" required>
                            </div>
                            <div class="col-md-6 mb-2">
                                <input type="text" name="education[0][college_university]" class="form-control form-control-sm" placeholder="College or University" required>
                            </div>
                            <div class="col-md-6 mb-2">
                                <input type="text" name="education[0][year_graduated]" class="form-control form-control-sm" placeholder="Year Graduated" required>
                            </div>
                            <div class="col-md-6 mb-2">
                                <input type="text" name="education[0][honors]" class="form-control form-control-sm" placeholder="Honor(s) or Award(s)">
                            </div>
                        </div>
                    </div>
                </div>
                <datalist id="degreeOptions">
                    @foreach ($programs as $program)
                        <option value="{{ $program->name }}"></option>
                    @endforeach
                </datalist>
                <button type="button" class="tracer-add-row mb-3" data-add="education">+ Add another degree</button>

                <label class="form-label">Professional Examination(s) Passed</label>
                <div id="examRows">
                    <div class="tracer-repeat-row">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <input type="text" name="professional_exams[0][name]" class="form-control form-control-sm" placeholder="Name of Examination">
                            </div>
                            <div class="col-md-4 mb-2">
                                <input type="date" name="professional_exams[0][date_taken]" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4 mb-2">
                                <input type="text" name="professional_exams[0][rating]" class="form-control form-control-sm" placeholder="Rating">
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="tracer-add-row mb-3" data-add="exam">+ Add another exam</button>

                <label class="form-label d-block">Reason(s) for taking the course (check all that apply)</label>
                <div class="table-responsive mb-2">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th></th>
                                <th class="text-center">Undergrad/AB/BS</th>
                                <th class="text-center">Graduate/MS/MA/PhD</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ([
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
                            ] as $key => $label)
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td class="text-center">
                                        <input class="form-check-input" type="checkbox" name="reasons_undergrad[]" value="{{ $key }}">
                                    </td>
                                    <td class="text-center">
                                        <input class="form-check-input" type="checkbox" name="reasons_graduate[]" value="{{ $key }}">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mb-3">
                    <label class="form-label">Others, please specify</label>
                    <input type="text" name="reasons_other" class="form-control">
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- SECTION C: TRAINING(S) / ADVANCE STUDIES --}}
            {{-- ============================================================ --}}
            <div class="tracer-section" data-section-index="2" id="step-c">
                <h2>C. Training(s) / Advance Studies</h2>
                <p class="tracer-card-sub">Professional or work-related trainings attended after college</p>

                <div id="trainingRows">
                    <div class="tracer-repeat-row">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <input type="text" name="trainings[0][title]" class="form-control form-control-sm" placeholder="Title of Training / Advance Study">
                            </div>
                            <div class="col-md-4 mb-2">
                                <input type="text" name="trainings[0][duration_credits]" class="form-control form-control-sm" placeholder="Duration & Credits Earned">
                            </div>
                            <div class="col-md-4 mb-2">
                                <input type="text" name="trainings[0][institution]" class="form-control form-control-sm" placeholder="Institution / College / University">
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="tracer-add-row mb-3" data-add="training">+ Add another training</button>

                <label class="form-label d-block">What made you pursue advance studies?</label>
                <div class="mb-3">
                    @foreach (['promotion' => 'For promotion', 'professional_development' => 'For professional development', 'others' => 'Others'] as $val => $label)
                        <div class="form-check">
                            <input class="form-check-input advance-reason-radio" type="radio" name="advance_study_reason" id="asr_{{ $val }}" value="{{ $val }}">
                            <label class="form-check-label" for="asr_{{ $val }}">{{ $label }}</label>
                        </div>
                    @endforeach
                    <input type="text" name="advance_study_reason_other" id="advance_study_reason_other" class="form-control mt-2 tracer-conditional" placeholder="Please specify">
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- SECTION D: EMPLOYMENT STATUS --}}
            {{-- ============================================================ --}}
            <div class="tracer-section" data-section-index="3" id="step-d">
                <h2>D. Employment Data</h2>
                <p class="tracer-card-sub">Your employment status, job history, and course relevance</p>

                <label class="form-label d-block">Are you presently employed? <span class="req">*</span></label>
                <div class="mb-3">
                    @foreach (['yes' => 'Yes', 'no' => 'No', 'never_employed' => 'Never Employed'] as $val => $label)
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="employment_status" id="emp_{{ $val }}"
                                   value="{{ $val }}" required data-employment-toggle>
                            <label class="form-check-label" for="emp_{{ $val }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>

                {{-- Shown when NO / NEVER EMPLOYED --}}
                <div id="notEmployedBlock" class="tracer-conditional mb-3">
                    <label class="form-label d-block">Reason(s) why you are not yet employed</label>
                    <div class="tracer-check-grid mb-2">
                        @foreach ([
                            'advance_study' => 'Advance or further study',
                            'no_job_opportunity' => 'No job opportunity',
                            'family_concern' => 'Family concern and decided not to find a job',
                            'did_not_look' => 'Did not look for a job',
                            'health' => 'Health-related reason(s)',
                            'lack_experience' => 'Lack of work experience',
                        ] as $val => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="reasons_not_employed[]" id="rne_{{ $val }}" value="{{ $val }}">
                                <label class="form-check-label" for="rne_{{ $val }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                    <input type="text" name="reasons_not_employed_other" class="form-control" placeholder="Other reason(s), please specify">
                </div>

                {{-- Shown when YES --}}
                <div id="employedBlock" class="tracer-conditional">
                    <label class="form-label d-block">Present Employment Status</label>
                    <div class="tracer-check-grid mb-2">
                        @foreach ([
                            'regular_permanent' => 'Regular or Permanent',
                            'temporary' => 'Temporary',
                            'casual' => 'Casual',
                            'contractual' => 'Contractual',
                            'self_employed' => 'Self-employed',
                        ] as $val => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="present_employment_status" id="pes_{{ $val }}" value="{{ $val }}" data-selfemployed-toggle>
                                <label class="form-check-label" for="pes_{{ $val }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>

                    <div id="selfEmployedSkillsBlock" class="tracer-conditional mb-3">
                        <label class="form-label">If self-employed, what skills acquired in college were you able to apply?</label>
                        <textarea name="self_employed_skills" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Present Occupation</label>
                        <input type="text" name="present_occupation" class="form-control" placeholder="e.g. Grade School Teacher, Electrical Engineer">
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block">Major line of business of the company (check one)</label>
                        <select name="business_line" class="form-select">
                            <option value="">Select business line</option>
                            @foreach ([
                                'Agriculture, Hunting and Forestry','Fishing','Mining and Quarrying','Manufacturing',
                                'Electricity, Gas and Water Supply','Construction',
                                'Wholesale and Retail Trade, repair of motor vehicles, motorcycles and personal/household goods',
                                'Hotels and Restaurants','Transport, Storage and Communication','Financial Intermediation',
                                'Real Estate, Renting and Business Activities','Public Administration and Defense; Compulsory Social Security',
                                'Education','Health and Social Work','Other Community, Social and Personal Service Activities',
                                'Private Households with Employed Persons','Extra-territorial Organizations and Bodies',
                            ] as $line)
                                <option value="{{ $line }}">{{ $line }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block">Place of work</label>
                        @foreach (['local' => 'Local', 'abroad' => 'Abroad'] as $val => $label)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="place_of_work" id="pow_{{ $val }}" value="{{ $val }}">
                                <label class="form-check-label" for="pow_{{ $val }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block">Is this your first job after college?</label>
                        @foreach (['1' => 'Yes', '0' => 'No'] as $val => $label)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="is_first_job" id="fj_{{ $val }}" value="{{ $val }}" data-firstjob-toggle>
                                <label class="form-check-label" for="fj_{{ $val }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <hr class="my-4">

                {{-- Shown if first job = Yes (Q22) --}}
                <div id="stayingBlock" class="tracer-conditional mb-3">
                    <label class="form-label d-block">What are your reason(s) for staying on the job?</label>
                    <div class="tracer-check-grid mb-2">
                        @foreach ([
                            'salaries_benefits' => 'Salaries and benefits','career_challenge' => 'Career challenge',
                            'special_skill' => 'Related to special skill','related_course' => 'Related to course or program of study',
                            'proximity' => 'Proximity to residence','peer_influence' => 'Peer influence','family_influence' => 'Family influence',
                        ] as $val => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="reasons_staying[]" id="rs_{{ $val }}" value="{{ $val }}">
                                <label class="form-check-label" for="rs_{{ $val }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                    <input type="text" name="reasons_staying_other" class="form-control" placeholder="Other reason(s), please specify">
                </div>

                {{-- Q24 - only relevant if the present job is also the first job --}}
                <div id="relatedCourseBlock" class="tracer-conditional mb-3">
                    <label class="form-label d-block">Is your first job related to the course you took up in college?</label>
                    @foreach (['1' => 'Yes', '0' => 'No'] as $val => $label)
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="first_job_related_to_course" id="fjrc_{{ $val }}" value="{{ $val }}" data-relatedcourse-toggle>
                            <label class="form-check-label" for="fjrc_{{ $val }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>

                {{-- Shown if first job IS related to course (Q24 = Yes) --}}
                <div id="acceptingBlock" class="tracer-conditional mb-3">
                    <label class="form-label d-block">What were your reasons for accepting the job?</label>
                    <div class="tracer-check-grid mb-2">
                        @foreach (['salaries_benefits' => 'Salaries & benefits','career_challenge' => 'Career challenge','special_skill' => 'Related to special skills','proximity' => 'Proximity to residence'] as $val => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="reasons_accepting[]" id="ra_{{ $val }}" value="{{ $val }}">
                                <label class="form-check-label" for="ra_{{ $val }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                    <input type="text" name="reasons_accepting_other" class="form-control" placeholder="Other reason(s), please specify">
                </div>

                {{-- Shown if the present job is NOT the first job (Q22 = No),
                     or the first job was NOT related to the course (Q24 = No) --}}
                <div id="changingBlock" class="tracer-conditional mb-3">
                    <label class="form-label d-block">What were your reason(s) for changing job?</label>
                    <div class="tracer-check-grid mb-2">
                        @foreach (['salaries_benefits' => 'Salaries & benefits','career_challenge' => 'Career challenge','special_skill' => 'Related to special skills','proximity' => 'Proximity to residence'] as $val => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="reasons_changing[]" id="rc_{{ $val }}" value="{{ $val }}">
                                <label class="form-check-label" for="rc_{{ $val }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                    <input type="text" name="reasons_changing_other" class="form-control" placeholder="Other reason(s), please specify">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">How long did you stay in your first job?</label>
                        <select name="first_job_duration" class="form-select">
                            <option value="">Select duration</option>
                            @foreach (['Less than a month','1 to 6 months','7 to 11 months','1 year to less than 2 years','2 years to less than 3 years','3 years to less than 4 years','Others'] as $d)
                                <option value="{{ $d }}">{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">How did you find your first job?</label>
                        <select name="how_found_first_job" class="form-select">
                            <option value="">Select an option</option>
                            @foreach (['Response to an advertisement','As walk-in applicant','Recommended by someone','Information from friends','Arranged by school\'s job placement officer','Family business','Job Fair or PESO','Others'] as $h)
                                <option value="{{ $h }}">{{ $h }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">How long did it take you to land your first job?</label>
                        <select name="time_to_land_first_job" class="form-select">
                            <option value="">Select duration</option>
                            @foreach (['Less than a month','1 to 6 months','7 to 11 months','1 year to less than 2 years','2 years to less than 3 years','3 years to less than 4 years','Others'] as $d)
                                <option value="{{ $d }}">{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Initial gross monthly earning (first job)</label>
                        <select name="initial_gross_monthly_earning" class="form-select">
                            <option value="">Select range</option>
                            @foreach (['Below P5,000','P5,000 to less than P10,000','P10,000 to less than P15,000','P15,000 to less than P20,000','P20,000 to less than P25,000','P25,000 and above'] as $r)
                                <option value="{{ $r }}">{{ $r }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Job Level - First Job</label>
                        <select name="job_level_first" class="form-select">
                            <option value="">Select level</option>
                            @foreach (['Rank or Clerical','Professional, Technical or Supervisory','Managerial or Executive','Self-employed'] as $lvl)
                                <option value="{{ $lvl }}">{{ $lvl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Job Level - Current/Present Job</label>
                        <select name="job_level_current" class="form-select">
                            <option value="">Select level</option>
                            @foreach (['Rank or Clerical','Professional, Technical or Supervisory','Managerial or Executive','Self-employed'] as $lvl)
                                <option value="{{ $lvl }}">{{ $lvl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <label class="form-label d-block">Was the curriculum you had in college relevant to your first job?</label>
                <div class="mb-3">
                    @foreach (['1' => 'Yes', '0' => 'No'] as $val => $label)
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="curriculum_relevant" id="cr_{{ $val }}" value="{{ $val }}" data-curriculum-toggle>
                            <label class="form-check-label" for="cr_{{ $val }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>

                <div id="competenciesBlock" class="tracer-conditional mb-3">
                    <label class="form-label d-block">Competencies learned in college useful in your first job</label>
                    <div class="tracer-check-grid mb-2">
                        @foreach (['communication' => 'Communication skills','human_relations' => 'Human Relations skills','entrepreneurial' => 'Entrepreneurial skills','it_skills' => 'Information Technology skills','problem_solving' => 'Problem-solving skills','critical_thinking' => 'Critical Thinking skills'] as $val => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="competencies_useful[]" id="cu_{{ $val }}" value="{{ $val }}">
                                <label class="form-check-label" for="cu_{{ $val }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                    <input type="text" name="competencies_other" class="form-control" placeholder="Other skills, please specify">
                </div>

                <div class="mb-2">
                    <label class="form-label">Suggestions to further improve the course curriculum</label>
                    <textarea name="curriculum_suggestions" class="form-control" rows="3"></textarea>
                </div>

                <hr class="my-4">

                {{-- Optional add-on: voluntary list of other alumni, to help
                     the institution track down more respondents. Entirely
                     optional - every field can be left blank. --}}
                <div class="mb-2">
                    <h2 class="h5">Help Us Reach Other Alumni <span class="text-muted fw-normal">(Optional)</span></h2>
                    <p class="tracer-card-sub">
                        Thank you for taking the time to complete this survey. As a fellow alumnus/alumna, we'd
                        appreciate it if you could list the names, addresses, and contact numbers of other
                        graduates of your institution you may know of. Their responses would help make this
                        study more complete and useful - but this part is completely optional and you're
                        welcome to skip it.
                    </p>

                    <div id="otherGraduateRows">
                        <div class="tracer-repeat-row">
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <input type="text" name="other_graduates[0][name]" class="form-control form-control-sm" placeholder="Full Name">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <input type="text" name="other_graduates[0][address]" class="form-control form-control-sm" placeholder="Address">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <input type="text" name="other_graduates[0][contact_number]" class="form-control form-control-sm" placeholder="Contact Number">
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="tracer-add-row mb-3" data-add="otherGraduate">+ Add another graduate</button>
                </div>
            </div>

            <div class="tracer-nav">
                <button type="button" class="btn-tracer-prev" id="prevBtn">&larr; Previous</button>
                <button type="button" class="btn-tracer-next" id="nextBtn">Next &rarr;</button>
                <button type="submit" class="btn-tracer-submit d-none" id="submitBtn">Submit Survey</button>
            </div>

        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
(function () {
    const sections = Array.from(document.querySelectorAll('.tracer-section'));
    const circles = Array.from(document.querySelectorAll('.tracer-step-circle'));
    const lines = Array.from(document.querySelectorAll('.tracer-step-line'));
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    let current = 0;
    // The furthest step the user has already reached. Steps up to this
    // point are considered "visited" and can always be revisited (via the
    // stepper circles) to fix a mistake, without re-validating anything.
    let maxReached = 0;

    function render() {
        sections.forEach((s, i) => s.classList.toggle('active', i === current));
        circles.forEach((c, i) => {
            c.classList.toggle('active', i === current);
            c.classList.toggle('done', i < current);
            // Reachable = already visited, or the very next step. Anything
            // further ahead stays locked until the user progresses via
            // Next (so required fields on skipped steps can't be bypassed).
            const reachable = i <= maxReached;
            c.classList.toggle('clickable', reachable && i !== current);
            c.classList.toggle('locked', !reachable);
            c.setAttribute('aria-disabled', reachable ? 'false' : 'true');
            c.setAttribute('aria-current', i === current ? 'step' : 'false');
        });
        lines.forEach((l, i) => l.classList.toggle('done', i < current));

        prevBtn.style.visibility = current === 0 ? 'hidden' : 'visible';
        const isLast = current === sections.length - 1;
        nextBtn.classList.toggle('d-none', isLast);
        submitBtn.classList.toggle('d-none', !isLast);

        // Scroll the card into view on step change (helps on mobile)
        document.querySelector('.tracer-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function getActiveSectionInvalidElements() {
        // Only check fields that actually live inside the currently active
        // section. Calling checkValidity() on the whole form also flags
        // required fields in the other (hidden) sections, and since those
        // aren't focusable, reportValidity() then fails silently instead
        // of showing anything - which is what made Next look "broken".
        const activeSection = sections[current];
        return Array.from(activeSection.querySelectorAll('input, select, textarea'))
            .filter(el => typeof el.checkValidity === 'function' && !el.checkValidity());
    }
    
    function goToStep(index) {
        current = index;
        if (current > maxReached) maxReached = current;
        render();
    }

    nextBtn.addEventListener('click', function () {
        const invalidEls = getActiveSectionInvalidElements();
        if (invalidEls.length > 0) {
            invalidEls[0].reportValidity();
            return;
        }
        if (current < sections.length - 1) {
            goToStep(current + 1);
        }
    });

    prevBtn.addEventListener('click', function () {
        if (current > 0) {
            goToStep(current - 1);
        }
    });

    // ---- Clickable stepper circles: jump straight to a section to review
    // or fix it before the final submit. Already-visited steps are always
    // reachable; a step ahead of the furthest one reached stays locked
    // until the user has passed through the steps before it via Next. ----
    circles.forEach((circle, i) => {
        function tryNavigate() {
            if (i === current) return;
            if (i > maxReached) return; // locked - not reached yet
            goToStep(i);
        }
        circle.addEventListener('click', tryNavigate);
        circle.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                tryNavigate();
            }
        });
    });

    // ---- Repeatable rows (Education / Exams / Trainings) ----
    const repeatableGroups = {
        education: {
            containerId: 'educationRows',
            template: `
                <div class="row">
                    <div class="col-md-6 mb-2"><input type="text" name="education[__INDEX__][degree]" class="form-control form-control-sm" placeholder="Degree & Specialization" list="degreeOptions" required></div>
                    <div class="col-md-6 mb-2"><input type="text" name="education[__INDEX__][college_university]" class="form-control form-control-sm" placeholder="College or University" required></div>
                    <div class="col-md-6 mb-2"><input type="text" name="education[__INDEX__][year_graduated]" class="form-control form-control-sm" placeholder="Year Graduated" required></div>
                    <div class="col-md-6 mb-2"><input type="text" name="education[__INDEX__][honors]" class="form-control form-control-sm" placeholder="Honor(s) or Award(s)"></div>
                </div>`,
        },
        exam: {
            containerId: 'examRows',
            template: `
                <div class="row">
                    <div class="col-md-4 mb-2"><input type="text" name="professional_exams[__INDEX__][name]" class="form-control form-control-sm" placeholder="Name of Examination"></div>
                    <div class="col-md-4 mb-2"><input type="date" name="professional_exams[__INDEX__][date_taken]" class="form-control form-control-sm"></div>
                    <div class="col-md-4 mb-2"><input type="text" name="professional_exams[__INDEX__][rating]" class="form-control form-control-sm" placeholder="Rating"></div>
                </div>`,
        },
        training: {
            containerId: 'trainingRows',
            template: `
                <div class="row">
                    <div class="col-md-4 mb-2"><input type="text" name="trainings[__INDEX__][title]" class="form-control form-control-sm" placeholder="Title of Training / Advance Study"></div>
                    <div class="col-md-4 mb-2"><input type="text" name="trainings[__INDEX__][duration_credits]" class="form-control form-control-sm" placeholder="Duration & Credits Earned"></div>
                    <div class="col-md-4 mb-2"><input type="text" name="trainings[__INDEX__][institution]" class="form-control form-control-sm" placeholder="Institution / College / University"></div>
                </div>`,
        },
        otherGraduate: {
            containerId: 'otherGraduateRows',
            template: `
                <div class="row">
                    <div class="col-md-4 mb-2"><input type="text" name="other_graduates[__INDEX__][name]" class="form-control form-control-sm" placeholder="Full Name"></div>
                    <div class="col-md-4 mb-2"><input type="text" name="other_graduates[__INDEX__][address]" class="form-control form-control-sm" placeholder="Address"></div>
                    <div class="col-md-4 mb-2"><input type="text" name="other_graduates[__INDEX__][contact_number]" class="form-control form-control-sm" placeholder="Contact Number"></div>
                </div>`,
        },
    };

    function addRow(groupKey) {
        const group = repeatableGroups[groupKey];
        const container = document.getElementById(group.containerId);
        const index = container.children.length;
        const html = group.template.replace(/__INDEX__/g, index);
        const wrapper = document.createElement('div');
        wrapper.className = 'tracer-repeat-row';
        wrapper.innerHTML = `<button type="button" class="tracer-remove-row" data-remove>&times; Remove</button>${html}`;
        container.appendChild(wrapper);
        return wrapper;
    }

    document.querySelectorAll('[data-add]').forEach(btn => {
        btn.addEventListener('click', function () {
            addRow(btn.dataset.add);
        });
    });

    document.addEventListener('click', function (e) {
        if (e.target.matches('[data-remove]')) {
            e.target.closest('.tracer-repeat-row').remove();
        }
    });

    // ---- Conditional show/hide logic ----

    // C: advance study "Others" free text
    document.querySelectorAll('.advance-reason-radio').forEach(r => {
        r.addEventListener('change', function () {
            document.getElementById('advance_study_reason_other').classList.toggle('show', this.value === 'others');
        });
    });

    // D: employment status branches
    document.querySelectorAll('[data-employment-toggle]').forEach(r => {
        r.addEventListener('change', function () {
            const val = this.value;
            document.getElementById('notEmployedBlock').classList.toggle('show', val === 'no' || val === 'never_employed');
            document.getElementById('employedBlock').classList.toggle('show', val === 'yes');
        });
    });

    // D: self-employed skills textarea
    document.querySelectorAll('[data-selfemployed-toggle]').forEach(r => {
        r.addEventListener('change', function () {
            document.getElementById('selfEmployedSkillsBlock').classList.toggle('show', this.value === 'self_employed');
        });
    });

    // D -> Q22/Q23/Q24/Q25/Q26: first-job branch.
    // stayingBlock + relatedCourseBlock show only when the present job IS
    // the first job (Q22 = Yes). acceptingBlock shows only when that first
    // job was related to the course (Q24 = Yes). changingBlock shows when
    // either the present job isn't the first job (Q22 = No) or the first
    // job wasn't related to the course (Q24 = No).
    function updateFirstJobBlocks() {
        const isFirstJobEl = document.querySelector('[data-firstjob-toggle]:checked');
        const relatedEl = document.querySelector('[data-relatedcourse-toggle]:checked');
        const isFirstJob = isFirstJobEl ? isFirstJobEl.value === '1' : null;
        const isRelated = relatedEl ? relatedEl.value === '1' : null;

        document.getElementById('stayingBlock').classList.toggle('show', isFirstJob === true);
        document.getElementById('relatedCourseBlock').classList.toggle('show', isFirstJob === true);
        document.getElementById('acceptingBlock').classList.toggle('show', isFirstJob === true && isRelated === true);
        document.getElementById('changingBlock').classList.toggle('show', isFirstJob === false || (isFirstJob === true && isRelated === false));
    }

    document.querySelectorAll('[data-firstjob-toggle]').forEach(r => {
        r.addEventListener('change', updateFirstJobBlocks);
    });
    document.querySelectorAll('[data-relatedcourse-toggle]').forEach(r => {
        r.addEventListener('change', updateFirstJobBlocks);
    });

    // E: curriculum relevance -> competencies block
    document.querySelectorAll('[data-curriculum-toggle]').forEach(r => {
        r.addEventListener('change', function () {
            document.getElementById('competenciesBlock').classList.toggle('show', this.value === '1');
        });
    });

    // ================================================================
    // Draft autosave (localStorage) - saves as the user types so an
    // accidental tab close doesn't lose their progress. Cleared on
    // successful submit.
    // ================================================================
    const draftKey = document.getElementById('tracerForm').dataset.draftKey;
    let savedDraft = null;
    try {
        const raw = localStorage.getItem(draftKey);
        if (raw) savedDraft = JSON.parse(raw);
    } catch (e) {
        savedDraft = null;
    }

    // If a draft exists, let the geo cascade (below) know to prefer it
    // over the server-rendered old()/prefill values.
    if (savedDraft) {
        ['region_of_origin', 'province', 'residence_city_municipality'].forEach((name, i) => {
            const el = [document.getElementById('geoRegion'), document.getElementById('geoProvince'), document.getElementById('geoCity')][i];
            if (savedDraft[name] && savedDraft[name][0]) {
                el.dataset.selected = savedDraft[name][0];
            }
        });
    }

    // ---- Region / Province / City-Municipality cascade ----
    fetch('{{ asset("data/ph_geo.json") }}')
        .then(res => res.json())
        .then(geo => {
            const regionEl = document.getElementById('geoRegion');
            const provinceEl = document.getElementById('geoProvince');
            const provinceWrap = document.getElementById('geoProvinceWrap');
            const cityEl = document.getElementById('geoCity');

            const selectedRegionName = regionEl.dataset.selected || '';
            const selectedProvinceName = provinceEl.dataset.selected || '';
            const selectedCityName = cityEl.dataset.selected || '';

            // Populate regions
            regionEl.innerHTML = '<option value="" disabled>Select region</option>' +
                geo.regions.map(r => `<option value="${r.name}" data-code="${r.code}">${r.name}</option>`).join('');

            function populateProvinces(regionCode, keepSelected) {
                const provinces = geo.provinces.filter(p => p.region_code === regionCode);
                if (provinces.length === 0) {
                    provinceWrap.style.display = 'none';
                    provinceEl.innerHTML = '<option value="">N/A</option>';
                    provinceEl.removeAttribute('required');
                    return null;
                }
                provinceWrap.style.display = '';
                provinceEl.setAttribute('required', 'required');
                provinceEl.innerHTML = '<option value="" disabled selected>Select province</option>' +
                    provinces.map(p => `<option value="${p.name}" data-code="${p.code}">${p.name}</option>`).join('');
                if (keepSelected) {
                    const match = provinces.find(p => p.name === keepSelected);
                    if (match) provinceEl.value = match.name;
                }
                return provinces;
            }

            function populateCities(regionCode, provinceCode, keepSelected) {
                const cities = geo.cities.filter(c =>
                    c.region_code === regionCode && (provinceCode ? c.province_code === provinceCode : true)
                );
                cityEl.innerHTML = '<option value="" disabled selected>Select city/municipality</option>' +
                    cities.map(c => `<option value="${c.name}">${c.name}${c.is_city ? '' : ' (Municipality)'}</option>`).join('');
                if (keepSelected) {
                    const match = cities.find(c => c.name === keepSelected);
                    if (match) cityEl.value = match.name;
                }
            }

            regionEl.addEventListener('change', function () {
                const code = this.selectedOptions[0]?.dataset.code;
                const provinces = populateProvinces(code, null);
                if (!provinces) {
                    populateCities(code, null, null);
                } else {
                    cityEl.innerHTML = '<option value="" disabled selected>Select province first</option>';
                }
            });

            provinceEl.addEventListener('change', function () {
                const regionCode = regionEl.selectedOptions[0]?.dataset.code;
                const provinceCode = this.selectedOptions[0]?.dataset.code;
                populateCities(regionCode, provinceCode, null);
            });

            // Restore previous selections (editing a draft, or a failed
            // validation round-trip)
            if (selectedRegionName) {
                regionEl.value = selectedRegionName;
                const code = regionEl.selectedOptions[0]?.dataset.code;
                const provinces = populateProvinces(code, selectedProvinceName);
                const provinceCode = provinceEl.selectedOptions[0]?.dataset.code;
                populateCities(code, provinces ? provinceCode : null, selectedCityName);
            }
        })
        .catch(() => {
            document.getElementById('geoRegion').innerHTML = '<option value="">Could not load region list - refresh the page</option>';
        });

    // ---- Apply the draft to every other field (checkboxes, radios,
    // repeatable rows, plain inputs/selects/textareas) ----
    function applyDraftToForm(form, draft) {
        // Ensure enough repeatable rows exist before setting their values
        Object.keys(repeatableGroups).forEach(groupKey => {
            const fieldPrefix = { education: 'education', exam: 'professional_exams', training: 'trainings', otherGraduate: 'other_graduates' }[groupKey];
            let maxIndex = -1;
            Object.keys(draft).forEach(name => {
                const m = name.match(new RegExp(`^${fieldPrefix}\\[(\\d+)\\]`));
                if (m) maxIndex = Math.max(maxIndex, parseInt(m[1], 10));
            });
            const container = document.getElementById(repeatableGroups[groupKey].containerId);
            while (container.children.length <= maxIndex) {
                addRow(groupKey);
            }
        });

        Object.keys(draft).forEach(name => {
            const values = draft[name];
            const field = form.elements[name];
            if (!field) return;
            const isList = typeof field.length === 'number' && field.nodeName === undefined;
            const list = isList ? Array.from(field) : [field];
            if (list[0].type === 'checkbox' || list[0].type === 'radio') {
                list.forEach(el => { el.checked = values.includes(el.value); });
            } else {
                list.forEach((el, i) => { if (values[i] !== undefined) el.value = values[i]; });
            }
        });

        // Re-run conditional show/hide logic for restored radio values
        ['[data-employment-toggle]', '[data-selfemployed-toggle]', '[data-firstjob-toggle]', '[data-relatedcourse-toggle]', '[data-curriculum-toggle]', '.advance-reason-radio']
            .forEach(sel => {
                const checked = form.querySelector(`${sel}:checked`);
                if (checked) checked.dispatchEvent(new Event('change', { bubbles: true }));
            });
    }

    if (savedDraft) {
        const form = document.getElementById('tracerForm');
        applyDraftToForm(form, savedDraft);
        document.getElementById('draftBanner').classList.remove('d-none');
        if (savedDraft.__step && savedDraft.__step[0] !== undefined) {
            const step = parseInt(savedDraft.__step[0], 10);
            if (!isNaN(step) && step >= 0 && step < sections.length) {
                maxReached = step;
                goToStep(step);
            }
        }
    }

    document.getElementById('discardDraftBtn').addEventListener('click', function () {
        localStorage.removeItem(draftKey);
        window.location.reload();
    });

    // ---- Autosave on every change, debounced ----
    function serializeFormToDraft(form) {
        const fd = new FormData(form);
        const obj = {};
        for (const [key, value] of fd.entries()) {
            if (key === '_token') continue;
            if (!obj[key]) obj[key] = [];
            obj[key].push(value);
        }
        return obj;
    }

    let saveTimeout = null;
    const tracerFormEl = document.getElementById('tracerForm');
    ['input', 'change'].forEach(evt => {
        tracerFormEl.addEventListener(evt, function () {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                try {
                    const draft = serializeFormToDraft(tracerFormEl);
                    draft.__step = [String(current)];
                    localStorage.setItem(draftKey, JSON.stringify(draft));
                    const banner = document.getElementById('draftBanner');
                    if (!banner.classList.contains('d-none')) {
                        document.getElementById('draftBannerText').textContent = 'Draft saved automatically.';
                    }
                } catch (e) {
                    // localStorage full or unavailable - fail silently, submit still works normally
                }
            }, 400);
        });
    });

    // NOTE: we deliberately do NOT clear the draft on the form's 'submit'
    // event here. That event fires the instant the browser submits the
    // form, before the server has responded - so it would wipe the draft
    // even when the server rejects the submission (missing consent, a
    // validation error, etc.) and sends the person somewhere else
    // entirely. Instead, the draft is only cleared from user/dashboard.blade.php,
    // gated on a session flag the controller sets exclusively on a
    // confirmed-successful save (see GraduateTracerController::store()).

    render();
})();
</script>
@endsection
