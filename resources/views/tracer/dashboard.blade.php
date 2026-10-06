@extends('layouts.app')

@section('title', 'Graduate Tracer Survey')

@section('content')
<div class="survey-workspace">
<aside class="survey-guide" aria-label="Survey guide">
    <img src="{{ asset('images/nemsu-logo.png') }}" alt="NEMSU" class="auth-panel-logo">
    <h2>Graduate Tracer <small>Survey</small></h2>
    @foreach (['General Information', 'Educational Background', 'Training(s) / Advance Studies', 'Employment Data'] as $sectionLabel)
        <button type="button" class="survey-guide-step" data-guide-index="{{ $loop->index }}"><span>{{ chr(65 + $loop->index) }}</span>{{ $sectionLabel }}</button>
    @endforeach
    <p class="survey-guide-quote">Our Graduates.<br>Our Pride. Our Future.</p>
</aside>
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
                    <label class="form-label">Academic Program</label>
                    <input type="text" class="form-control" value="{{ $registeredProgram ? $registeredProgram->name.' ('.$registeredProgram->code.')' : 'Not set' }}" disabled readonly>
                    <small class="text-muted">This is the program you selected when you registered your account and can't be changed here.</small>
                    {{-- Not trusted server-side (see GraduateTracerController::store()) - the
                         field above is display-only; the value actually saved is always
                         re-derived from the graduate's registration record. --}}
                    <input type="hidden" name="academic_program_id" value="{{ $prefill['academic_program_id'] ?? '' }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Last Name <span class="req">*</span></label>
                    <input type="text" name="last_name" class="form-control" placeholder="e.g. Dela Cruz"
                           value="{{ old('last_name', $prefill['last_name'] ?? '') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">First Name <span class="req">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Juan"
                           value="{{ old('name', $prefill['name'] ?? '') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Middle Name (leave as blank if None)</label>
                    <input type="text" name="middle_name" class="form-control" placeholder="e.g. Santos"
                           value="{{ old('middle_name', $prefill['middle_name'] ?? '') }}">
                </div>

                <div class="mb-3">
                    <h3 class="h6"><strong>Permanent Address</strong></h3>
                    <div class="mb-2">
                        <label class="form-label">Region of Origin <span class="req">*</span></label>
                        <select name="region_of_origin" id="permanentRegion" class="form-select" data-address-region required
                                data-selected="{{ old('region_of_origin', $prefill['region_of_origin'] ?? '') }}">
                            <option value="" disabled selected>Loading regions...</option>
                        </select>
                    </div>
                    <div class="mb-2" id="permanentProvinceWrap">
                        <label class="form-label">Province</label>
                        <select name="permanent_province" id="permanentProvince" class="form-select" data-address-province
                                data-selected="{{ old('permanent_province', $prefill['permanent_province'] ?? '') }}">
                            <option value="" selected>Select region first</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">City / Municipality <span class="req">*</span></label>
                        <select name="permanent_municipality" id="permanentCity" class="form-select" data-address-city required
                                data-selected="{{ old('permanent_municipality', $prefill['permanent_municipality'] ?? '') }}">
                            <option value="" selected>Select region first</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Barangay <span class="req">*</span></label>
                        <select name="permanent_barangay" id="permanentBarangay" class="form-select" data-address-barangay required
                                data-selected="{{ old('permanent_barangay', $prefill['permanent_barangay'] ?? '') }}">
                            <option value="" selected>Select city/municipality first</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Street (optional)</label>
                        <input type="text" name="permanent_street" id="permanentStreet" class="form-control" placeholder="House No. / Street"
                               value="{{ old('permanent_street', $prefill['permanent_street'] ?? '') }}">
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap mb-2" style="gap: .5rem;">
                        <h3 class="h6 mb-0"><strong>Current Address</strong></h3>
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="sameAsPermanentAddress">
                            <label class="form-check-label small" for="sameAsPermanentAddress">(same as permanent address)</label>
                        </div>
                    </div>
                    <div id="currentAddressFields">
                    <div class="mb-2">
                        <label class="form-label">Region <span class="req">*</span></label>
                        <select id="currentRegion" class="form-select" data-address-region required>
                            <option value="" disabled selected>Loading regions...</option>
                        </select>
                    </div>
                    <div class="mb-2" id="currentProvinceWrap">
                        <label class="form-label">Province</label>
                        <select name="current_province" id="currentProvince" class="form-select" data-address-province
                                data-selected="{{ old('current_province', $prefill['current_province'] ?? '') }}">
                            <option value="" selected>Select region first</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">City / Municipality <span class="req">*</span></label>
                        <select name="current_municipality" id="currentCity" class="form-select" data-address-city required
                                data-selected="{{ old('current_municipality', $prefill['current_municipality'] ?? '') }}">
                            <option value="" selected>Select region first</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Barangay <span class="req">*</span></label>
                        <select name="current_barangay" id="currentBarangay" class="form-select" data-address-barangay required
                                data-selected="{{ old('current_barangay', $prefill['current_barangay'] ?? '') }}">
                            <option value="" selected>Select city/municipality first</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Street (optional)</label>
                        <input type="text" name="current_street" id="currentStreet" class="form-control" placeholder="House No. / Street"
                               value="{{ old('current_street', $prefill['current_street'] ?? '') }}">
                    </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label d-block">Location of Residence <span class="req">*</span></label>
                    @php $residenceLocation = old('residence_location', $prefill['residence_location'] ?? ''); @endphp
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="residence_location" id="residenceCity"
                               value="city" {{ $residenceLocation === 'city' ? 'checked' : '' }} data-residence-toggle>
                        <label class="form-check-label" for="residenceCity">City</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="residence_location" id="residenceMunicipality"
                               value="municipality" {{ $residenceLocation === 'municipality' ? 'checked' : '' }} data-residence-toggle>
                        <label class="form-check-label" for="residenceMunicipality">Municipality</label>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">E-mail Address</label>
                        <input type="email" name="email" class="form-control" placeholder="you@example.com"
                               value="{{ old('email', $prefill['email'] ?? '') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Telephone / Contact Number</label>
                        <input type="text" name="telephone" class="form-control" placeholder="e.g. 0861234567"
                               inputmode="numeric" pattern="[0-9]*" maxlength="20"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                               value="{{ old('telephone', $prefill['telephone'] ?? '') }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mobile Number <span class="req">*</span></label>
                    <input type="text" name="mobile_number" id="mobileNumberInput" class="form-control" placeholder="e.g. 0917 123 4567 or +63 917 123 4567"
                           inputmode="tel" maxlength="17"
                           pattern="^(09\d{2} ?\d{3} ?\d{4}|\+63 ?9\d{2} ?\d{3} ?\d{4})$"
                           title="Enter a valid PH mobile number, e.g. 0917 123 4567 or +63 917 123 4567"
                           value="{{ old('mobile_number', $prefill['mobile_number'] ?? '') }}" required>
                    <small class="text-muted">Format: 0917 123 4567 (domestic) or +63 917 123 4567 (international).</small>
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
                               @if ($maxBirthYear) max="{{ $maxBirthYear }}-12-31" @endif
                               value="{{ old('birthday', $prefill['birthday'] ?? '') }}" required>
                        <small class="text-muted" id="birthdayYearHint">@if ($maxBirthYear)Must be born in {{ $maxBirthYear }} or earlier.@endif</small>
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
                    @php
                        $educationRows = old('education', $survey?->educationalBackgrounds->map(fn ($row) => $row->only(['degree', 'college_university', 'year_graduated', 'honors']))->all() ?? []);
                        if (empty($educationRows)) $educationRows = [$firstEducationDefaults];
                    @endphp
                    @foreach ($educationRows as $educationRow)
                    <div class="tracer-repeat-row">
                        @unless ($loop->first)<button type="button" class="tracer-remove-row" data-remove>&times; Remove</button>@endunless
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <input type="text" name="education[{{ $loop->index }}][degree]" class="form-control form-control-sm" placeholder="Degree & Specialization" list="degreeOptions" required
                                       value="{{ $educationRow['degree'] ?? '' }}">
                            </div>
                            <div class="col-md-6 mb-2">
                                <input type="text" name="education[{{ $loop->index }}][college_university]" class="form-control form-control-sm" placeholder="College or University" required
                                       value="{{ $educationRow['college_university'] ?? '' }}">
                            </div>
                            <div class="col-md-6 mb-2">
                                @include('partials.survey-school-year', ['fieldName' => 'education['.$loop->index.'][year_graduated]', 'selectedYear' => $educationRow['year_graduated'] ?? ''])
                            </div>
                            <div class="col-md-6 mb-2">
                                <input type="text" name="education[{{ $loop->index }}][honors]" class="form-control form-control-sm" placeholder="Honor(s) or Award(s)" value="{{ $educationRow['honors'] ?? '' }}">
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <template id="schoolYearSelectTemplate">
                    @include('partials.survey-school-year', ['fieldName' => 'education[__INDEX__][year_graduated]', 'selectedYear' => ''])
                </template>
                <datalist id="degreeOptions">
                    @foreach ($programs as $program)
                        <option value="{{ $program->name }}"></option>
                    @endforeach
                </datalist>
                <button type="button" class="tracer-add-row mb-3" data-add="education">+ Add another degree</button>
                <p class="text-muted small">You can include up to 10 degrees.</p>

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
                                        <input class="form-check-input" type="checkbox" name="reasons_graduate[]" value="{{ $key }}" {{ $isGraduateProgram ? '' : 'disabled' }}>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @unless ($isGraduateProgram)
                    <small class="text-muted d-block mb-2">The Graduate/MS/MA/PhD column is only selectable for graduate-level academic programs.</small>
                @endunless
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
                                <input type="text" name="trainings[0][title]" class="form-control form-control-sm" placeholder="Title of Training / Advance Study" required>
                            </div>
                            <div class="col-md-4 mb-2">
                                <input type="text" name="trainings[0][duration_credits]" class="form-control form-control-sm" placeholder="Duration & Credits Earned" required>
                            </div>
                            <div class="col-md-4 mb-2">
                                <input type="text" name="trainings[0][institution]" class="form-control form-control-sm" placeholder="Institution / College / University" required>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="tracer-add-row mb-3" data-add="training">+ Add another training</button>

                <label class="form-label d-block">What made you pursue advance studies? <span class="req">*</span></label>
                <div class="mb-3">
                    @foreach (['promotion' => 'For promotion', 'professional_development' => 'For professional development', 'others' => 'Others'] as $val => $label)
                        <div class="form-check">
                            <input class="form-check-input advance-reason-radio" type="radio" name="advance_study_reason" id="asr_{{ $val }}" value="{{ $val }}" required>
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
                        <label class="form-label d-block">If self-employed, what skills acquired in college were you able to apply? <span class="req">*</span></label>
                        <div id="skillRows"></div>
                        <p class="text-muted small">Choose a suggestion or type your own skill if it is not listed.</p>
                        <button type="button" class="tracer-add-row mb-2" id="addSkillRowBtn">+ Add Another Skill</button>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="presentOccupationInput">Present Occupation</label>
                        <div class="skill-search-wrap">
                            <input type="text" id="presentOccupationInput" name="present_occupation" class="form-control"
                                   role="combobox" aria-expanded="false" aria-autocomplete="list" aria-controls="presentOccupationListbox"
                                   autocomplete="off" placeholder="Search for an occupation..."
                                   value="{{ old('present_occupation', $prefill['present_occupation'] ?? '') }}">
                            <input type="hidden" id="presentOccupationId" name="present_occupation_id"
                                   value="{{ old('present_occupation_id', $prefill['present_occupation_id'] ?? '') }}">
                            <ul class="skill-suggestions d-none" id="presentOccupationListbox" role="listbox"></ul>
                        </div>
                        <div class="skill-inline-message small text-danger mt-1 d-none" id="presentOccupationMessage"></div>
                        <small class="text-muted d-block">Choose a suggestion or type your own occupation if it is not listed.</small>
                        <small class="text-muted" id="presentOccupationHint">Select "Present Employment Status" above to filter suggestions by employment type.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block" for="businessLineInput">Major line of business of the company (check one)</label>
                        <div class="skill-search-wrap">
                            <input type="text" id="businessLineInput" name="business_line" class="form-control"
                                   role="combobox" aria-expanded="false" aria-autocomplete="list" aria-controls="businessLineListbox"
                                   autocomplete="off" placeholder="Search for a business line..."
                                   value="{{ old('business_line', $prefill['business_line'] ?? '') }}">
                            <input type="hidden" id="businessLineId" name="business_line_id"
                                   value="{{ old('business_line_id', $prefill['business_line_id'] ?? '') }}">
                            <ul class="skill-suggestions d-none" id="businessLineListbox" role="listbox"></ul>
                        </div>
                        <div class="skill-inline-message small text-danger mt-1 d-none" id="businessLineMessage"></div>
                        <small class="text-muted">Choose a suggestion or type your own business line if it is not listed.</small>
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

                <div id="firstJobDetailsBlock" class="tracer-conditional mb-3">
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
                </div>{{-- /firstJobDetailsBlock --}}

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

                <hr class="my-4">

                <div class="mb-2">
                    <h2 class="h5">Disclosure &amp; Consent</h2>
                    <p class="tracer-card-sub">
                        Please review and agree to the Disclosure and Consent Agreement before submitting your survey.
                    </p>
                    <button type="button" class="btn-tracer-prev mb-3" data-bs-toggle="modal" data-bs-target="#disclosureModal">
                        Disclosure Agreement
                    </button>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="consentCheckbox" name="consent_agreement" value="1" required>
                        <label class="form-check-label" for="consentCheckbox">
                            I have read and agree to the Disclosure and Consent Agreement, and I consent to my
                            current location being recorded for graduate tracing and reporting purposes.
                        </label>
                    </div>
                    <p id="consentLocationHint" class="small text-muted d-none mb-0"></p>

                    {{-- Populated by the Geolocation API right before the checkbox is
                         allowed to register as checked - see the script below. --}}
                    <input type="hidden" name="consent_latitude" id="consentLatitude">
                    <input type="hidden" name="consent_longitude" id="consentLongitude">
                </div>
            </div>

            <div class="tracer-nav">
                <button type="button" class="btn-tracer-prev" id="prevBtn">&larr; Previous</button>
                <button type="button" class="btn-tracer-next" id="nextBtn">Next &rarr;</button>
                <button type="submit" class="btn-tracer-submit d-none" id="submitBtn" disabled>Submit Survey</button>
            </div>

        </div>
    </form>
</div>
</div>

<div class="modal fade" id="disclosureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Graduate Tracer System Disclosure and Consent Agreement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Welcome to the Graduate Tracer System.</p>
                <p>Before proceeding, please read the following Disclosure and Consent Agreement carefully.</p>

                <h6 class="fw-bold mt-3">Disclosure and Consent Agreement</h6>
                <p>By accessing and using this Graduate Tracer System, I voluntarily acknowledge, understand, and agree to the following:</p>

                <h6 class="fw-bold mt-3">1. Accuracy of Information</h6>
                <p>I attest that all personal, educational, employment, and other information that I have provided in this system is true, accurate, complete, and correct to the best of my knowledge. I understand that any false, misleading, or incomplete information may affect the integrity of the Graduate Tracer records and may result in the correction, rejection, or removal of my submitted information when necessary.</p>

                <h6 class="fw-bold mt-3">2. Consent to Collection and Processing of Personal Data</h6>
                <p>I voluntarily give my consent to the collection, recording, organization, storage, updating, retrieval, consultation, use, consolidation, and processing of my personal information by the institution solely for legitimate educational purposes, including but not limited to:</p>
                <ul>
                    <li>Graduate tracing and alumni tracking;</li>
                    <li>Institutional research and program evaluation;</li>
                    <li>Curriculum development and quality assurance;</li>
                    <li>Accreditation and compliance with regulatory agencies;</li>
                    <li>Statistical reporting and graduate employment analysis; and</li>
                    <li>Other lawful academic and administrative purposes.</li>
                </ul>
                <p>All personal data shall be processed in accordance with the <strong>Data Privacy Act of 2012 (Republic Act No. 10173)</strong> and its Implementing Rules and Regulations (IRR), as well as the institution's applicable data privacy and information security policies.</p>

                <h6 class="fw-bold mt-3">3. Data Privacy and Confidentiality</h6>
                <p>I understand that the institution is committed to protecting my personal information and will implement appropriate organizational, physical, and technical security measures to safeguard my data against unauthorized access, disclosure, alteration, misuse, or destruction.</p>
                <p>My personal information shall only be accessed by authorized personnel and shall not be disclosed to unauthorized individuals or organizations except when required by law or with my consent.</p>

                <h6 class="fw-bold mt-3">4. Location Permission</h6>
                <p>To improve the accuracy and reliability of graduate distribution and employment statistics, this system may request permission to access my device's current location.</p>
                <p>I understand that my location information will be collected only for graduate tracing, mapping, statistical analysis, and institutional reporting purposes. My location data will not be used for commercial purposes or shared with unauthorized third parties.</p>
                <p>If prompted, I agree to enable my device's location services to allow the system to accurately record my current geographic location.</p>

                <h6 class="fw-bold mt-3">5. Voluntary Consent</h6>
                <p>I understand that my participation in the Graduate Tracer System is voluntary. By selecting <strong>"I Agree"</strong> and continuing to use the system, I confirm that:</p>
                <ul>
                    <li>I have carefully read and understood this Disclosure and Consent Agreement.</li>
                    <li>I voluntarily consent to the collection and processing of my personal information in accordance with the <strong>Data Privacy Act of 2012 (Republic Act No. 10173)</strong>.</li>
                    <li>I certify that all information I have submitted is truthful, accurate, and complete.</li>
                    <li>I understand the purposes for which my information and location data will be collected and processed.</li>
                </ul>
                <p>By clicking <strong>"I Agree"</strong>, I signify my informed consent to the terms stated above.</p>
                <p class="fw-bold">Please enable your device's location services when prompted. Allowing location access helps the institution generate accurate graduate distribution records and improve the quality of graduate tracer reports.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
window.__prefillSelfEmployedSkills = @json(old('self_employed_skills', $prefill['self_employed_skills'] ?? []));

(function () {
    // ---- Lock the browser's own Back/Forward while the survey is in
    // progress, so a graduate can't accidentally leave (or lose unsaved
    // answers) that way - navigation between Sections A-D should only
    // happen via the in-page Prev/Next buttons and stepper circles above.
    // This can't disable the toolbar buttons themselves (no web API for
    // that), so instead it keeps re-pushing the current URL onto the
    // history stack every time `popstate` fires, which cancels the
    // effect of a back/forward press from the user's point of view.
    history.pushState(null, document.title, location.href);
    window.addEventListener('popstate', function () {
        history.pushState(null, document.title, location.href);
        window.alert('Please use the Back/Next buttons on this page to move between sections - your answers are still saved.');
    });
})();

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
        document.querySelectorAll('[data-guide-index]').forEach((button, i) => {
            button.disabled = i > maxReached;
            button.setAttribute('aria-current', i === current ? 'step' : 'false');
        });
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
    document.querySelectorAll('[data-guide-index]').forEach((button, i) => {
        button.addEventListener('click', () => { if (i <= maxReached) goToStep(i); });
    });

    // ---- Repeatable rows (Education / Exams / Trainings) ----
    const repeatableGroups = {
        education: {
            containerId: 'educationRows',
            template: `
                <div class="row">
                    <div class="col-md-6 mb-2"><input type="text" name="education[__INDEX__][degree]" class="form-control form-control-sm" placeholder="Degree & Specialization" list="degreeOptions" required></div>
                    <div class="col-md-6 mb-2"><input type="text" name="education[__INDEX__][college_university]" class="form-control form-control-sm" placeholder="College or University" required></div>
                    <div class="col-md-6 mb-2">${document.getElementById('schoolYearSelectTemplate').innerHTML}</div>
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

    function addRow(groupKey, restoring = false) {
        const group = repeatableGroups[groupKey];
        const container = document.getElementById(group.containerId);
        if (!restoring && groupKey === 'education' && container.children.length >= 10) return null;
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
            const row = e.target.closest('.tracer-repeat-row');
            const container = row.parentElement;
            row.remove();
            Array.from(container.children).forEach((remaining, i) => {
                remaining.querySelectorAll('[name]').forEach(field => {
                    field.name = field.name.replace(/\[\d+\]/, '[' + i + ']');
                });
            });
        }
    });

    // ---- Conditional show/hide logic ----

    // A: Mobile Number - restrict typed characters to the PH mobile
    // formats the server will accept (digits, spaces, and a single
    // leading +), capping how many digits can be entered so the field
    // can't grow past a valid domestic/international number. Final
    // acceptance is still enforced by the `pattern` attribute above (and
    // again server-side) - this is just live input hygiene.
    (function () {
        const input = document.getElementById('mobileNumberInput');
        if (!input) return;

        function sanitize(raw) {
            let value = raw.replace(/[^\d+\s]/g, '');
            const firstPlus = value.indexOf('+');
            if (firstPlus > 0) {
                value = value.slice(0, firstPlus) + value.slice(firstPlus).replace(/\+/g, '');
            }
            value = value.replace(/(?!^)\+/g, '');

            const hasPlus = value.startsWith('+');
            const maxDigits = hasPlus ? 12 : 11; // +63 9XX XXX XXXX = 12 digits; 09XX XXX XXXX = 11 digits
            let digitCount = 0;
            let result = '';
            for (const ch of value) {
                if (ch === '+' || ch === ' ') {
                    result += ch;
                    continue;
                }
                if (digitCount >= maxDigits) continue;
                digitCount++;
                result += ch;
            }
            return result;
        }

        input.addEventListener('input', function () {
            const pos = this.selectionStart;
            const before = this.value;
            this.value = sanitize(this.value);
            if (pos !== null && this.value.length !== before.length) {
                this.setSelectionRange(this.value.length, this.value.length);
            }
        });
    })();


    // C: advance study "Others" free text
    document.querySelectorAll('.advance-reason-radio').forEach(r => {
        r.addEventListener('change', function () {
            document.getElementById('advance_study_reason_other').classList.toggle('show', this.value === 'others');
        });
    });

    // ----------------------------------------------------------------
    // Helpers for Section D's conditional questions being required.
    //
    // Plain `required` works fine for radios/selects/textareas, but a
    // checkbox *group* (reasons_not_employed[], reasons_staying[], etc.)
    // has no native "at least one must be checked" semantics - setting
    // `required` on every checkbox in a group would instead demand ALL of
    // them be checked. makeCheckboxGroupValidator() works around this: it
    // keeps `required` on every box in the group exactly while none of
    // them are checked, and clears it the moment any one is.
    //
    // Just as important: every one of these is only ever required while
    // its containing block is actually visible. This form is a single
    // plain HTML submit (no JS submit handler), so the browser runs its
    // normal validation across the WHOLE form when Submit is clicked -
    // including hidden conditional blocks. A `required` field left behind
    // in a hidden block can silently block submission with no visible
    // error (the browser can't focus/show a message on a display:none
    // element), so every toggle handler below must flip `required` off
    // the instant a block is hidden, not just on when it's shown.
    function setRequired(selector, isRequired) {
        document.querySelectorAll(selector).forEach(el => { el.required = isRequired; });
    }

    function makeCheckboxGroupValidator(name) {
        const boxes = Array.from(document.querySelectorAll(`input[type="checkbox"][name="${name}[]"]`));
        let active = false;
        function recompute() {
            const anyChecked = boxes.some(b => b.checked);
            boxes.forEach(b => { b.required = active && !anyChecked; });
        }
        boxes.forEach(b => b.addEventListener('change', recompute));
        return { setActive(val) { active = val; recompute(); } };
    }

    const notEmployedReasonsValidator = makeCheckboxGroupValidator('reasons_not_employed');
    const stayingReasonsValidator = makeCheckboxGroupValidator('reasons_staying');
    const acceptingReasonsValidator = makeCheckboxGroupValidator('reasons_accepting');
    const changingReasonsValidator = makeCheckboxGroupValidator('reasons_changing');
    const competenciesValidator = makeCheckboxGroupValidator('competencies_useful');

    // D: employment status branches
    document.querySelectorAll('[data-employment-toggle]').forEach(r => {
        r.addEventListener('change', function () {
            const val = this.value;
            const notEmployedVisible = val === 'no' || val === 'never_employed';
            const employedVisible = val === 'yes';

            document.getElementById('notEmployedBlock').classList.toggle('show', notEmployedVisible);
            document.getElementById('employedBlock').classList.toggle('show', employedVisible);
            notEmployedReasonsValidator.setActive(notEmployedVisible);

            setRequired('[name="present_employment_status"]', employedVisible);
            setRequired('[name="present_occupation"]', employedVisible);
            setRequired('[name="business_line"]', employedVisible);
            setRequired('[name="place_of_work"]', employedVisible);
            setRequired('[data-firstjob-toggle]', employedVisible);
            occupationCombo?.updateValidity();
            businessLineCombo?.updateValidity();

            if (!employedVisible) {
                // Leaving the "employed" branch entirely - force every
                // nested block (self-employed skills, staying/related/
                // accepting/changing) hidden and not required, regardless
                // of whatever they were previously set to.
                document.getElementById('selfEmployedSkillsBlock').classList.remove('show');
                if (typeof setSelfEmployedSkillsActiveRef === 'function') setSelfEmployedSkillsActiveRef(false);
                document.getElementById('stayingBlock').classList.remove('show');
                document.getElementById('relatedCourseBlock').classList.remove('show');
                document.getElementById('acceptingBlock').classList.remove('show');
                document.getElementById('changingBlock').classList.remove('show');
                setRequired('[data-relatedcourse-toggle]', false);
                stayingReasonsValidator.setActive(false);
                acceptingReasonsValidator.setActive(false);
                changingReasonsValidator.setActive(false);
            } else {
                updateFirstJobBlocks();
            }

            updateFirstJobDetailsVisibility();
        });
    });

    // ---- Self-employed skills searchable multi-select (Section D) ----
    // Renders entirely via JS (no server-rendered rows) so restoring a
    // previously saved or old()-repopulated set of skills and a freshly
    // added blank row use the exact same code path. Talks to the same
    // college_skills_acquired.json the server validates against (see
    // CollegeSkillsCatalog in GraduateTracerController) - if this file and
    // that one ever drift apart, the server always wins since it never
    // trusts what the browser sends.
    const skillRowsContainer = document.getElementById('skillRows');
    const addSkillRowBtn = document.getElementById('addSkillRowBtn');
    let skillCatalog = [];
    let skillCatalogLoaded = false;
    let skillCatalogFailed = false;
    let selfEmployedSkillsActive = false;
    let addSkillRowRef = null; // set below once the combobox module initializes; used by applyDraftToForm()
    let setSelfEmployedSkillsActiveRef = null; // set below; used by the employment-status handler further up

    if (skillRowsContainer && addSkillRowBtn) {
        fetch('/data/college_skills_acquired.json')
            .then(r => r.json())
            .then(data => {
                (data.categories || []).forEach(cat => {
                    (cat.skills || []).forEach(skill => {
                        skillCatalog.push({
                            id: skill.id,
                            name: skill.name,
                            categoryName: cat.name,
                            searchKey: (skill.name || '').toLowerCase(),
                        });
                    });
                });
                skillCatalogLoaded = true;
            })
            .catch(() => { skillCatalogFailed = true; });

        function selectedSkillIdsExcept(exceptIndex) {
            const ids = [];
            skillRowsContainer.querySelectorAll('[data-skill-row]').forEach(row => {
                if (parseInt(row.dataset.skillIndex, 10) === exceptIndex) return;
                const hidden = row.querySelector('.skill-hidden-id');
                if (hidden && hidden.value) ids.push(hidden.value);
            });
            return ids;
        }

        function searchSkills(query) {
            const q = query.trim().toLowerCase();
            if (!q) return skillCatalog.slice(0, 40);
            const starts = [];
            const contains = [];
            skillCatalog.forEach(skill => {
                if (skill.searchKey.startsWith(q)) {
                    starts.push(skill);
                } else if (skill.searchKey.includes(q)) {
                    contains.push(skill);
                }
            });
            return starts.concat(contains).slice(0, 40);
        }

        function renderSuggestions(listbox, matches, onSelect) {
            listbox.textContent = '';
            if (matches.length === 0) {
                const li = document.createElement('li');
                li.className = 'skill-empty';
                li.textContent = 'No matching skills. Your typed skill will be saved.';
                listbox.appendChild(li);
                return;
            }
            matches.forEach((skill, i) => {
                const li = document.createElement('li');
                li.className = 'skill-option';
                li.id = listbox.id + '-opt-' + i;
                li.setAttribute('role', 'option');
                li.dataset.skillId = skill.id;
                li.dataset.skillName = skill.name;

                const nameEl = document.createElement('span');
                nameEl.className = 'skill-option-name';
                nameEl.textContent = skill.name;

                //const catEl = document.createElement('span');
                //catEl.className = 'skill-option-category';
                //catEl.textContent = skill.categoryName;

                li.appendChild(nameEl);
                //li.appendChild(catEl);
                // mousedown (not click) fires before the input's blur
                // closes the dropdown, so the selection still registers.
                li.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    onSelect(skill);
                });
                listbox.appendChild(li);
            });
        }

        function updateSkillsValidity() {
            const firstInput = skillRowsContainer.querySelector('.skill-search-input');
            if (!firstInput) return;
            const hasSelection = Array.from(skillRowsContainer.querySelectorAll('.skill-search-input')).some(input => input.value.trim());
            firstInput.setCustomValidity(
                selfEmployedSkillsActive && !hasSelection ? 'Please select or type at least one skill.' : ''
            );
        }

        function renumberSkillRows() {
            Array.from(skillRowsContainer.querySelectorAll('[data-skill-row]')).forEach((row, i) => {
                row.dataset.skillIndex = String(i);
                const label = row.querySelector('.skill-row-label');
                const input = row.querySelector('.skill-search-input');
                const hidden = row.querySelector('.skill-hidden-id');
                const listbox = row.querySelector('.skill-suggestions');
                const newInputId = 'skillInput' + i;
                const newListboxId = 'skillListbox' + i;
                if (label) { label.textContent = 'Skill ' + (i + 1); label.setAttribute('for', newInputId); }
                if (input) { input.id = newInputId; input.name = 'self_employed_skills[' + i + '][name]'; input.setAttribute('aria-controls', newListboxId); }
                if (hidden) { hidden.name = 'self_employed_skills[' + i + '][id]'; }
                if (listbox) { listbox.id = newListboxId; }
            });
        }

        function wireSkillRow(row, input, hidden, listbox, message) {
            let activeOptionIndex = -1;

            function currentIndex() {
                return parseInt(row.dataset.skillIndex, 10);
            }

            function showMessage(text) {
                message.textContent = text;
                message.classList.toggle('d-none', !text);
            }

            function closeDropdown() {
                listbox.classList.add('d-none');
                input.setAttribute('aria-expanded', 'false');
                activeOptionIndex = -1;
            }

            function openDropdown() {
                listbox.classList.remove('d-none');
                input.setAttribute('aria-expanded', 'true');
            }

            function updateActiveOption(options) {
                options.forEach((opt, i) => {
                    opt.classList.toggle('active', i === activeOptionIndex);
                    if (i === activeOptionIndex) input.setAttribute('aria-activedescendant', opt.id);
                });
                if (options[activeOptionIndex]) {
                    options[activeOptionIndex].scrollIntoView({ block: 'nearest' });
                }
            }

            function selectSkill(skill) {
                if (selectedSkillIdsExcept(currentIndex()).includes(skill.id)) {
                    showMessage('This skill has already been selected.');
                    return;
                }
                input.value = skill.name;
                hidden.value = skill.id;
                showMessage('');
                closeDropdown();
                updateSkillsValidity();
            }

            input.addEventListener('focus', function () {
                if (!skillCatalogLoaded) return;
                renderSuggestions(listbox, searchSkills(input.value || ''), selectSkill);
                openDropdown();
            });

            input.addEventListener('input', function () {
                hidden.value = ''; // editing after a selection clears the hidden id
                showMessage('');
                updateSkillsValidity();
                if (!skillCatalogLoaded) {
                    if (skillCatalogFailed) showMessage('Suggestions unavailable. You can still type your own skill.');
                    return;
                }
                activeOptionIndex = -1;
                renderSuggestions(listbox, searchSkills(input.value), selectSkill);
                openDropdown();
            });

            input.addEventListener('blur', function () {
                // Delayed so the mousedown-based selection above still
                // registers before the dropdown closes.
                setTimeout(function () {
                    closeDropdown();
                    showMessage('');
                }, 150);
            });

            input.addEventListener('keydown', function (e) {
                const options = Array.from(listbox.querySelectorAll('.skill-option'));
                if (listbox.classList.contains('d-none') || options.length === 0) return;
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    activeOptionIndex = Math.min(activeOptionIndex + 1, options.length - 1);
                    updateActiveOption(options);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    activeOptionIndex = Math.max(activeOptionIndex - 1, 0);
                    updateActiveOption(options);
                } else if (e.key === 'Enter') {
                    if (activeOptionIndex >= 0 && options[activeOptionIndex]) {
                        e.preventDefault();
                        selectSkill({
                            id: options[activeOptionIndex].dataset.skillId,
                            name: options[activeOptionIndex].dataset.skillName,
                        });
                    }
                } else if (e.key === 'Escape') {
                    closeDropdown();
                }
            });
        }

        function buildSkillRow(index) {
            const row = document.createElement('div');
            row.className = 'tracer-skill-row';
            row.dataset.skillRow = '';
            row.dataset.skillIndex = String(index);

            const inputId = 'skillInput' + index;
            const listboxId = 'skillListbox' + index;

            const header = document.createElement('div');
            header.className = 'skill-row-header';

            const label = document.createElement('label');
            label.className = 'form-label small mb-0 skill-row-label';
            label.textContent = 'Skill ' + (index + 1);
            label.setAttribute('for', inputId);
            header.appendChild(label);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'tracer-skill-remove';
            removeBtn.innerHTML = '&times;';
            removeBtn.setAttribute('aria-label', 'Remove this skill');
            removeBtn.addEventListener('click', function () {
                row.remove();
                renumberSkillRows();
                updateSkillRemoveButtonsVisibility();
                updateSkillsValidity();
            });
            header.appendChild(removeBtn);

            row.appendChild(header);

            const wrap = document.createElement('div');
            wrap.className = 'skill-search-wrap';

            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control skill-search-input';
            input.id = inputId;
            input.name = 'self_employed_skills[' + index + '][name]';
            input.setAttribute('role', 'combobox');
            input.setAttribute('aria-expanded', 'false');
            input.setAttribute('aria-autocomplete', 'list');
            input.setAttribute('aria-controls', listboxId);
            input.setAttribute('autocomplete', 'off');
            input.placeholder = 'Search for a skill...';

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.className = 'skill-hidden-id';
            hidden.name = 'self_employed_skills[' + index + '][id]';

            const listbox = document.createElement('ul');
            listbox.className = 'skill-suggestions d-none';
            listbox.id = listboxId;
            listbox.setAttribute('role', 'listbox');

            const message = document.createElement('div');
            message.className = 'skill-inline-message small text-danger mt-1 d-none';

            wrap.appendChild(input);
            wrap.appendChild(hidden);
            wrap.appendChild(listbox);
            row.appendChild(wrap);
            row.appendChild(message);

            wireSkillRow(row, input, hidden, listbox, message);

            return row;
        }

        function updateSkillRemoveButtonsVisibility() {
            const rows = skillRowsContainer.querySelectorAll('[data-skill-row]');
            const show = rows.length >= 2;
            rows.forEach(row => {
                const btn = row.querySelector('.tracer-skill-remove');
                if (btn) btn.classList.toggle('d-none', !show);
            });
        }

        function addSkillRow(prefill) {
            const index = skillRowsContainer.querySelectorAll('[data-skill-row]').length;
            const row = buildSkillRow(index);
            skillRowsContainer.appendChild(row);
            if (prefill && prefill.name) {
                row.querySelector('.skill-search-input').value = prefill.name;
                row.querySelector('.skill-hidden-id').value = prefill.id || '';
            }
            updateSkillRemoveButtonsVisibility();
            return row;
        }
        addSkillRowRef = addSkillRow;

        function setSelfEmployedSkillsActive(active) {
            selfEmployedSkillsActive = active;
            // Disabled (not just hidden) fields are excluded from the
            // form submission entirely, which is how stale skill rows
            // are kept from being submitted once Self-employed is no
            // longer selected - without erasing what was typed, in case
            // the graduate flips back to Self-employed afterwards.
            skillRowsContainer.querySelectorAll('input').forEach(el => { el.disabled = !active; });
            updateSkillsValidity();
        }
        setSelfEmployedSkillsActiveRef = setSelfEmployedSkillsActive;

        // Initial rows: restore previously saved/old()-repopulated skills
        // if any, otherwise start with a single blank row.
        const prefillSkills = window.__prefillSelfEmployedSkills || [];
        if (prefillSkills.length > 0) {
            prefillSkills.forEach(skill => addSkillRow(skill));
        } else {
            addSkillRow();
        }

        addSkillRowBtn.addEventListener('click', function () {
            const row = addSkillRow();
            row.querySelector('.skill-search-input').focus();
        });

        document.addEventListener('click', function (e) {
            skillRowsContainer.querySelectorAll('.skill-search-wrap').forEach(wrap => {
                if (!wrap.contains(e.target)) {
                    const lb = wrap.querySelector('.skill-suggestions');
                    const inp = wrap.querySelector('.skill-search-input');
                    if (lb) lb.classList.add('d-none');
                    if (inp) inp.setAttribute('aria-expanded', 'false');
                }
            });
        });

        // Match whatever the "Present Employment Status" radio is
        // already set to on load (resumed drafts re-dispatch 'change' on
        // it - see the re-sync list further down - which calls this
        // again with the right value).
        setSelfEmployedSkillsActive(false);

        document.querySelectorAll('[data-selfemployed-toggle]').forEach(r => {
            r.addEventListener('change', function () {
                const visible = this.value === 'self_employed';
                document.getElementById('selfEmployedSkillsBlock').classList.toggle('show', visible);
                setSelfEmployedSkillsActive(visible);
            });
        });
    }

    // ---- Present Occupation & Major Line of Business (Section D) ----
    // Two independent searchable single-selects sharing one small
    // reusable combobox factory (see createSearchableSingleSelect below).
    // Both talk to the same JSON files the server validates against
    // (OccupationCatalog / BusinessLineCatalog in GraduateTracerController)
    // - the server never trusts what's submitted here.
    const EMPLOYMENT_TYPE_MAP = {
        regular_permanent: 'EMP-REGULAR_PERMANENT',
        temporary: 'EMP-TEMPORARY',
        casual: 'EMP-CASUAL',
        contractual: 'EMP-CONTRACTUAL',
        self_employed: 'EMP-SELF_EMPLOYED',
    };

    function currentEmploymentTypeId() {
        const checked = document.querySelector('[data-selfemployed-toggle]:checked');
        return checked ? (EMPLOYMENT_TYPE_MAP[checked.value] || null) : null;
    }

    // Generic searchable single-select combobox: one visible text input +
    // one hidden id input + one <ul> suggestions list. `getItems(query)`
    // returns the (already ordered/filtered) items to show for that
    // query; items are {id, name, categoryName?}. Mirrors the self-
    // employed skills combobox's behavior (prefix-first search, ARIA
    // combobox/listbox, keyboard nav, outside-click close, edit-clears-id,
    // textContent-only rendering) but for a single value instead of a
    // repeatable list.
    function createSearchableSingleSelect(input, hidden, listbox, message, getItems, opts) {
        opts = opts || {};
        let activeOptionIndex = -1;

        function showMessage(text) {
            if (!message) return;
            message.textContent = text;
            message.classList.toggle('d-none', !text);
        }

        function closeDropdown() {
            listbox.classList.add('d-none');
            input.setAttribute('aria-expanded', 'false');
            activeOptionIndex = -1;
        }

        function openDropdown() {
            listbox.classList.remove('d-none');
            input.setAttribute('aria-expanded', 'true');
        }

        function updateActiveOption(options) {
            options.forEach((opt, i) => {
                opt.classList.toggle('active', i === activeOptionIndex);
                if (i === activeOptionIndex) input.setAttribute('aria-activedescendant', opt.id);
            });
            if (options[activeOptionIndex]) {
                options[activeOptionIndex].scrollIntoView({ block: 'nearest' });
            }
        }

        // Only enforced while the field is actually `required` (i.e. the
        // graduate is currently employed) - see the employment-status
        // handler above, which calls updateValidity() on both combos
        // whenever it flips `required` on these fields.
        function updateValidity() {
            if (!input.required || !input.value.trim()) {
                input.setCustomValidity('');
                return;
            }
            input.setCustomValidity(''); // Required typed answers are valid without a catalog id.
        }

        function renderList(items) {
            listbox.textContent = '';
            if (items.length === 0) {
                const li = document.createElement('li');
                li.className = 'skill-empty';
                li.textContent = 'No matches. Your typed answer will be saved.';
                listbox.appendChild(li);
                return;
            }
            items.forEach((item, i) => {
                const li = document.createElement('li');
                li.className = 'skill-option';
                li.id = listbox.id + '-opt-' + i;
                li.setAttribute('role', 'option');
                li.dataset.itemId = item.id;
                li.dataset.itemName = item.name;

                const nameEl = document.createElement('span');
                nameEl.className = 'skill-option-name';
                nameEl.textContent = item.name;
                li.appendChild(nameEl);

                if (item.categoryName) {
                    const catEl = document.createElement('span');
                    catEl.className = 'skill-option-category';
                    catEl.textContent = item.categoryName;
                    li.appendChild(catEl);
                }

                li.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    selectItem(item);
                });
                listbox.appendChild(li);
            });
        }

        function selectItem(item) {
            input.value = item.name;
            hidden.value = item.id;
            showMessage('');
            closeDropdown();
            updateValidity();
            if (opts.onSelect) opts.onSelect(item);
        }

        input.addEventListener('focus', function () {
            renderList(getItems(input.value || ''));
            openDropdown();
        });

        input.addEventListener('input', function () {
            hidden.value = ''; // editing after a selection clears the hidden id
            showMessage('');
            updateValidity();
            activeOptionIndex = -1;
            renderList(getItems(input.value));
            openDropdown();
        });

        input.addEventListener('blur', function () {
            setTimeout(function () {
                closeDropdown();
                showMessage('');
            }, 150);
        });

        input.addEventListener('keydown', function (e) {
            const options = Array.from(listbox.querySelectorAll('.skill-option'));
            if (listbox.classList.contains('d-none') || options.length === 0) return;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeOptionIndex = Math.min(activeOptionIndex + 1, options.length - 1);
                updateActiveOption(options);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeOptionIndex = Math.max(activeOptionIndex - 1, 0);
                updateActiveOption(options);
            } else if (e.key === 'Enter') {
                if (activeOptionIndex >= 0 && options[activeOptionIndex]) {
                    e.preventDefault();
                    selectItem({
                        id: options[activeOptionIndex].dataset.itemId,
                        name: options[activeOptionIndex].dataset.itemName,
                    });
                }
            } else if (e.key === 'Escape') {
                closeDropdown();
            }
        });

        document.addEventListener('click', function (e) {
            if (!input.closest('.skill-search-wrap').contains(e.target)) {
                closeDropdown();
            }
        });

        return {
            setValue: function (id, name) {
                hidden.value = id || '';
                input.value = name || '';
                updateValidity();
            },
            clearSelection: function (msg) {
                hidden.value = '';
                input.value = '';
                updateValidity();
                if (msg) showMessage(msg);
            },
            updateValidity: updateValidity,
        };
    }

    function searchByName(pool, query, limit) {
        const q = query.trim().toLowerCase();
        if (!q) return pool.slice(0, limit);
        const starts = [];
        const contains = [];
        pool.forEach(item => {
            if (item.searchKey.startsWith(q)) starts.push(item);
            else if (item.searchKey.includes(q)) contains.push(item);
        });
        return starts.concat(contains).slice(0, limit);
    }

    // -- Present Occupation --
    let occupationCombo = null;
    const presentOccupationInput = document.getElementById('presentOccupationInput');
    if (presentOccupationInput) {
        const occupationHiddenId = document.getElementById('presentOccupationId');
        const occupationListbox = document.getElementById('presentOccupationListbox');
        const occupationMessage = document.getElementById('presentOccupationMessage');
        const occupationHint = document.getElementById('presentOccupationHint');
        let occupationCatalog = [];
        let occupationById = {};

        function updateOccupationHint() {
            if (!occupationHint) return;
            occupationHint.textContent = currentEmploymentTypeId()
                ? 'Suggestions are ranked by how common they are for the selected employment status.'
                : 'Select "Present Employment Status" above to filter suggestions by employment type.';
        }

        function occupationItems(query) {
            const empTypeId = currentEmploymentTypeId();
            let pool = occupationCatalog;
            if (empTypeId) {
                // Exclude occupations rated "not_applicable" for this
                // employment type entirely; rank common > possible >
                // uncommon within what's left (mirrors the dataset's own
                // filtering_config, including its "uncommon" expanded-
                // search tier as a natural fallback when nothing more
                // common matches the typed text).
                const tierRank = { common: 0, possible: 1, uncommon: 2 };
                pool = pool
                    .filter(o => {
                        const rating = o.availability[empTypeId];
                        return rating && rating !== 'not_applicable';
                    })
                    .slice()
                    .sort((a, b) => (tierRank[a.availability[empTypeId]] ?? 3) - (tierRank[b.availability[empTypeId]] ?? 3));
            }
            return searchByName(pool, query, 40);
        }

        occupationCombo = createSearchableSingleSelect(
            presentOccupationInput, occupationHiddenId, occupationListbox, occupationMessage,
            occupationItems,
            { selectMessage: 'Please select an occupation from the suggestions.', emptyText: 'No matching occupations found.' }
        );

        fetch('/data/occupations.json')
            .then(r => r.json())
            .then(data => {
                (data.categories || []).forEach(cat => {
                    (cat.occupations || []).forEach(occ => {
                        const item = {
                            id: occ.id,
                            name: occ.name,
                            categoryName: cat.name,
                            searchKey: (occ.name || '').toLowerCase(),
                            availability: occ.employment_type_availability || {},
                        };
                        occupationCatalog.push(item);
                        occupationById[occ.id] = item;
                    });
                });
            })
            .catch(() => {
                if (occupationMessage) {
                    occupationMessage.textContent = 'Suggestions unavailable. You can still type your own occupation.';
                    occupationMessage.classList.remove('d-none');
                }
            });

        updateOccupationHint();

        // Employment-type change: drop the current selection if it's no
        // longer compatible, and refresh the hint text.
        document.querySelectorAll('[data-selfemployed-toggle]').forEach(r => {
            r.addEventListener('change', function () {
                updateOccupationHint();
                const empTypeId = currentEmploymentTypeId();
                const currentId = occupationHiddenId.value;
                if (currentId && empTypeId) {
                    const occ = occupationById[currentId];
                    const rating = occ ? occ.availability[empTypeId] : null;
                    if (!rating || rating === 'not_applicable') {
                        occupationCombo.clearSelection('Your previous occupation selection isn\'t available for the new employment status - please choose again.');
                    }
                }
            });
        });
    }

    // -- Major Line of Business --
    let businessLineCombo = null;
    const businessLineInput = document.getElementById('businessLineInput');
    if (businessLineInput) {
        const businessLineHiddenId = document.getElementById('businessLineId');
        const businessLineListbox = document.getElementById('businessLineListbox');
        const businessLineMessage = document.getElementById('businessLineMessage');
        let businessLineCatalog = [];

        businessLineCombo = createSearchableSingleSelect(
            businessLineInput, businessLineHiddenId, businessLineListbox, businessLineMessage,
            query => searchByName(businessLineCatalog, query, 40),
            { selectMessage: 'Please select a business line from the suggestions.', emptyText: 'No matching business lines found.' }
        );

        fetch('/data/major_lines_of_business.json')
            .then(r => r.json())
            .then(data => {
                (data.categories || []).forEach(cat => {
                    (cat.business_lines || []).forEach(line => {
                        businessLineCatalog.push({
                            id: line.id,
                            name: line.name,
                            categoryName: cat.name,
                            searchKey: (line.name || '').toLowerCase(),
                        });
                    });
                });
            })
            .catch(() => {
                if (businessLineMessage) {
                    businessLineMessage.textContent = 'Suggestions unavailable. You can still type your own business line.';
                    businessLineMessage.classList.remove('d-none');
                }
            });
    }

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

        const stayingVisible = isFirstJob === true;
        const relatedVisible = isFirstJob === true;
        const acceptingVisible = isFirstJob === true && isRelated === true;
        const changingVisible = isFirstJob === false || (isFirstJob === true && isRelated === false);

        document.getElementById('stayingBlock').classList.toggle('show', stayingVisible);
        document.getElementById('relatedCourseBlock').classList.toggle('show', relatedVisible);
        document.getElementById('acceptingBlock').classList.toggle('show', acceptingVisible);
        document.getElementById('changingBlock').classList.toggle('show', changingVisible);

        stayingReasonsValidator.setActive(stayingVisible);
        setRequired('[data-relatedcourse-toggle]', relatedVisible);
        acceptingReasonsValidator.setActive(acceptingVisible);
        changingReasonsValidator.setActive(changingVisible);

        updateFirstJobDetailsVisibility();
    }

    // D: How long did you stay in your first job? / How did you find your
    // first job? / How long did it take you to land your first job? /
    // Initial gross monthly earning (first job) / Job Level - First Job /
    // Job Level - Current/Present Job / Was the curriculum relevant to
    // your first job? - hidden when "Never Employed" is selected, and
    // also hidden when the graduate IS currently employed (Yes) AND their
    // present job IS their first job (nothing to compare against a
    // separate "first job" in that case). Re-run on every employment-
    // status or first-job change (see the two listeners below).
    function updateFirstJobDetailsVisibility() {
        const empEl = document.querySelector('[data-employment-toggle]:checked');
        const emp = empEl ? empEl.value : null;
        const fjEl = document.querySelector('[data-firstjob-toggle]:checked');
        const isFirstJob = fjEl ? fjEl.value === '1' : null;

        const visible = emp !== 'never_employed' && !(emp === 'yes' && isFirstJob === true);

        document.getElementById('firstJobDetailsBlock').classList.toggle('show', visible);
        setRequired('[data-curriculum-toggle]', visible);

        if (!visible) {
            document.getElementById('competenciesBlock').classList.remove('show');
            competenciesValidator.setActive(false);
        }
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
            const visible = this.value === '1';
            document.getElementById('competenciesBlock').classList.toggle('show', visible);
            competenciesValidator.setActive(visible);
        });
    });

    // ================================================================
    // Draft autosave (localStorage) - saves as the user types so an
    // accidental tab close doesn't lose their progress. Cleared on
    // successful submit.
    // ================================================================
    const draftKey = document.getElementById('tracerForm').dataset.draftKey;
    function updateSchoolYearBirthday() {
        const year = document.querySelector('[name="education[0][year_graduated]"]');
        const birthday = document.querySelector('[name="birthday"]');
        const match = year?.value.match(/(\d{4})\D+(\d{4})/);
        const birthYear = match ? Number(match[2]) - 22 : null;
        if (birthYear) birthday.max = birthYear + '-12-31';
        else birthday.removeAttribute('max');
        document.getElementById('birthdayYearHint').textContent = birthYear ? 'Must be born in ' + birthYear + ' or earlier.' : '';
    }
    document.getElementById('educationRows').addEventListener('change', updateSchoolYearBirthday);
    let savedDraft = null;
    try {
        const raw = localStorage.getItem(draftKey);
        if (raw) savedDraft = JSON.parse(raw);
    } catch (e) {
        savedDraft = null;
    }

    // If a draft exists, let the region dropdown (below) know to prefer it
    // over the server-rendered old()/prefill value.
    if (savedDraft) {
        ['region_of_origin', 'permanent_province', 'permanent_municipality', 'permanent_barangay', 'current_province', 'current_municipality', 'current_barangay'].forEach(name => {
            const target = document.querySelector(`[name="${name}"]`);
            if (target && savedDraft[name] && savedDraft[name][0]) {
                target.dataset.selected = savedDraft[name][0];
            }
        });
    }

    // ---- Location of Residence: "City" and "Municipality" are rendered as
    // checkboxes per the form design, but only one can ever apply - checking
    // one unchecks the other. Actual required-ness (must pick exactly one)
    // is enforced server-side by GraduateTracerController's
    // `residence_location => in:city,municipality` rule. ----
    document.querySelectorAll('[data-residence-toggle]').forEach(box => {
        box.addEventListener('change', () => {
            if (box.checked) {
                document.querySelectorAll('[data-residence-toggle]').forEach(other => {
                    if (other !== box) other.checked = false;
                });
            }
        });
    });

    // ---- Permanent/Current Address cascades (Region -> Province -> City
    // -> Barangay). Permanent Address's Region select doubles as the
    // "Region of Origin" answer (it has name="region_of_origin" and is the
    // only one of the two that actually submits a region value) - Current
    // Address's Region select is a UI-only cascade aid with no `name`,
    // since only its province/municipality/barangay are actually stored. ----
    fetch('{{ asset("data/ph_geo.json") }}')
        .then(res => res.json())
        .then(geo => {
            fetch('{{ asset("data/ph_barangays.json") }}')
                .then(res => res.json())
                .then(barangaysByCity => {
                    wireAddressCascade(geo, barangaysByCity, 'permanent');
                    wireAddressCascade(geo, barangaysByCity, 'current');
                    wireSameAsPermanentAddress();
                })
                .catch(() => {
                    // Barangay data failed to load - still wire up
                    // Region/Province/City so the rest of the form works;
                    // Barangay just falls back to "not available".
                    wireAddressCascade(geo, {}, 'permanent');
                    wireAddressCascade(geo, {}, 'current');
                    wireSameAsPermanentAddress();
                });
        })
        .catch(() => {
            ['permanent', 'current'].forEach(prefix => {
                document.getElementById(prefix + 'Region').innerHTML = '<option value="">Could not load region list - refresh the page</option>';
            });
        });

    function wireAddressCascade(geo, barangaysByCity, prefix) {
        const regionEl = document.getElementById(prefix + 'Region');
        const provinceEl = document.getElementById(prefix + 'Province');
        const cityEl = document.getElementById(prefix + 'City');
        const barangayEl = document.getElementById(prefix + 'Barangay');

        const selectedProvinceName = provinceEl.dataset.selected || '';
        const selectedCityName = cityEl.dataset.selected || '';
        const selectedBarangayName = barangayEl.dataset.selected || '';

        regionEl.innerHTML = '<option value="" disabled selected>Select region</option>' +
            geo.regions.map(r => `<option value="${r.name}" data-code="${r.code}">${r.name}</option>`).join('');

        function populateProvinces(regionCode, keepSelected) {
            const provinces = geo.provinces.filter(p => p.region_code === regionCode);
            if (provinces.length === 0) {
                // Some regions (e.g. NCR) have no provinces - keep the
                // field visible rather than hiding it, just with a single
                // non-required "N/A" option, so it never looks like the
                // Province field has disappeared from the form.
                provinceEl.removeAttribute('required');
                provinceEl.innerHTML = '<option value="" selected>N/A - this region has no provinces</option>';
                return null;
            }
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

        function populateBarangays(cityName, keepSelected) {
            const barangays = barangaysByCity[cityName] || [];
            if (barangays.length === 0) {
                barangayEl.innerHTML = '<option value="">Not available for this city/municipality</option>';
                return;
            }
            barangayEl.innerHTML = '<option value="" disabled selected>Select barangay</option>' +
                barangays.map(b => `<option value="${b}">${b}</option>`).join('');
            if (keepSelected && barangays.includes(keepSelected)) {
                barangayEl.value = keepSelected;
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
            barangayEl.innerHTML = '<option value="" selected>Select city/municipality first</option>';
        });

        provinceEl.addEventListener('change', function () {
            const regionCode = regionEl.selectedOptions[0]?.dataset.code;
            const provinceCode = this.selectedOptions[0]?.dataset.code;
            populateCities(regionCode, provinceCode, null);
            barangayEl.innerHTML = '<option value="" selected>Select city/municipality first</option>';
        });

        cityEl.addEventListener('change', function () {
            populateBarangays(this.value, null);
        });

        // If editing a previously-saved address, we only know the
        // province/city names (not which region they belong to) - find the
        // right region by looking up the saved province or city first, so
        // the cascade can restore itself the same way a fresh selection
        // would build it. Falls back to the region select's own saved
        // value (relevant for Permanent Address, whose region IS submitted
        // as region_of_origin) if province/city aren't set yet.
        let inferredRegionCode = null;
        if (selectedProvinceName) {
            const match = geo.provinces.find(p => p.name === selectedProvinceName);
            inferredRegionCode = match?.region_code || null;
        } else if (selectedCityName) {
            const match = geo.cities.find(c => c.name === selectedCityName);
            inferredRegionCode = match?.region_code || null;
        } else if (regionEl.dataset.selected) {
            const match = geo.regions.find(r => r.name === regionEl.dataset.selected);
            inferredRegionCode = match?.code || null;
        }

        if (inferredRegionCode) {
            const regionMatch = geo.regions.find(r => r.code === inferredRegionCode);
            if (regionMatch) {
                regionEl.value = regionMatch.name;
                const provinces = populateProvinces(inferredRegionCode, selectedProvinceName);
                const provinceCode = provinceEl.selectedOptions[0]?.dataset.code;
                populateCities(inferredRegionCode, provinces ? provinceCode : null, selectedCityName);
                if (selectedCityName) {
                    populateBarangays(selectedCityName, selectedBarangayName);
                }
            }
        }
    }

    // ---- "(same as permanent address)" checkbox: copies Permanent
    // Address's Region/Province/City/Barangay/Street into Current
    // Address's matching fields, driving the same cascade listeners
    // wireAddressCascade('current') already attached above (by setting a
    // value and dispatching 'change', rather than duplicating the
    // populate*() logic that's private to wireAddressCascade). Stays in
    // sync afterward for as long as the box is checked - editing
    // Permanent Address re-copies automatically; unchecking hands control
    // back to the user without clearing what was last copied. ----
    function wireSameAsPermanentAddress() {
        const checkbox = document.getElementById('sameAsPermanentAddress');
        const fieldsWrap = document.getElementById('currentAddressFields');

        const permanent = {
            region: document.getElementById('permanentRegion'),
            province: document.getElementById('permanentProvince'),
            city: document.getElementById('permanentCity'),
            barangay: document.getElementById('permanentBarangay'),
            street: document.getElementById('permanentStreet'),
        };
        const current = {
            region: document.getElementById('currentRegion'),
            province: document.getElementById('currentProvince'),
            city: document.getElementById('currentCity'),
            barangay: document.getElementById('currentBarangay'),
            street: document.getElementById('currentStreet'),
        };

        function copyPermanentToCurrent() {
            if (!permanent.region.value) return;

            current.region.value = permanent.region.value;
            current.region.dispatchEvent(new Event('change', { bubbles: true }));

            if (permanent.province.value && Array.from(current.province.options).some(o => o.value === permanent.province.value)) {
                current.province.value = permanent.province.value;
                current.province.dispatchEvent(new Event('change', { bubbles: true }));
            }

            if (permanent.city.value && Array.from(current.city.options).some(o => o.value === permanent.city.value)) {
                current.city.value = permanent.city.value;
                current.city.dispatchEvent(new Event('change', { bubbles: true }));
            }

            if (permanent.barangay.value && Array.from(current.barangay.options).some(o => o.value === permanent.barangay.value)) {
                current.barangay.value = permanent.barangay.value;
            }

            current.street.value = permanent.street.value;
        }

        checkbox.addEventListener('change', function () {
            fieldsWrap.classList.toggle('tracer-fields-locked', this.checked);
            if (this.checked) copyPermanentToCurrent();
        });

        [permanent.region, permanent.province, permanent.city, permanent.barangay].forEach(el => {
            el.addEventListener('change', () => {
                if (checkbox.checked) copyPermanentToCurrent();
            });
        });
        permanent.street.addEventListener('input', () => {
            if (checkbox.checked) copyPermanentToCurrent();
        });
    }

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
                addRow(groupKey, true);
            }
        });

        // Same idea for the self-employed skills rows, which aren't part
        // of the generic repeatableGroups system (see addSkillRowRef).
        if (typeof addSkillRowRef === 'function') {
            let maxSkillIndex = -1;
            Object.keys(draft).forEach(name => {
                const m = name.match(/^self_employed_skills\[(\d+)\]/);
                if (m) maxSkillIndex = Math.max(maxSkillIndex, parseInt(m[1], 10));
            });
            const skillContainer = document.getElementById('skillRows');
            while (skillContainer && skillContainer.children.length <= maxSkillIndex) {
                addSkillRowRef();
            }
        }

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

        occupationCombo?.updateValidity();
        businessLineCombo?.updateValidity();
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

    // Prevent spam-clicking Submit from firing duplicate requests (which
    // could otherwise create duplicate rows for the repeatable sections,
    // or just hammer the server with the same POST several times over).
    // The 'submit' event only fires once the browser's own required-field
    // validation has already passed, so disabling the button here can't
    // trap someone behind a validation error with no way to retry - if
    // validation fails, 'submit' never fires and the button stays enabled.
    //
    // The percentage shown is a simulated "still working" indicator, not
    // real upload/network progress. This is a plain full-page form POST
    // (no AJAX), and the actual wait here is mostly server-side - Laravel
    // inserting rows across ~10 tables in a transaction - which a real
    // upload-progress event couldn't see anyway (it only measures how much
    // of the request body has been sent, not how long the server takes to
    // process it). Easing toward 90% and stopping there communicates "this
    // is still going" honestly without claiming a precision we don't have;
    // it's never expected to reach 100% itself since the page navigates
    // away (success) or reloads fresh (validation error) either way.
    function startFakeProgress(button, label) {
        let percent = 0;
        button.disabled = true;
        setInterval(() => {
            percent = Math.min(90, percent + Math.max(1, Math.round((90 - percent) * 0.15)));
            button.textContent = `${label} ${percent}%`;
        }, 150);
    }

    tracerFormEl.addEventListener('submit', function () {
        startFakeProgress(submitBtn, 'Submitting');
    });

    // ---- Disclosure & Consent: the checkbox can only end up checked once
    // the browser has actually handed back a location fix. The Submit
    // button stays disabled until both the checkbox is checked and a
    // latitude/longitude pair has been captured. ----
    const consentCheckbox = document.getElementById('consentCheckbox');
    const consentLat = document.getElementById('consentLatitude');
    const consentLng = document.getElementById('consentLongitude');
    const consentHint = document.getElementById('consentLocationHint');

    function showConsentHint(message) {
        consentHint.textContent = message;
        consentHint.classList.remove('d-none');
    }

    function updateSubmitAvailability() {
        const ready = consentCheckbox.checked && consentLat.value !== '' && consentLng.value !== '';
        submitBtn.disabled = !ready;
    }

    consentCheckbox.addEventListener('change', function () {
        if (!this.checked) {
            // Unchecked (either by the user, or provisionally by us below
            // while a location fix is pending) - clear any previously
            // captured coordinates so a stale fix can't sneak through.
            consentLat.value = '';
            consentLng.value = '';
            updateSubmitAvailability();
            return;
        }

        // Provisionally uncheck until location succeeds - this is what
        // makes "the checkbox will not be checked" true while location is
        // off, rather than letting it check first and validating after.
        this.checked = false;
        updateSubmitAvailability();

        if (!('geolocation' in navigator)) {
            showConsentHint('Your browser does not support location services, which are required to give consent.');
            return;
        }

        showConsentHint('Requesting your location…');

        navigator.geolocation.getCurrentPosition(
            function (position) {
                consentLat.value = position.coords.latitude;
                consentLng.value = position.coords.longitude;
                consentCheckbox.checked = true;
                consentHint.classList.add('d-none');
                updateSubmitAvailability();
            },
            function () {
                showConsentHint('Please turn on location services for this site, then check the box again.');
                updateSubmitAvailability();
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    });

    updateSubmitAvailability();
    updateSchoolYearBirthday();

    render();
})();
</script>
@endsection
