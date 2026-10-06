@php $gi = $survey->generalInformation; $ed = $survey->employmentData; $address = $gi?->address; @endphp

<div class="tracer-card mb-3">
    <h2>A. General Information</h2>
    <dl class="row mb-0">
        <dt class="col-sm-4">Name</dt><dd class="col-sm-8">{{ $gi?->name }}</dd>
        <dt class="col-sm-4">Permanent Address</dt><dd class="col-sm-8">{{ $address?->formattedPermanentAddress() ?: '—' }}</dd>
        <dt class="col-sm-4">Current Address</dt><dd class="col-sm-8">{{ $address?->formattedCurrentAddress() ?: '—' }}</dd>
        <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $gi?->email ?: '—' }}</dd>
        <dt class="col-sm-4">Telephone</dt><dd class="col-sm-8">{{ $gi?->telephone ?: '—' }}</dd>
        <dt class="col-sm-4">Mobile Number</dt><dd class="col-sm-8">{{ $gi?->mobile_number }}</dd>
        <dt class="col-sm-4">Civil Status</dt><dd class="col-sm-8">{{ ucwords(str_replace('_', ' ', $gi?->civil_status ?? '')) }}</dd>
        <dt class="col-sm-4">Sex</dt><dd class="col-sm-8">{{ ucfirst($gi?->sex ?? '') }}</dd>
        <dt class="col-sm-4">Birthday</dt><dd class="col-sm-8">{{ $gi?->birthday?->format('F j, Y') }}</dd>
        <dt class="col-sm-4">Region of Origin</dt><dd class="col-sm-8">{{ $gi?->region_of_origin }}</dd>
        <dt class="col-sm-4">Location of Residence</dt><dd class="col-sm-8">{{ ucfirst($gi?->residence_location ?? '') ?: '—' }}</dd>
        <dt class="col-sm-4">Academic Program</dt><dd class="col-sm-8">{{ $survey->academicProgram->name ?? '—' }}</dd>
        <dt class="col-sm-4">School Year</dt><dd class="col-sm-8">{{ $survey->schoolYear->label ?? '—' }}</dd>
    </dl>
</div>

<div class="tracer-card mb-3">
    <h2>B. Educational Background</h2>
    <table class="table table-sm">
        <thead><tr><th>Degree &amp; Specialization</th><th>College / University</th><th>Year Graduated</th><th>Honors</th></tr></thead>
        <tbody>
            @forelse ($survey->educationalBackgrounds as $e)
                <tr><td>{{ $e->degree }}</td><td>{{ $e->college_university }}</td><td>{{ $e->year_graduated }}</td><td>{{ $e->honors ?: '—' }}</td></tr>
            @empty
                <tr><td colspan="4" class="text-muted">None on file</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($survey->professionalExams->isNotEmpty())
        <h3 class="h6 mt-3">Professional Examination(s) Passed</h3>
        <table class="table table-sm">
            <thead><tr><th>Name of Examination</th><th>Date Taken</th><th>Rating</th></tr></thead>
            <tbody>
                @foreach ($survey->professionalExams as $exam)
                    <tr><td>{{ $exam->exam_name }}</td><td>{{ $exam->date_taken?->format('M Y') ?: '—' }}</td><td>{{ $exam->rating ?: '—' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @php
        $ugReasons = $survey->courseReasons->where('level', 'undergraduate')->where('reason_key', '!=', 'other');
        $gradReasons = $survey->courseReasons->where('level', 'graduate')->where('reason_key', '!=', 'other');
        $otherReason = $survey->courseReasons->firstWhere('reason_key', 'other');
    @endphp
    @if ($ugReasons->isNotEmpty() || $gradReasons->isNotEmpty() || $otherReason)
        <h3 class="h6 mt-3">Reasons for Taking the Course</h3>
        <p class="mb-1"><strong>Undergraduate:</strong> {{ $ugReasons->pluck('reason_key')->map(fn ($k) => str_replace('_', ' ', $k))->implode(', ') ?: '—' }}</p>
        <p class="mb-1"><strong>Graduate:</strong> {{ $gradReasons->pluck('reason_key')->map(fn ($k) => str_replace('_', ' ', $k))->implode(', ') ?: '—' }}</p>
        @if ($otherReason)
            <p class="mb-0"><strong>Others:</strong> {{ $otherReason->other_text }}</p>
        @endif
    @endif
</div>

<div class="tracer-card mb-3">
    <h2>C. Training(s) / Advance Studies</h2>
    <table class="table table-sm">
        <thead><tr><th>Title</th><th>Duration / Credits</th><th>Institution</th></tr></thead>
        <tbody>
            @forelse ($survey->trainings as $t)
                <tr><td>{{ $t->title }}</td><td>{{ $t->duration_credits ?: '—' }}</td><td>{{ $t->institution ?: '—' }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-muted">None on file</td></tr>
            @endforelse
        </tbody>
    </table>
    @if ($survey->advance_study_reason)
        <p class="mb-0"><strong>Reason for pursuing advance studies:</strong>
            {{ ucwords(str_replace('_', ' ', $survey->advance_study_reason)) }}
            @if ($survey->advance_study_reason_other) — {{ $survey->advance_study_reason_other }} @endif
        </p>
    @endif
</div>

<div class="tracer-card mb-3">
    <h2>D. Employment Data</h2>
    <dl class="row mb-2">
        <dt class="col-sm-4">Presently Employed?</dt>
        <dd class="col-sm-8">{{ ['yes' => 'Yes', 'no' => 'No', 'never_employed' => 'Never Employed'][$ed?->employment_status ?? ''] ?? '—' }}</dd>

        @if ($ed?->employment_status !== 'yes')
            <dt class="col-sm-4">Reason(s) not employed</dt>
            <dd class="col-sm-8">
                {{ $ed?->notEmployedReasons->where('reason_key', '!=', 'other')->pluck('reason_key')->map(fn ($k) => str_replace('_', ' ', $k))->implode(', ') ?: '—' }}
                @php $otherNotEmployed = $ed?->notEmployedReasons->firstWhere('reason_key', 'other'); @endphp
                @if ($otherNotEmployed) — {{ $otherNotEmployed->other_text }} @endif
            </dd>
        @else
            <dt class="col-sm-4">Present Employment Status</dt><dd class="col-sm-8">{{ ucwords(str_replace('_', ' ', $ed?->present_employment_status ?? '')) ?: '—' }}</dd>
            <dt class="col-sm-4">Present Occupation</dt><dd class="col-sm-8">{{ $ed?->present_occupation ?: '—' }}</dd>
            <dt class="col-sm-4">Major Line of Business</dt><dd class="col-sm-8">{{ $ed?->business_line ?: '—' }}</dd>
            <dt class="col-sm-4">Place of Work</dt><dd class="col-sm-8">{{ ucfirst($ed?->place_of_work ?? '') ?: '—' }}</dd>
            <dt class="col-sm-4">First Job After College?</dt><dd class="col-sm-8">{{ is_null($ed?->is_first_job) ? '—' : ($ed->is_first_job ? 'Yes' : 'No') }}</dd>
        @endif

        <dt class="col-sm-4">Curriculum Relevant to First Job?</dt>
        <dd class="col-sm-8">{{ is_null($ed?->curriculum_relevant) ? '—' : ($ed->curriculum_relevant ? 'Yes' : 'No') }}</dd>
    </dl>

    @if ($ed?->competencies->isNotEmpty())
        <p class="mb-1"><strong>Competencies useful in first job:</strong>
            {{ $ed->competencies->where('competency_key', '!=', 'other')->pluck('competency_key')->map(fn ($k) => str_replace('_', ' ', $k))->implode(', ') }}
            @php $otherComp = $ed->competencies->firstWhere('competency_key', 'other'); @endphp
            @if ($otherComp) — {{ $otherComp->other_text }} @endif
        </p>
    @endif

    @if ($ed?->curriculum_suggestions)
        <p class="mb-0"><strong>Suggestions to improve the curriculum:</strong> {{ $ed->curriculum_suggestions }}</p>
    @endif
</div>

@if ($survey->otherGraduates->isNotEmpty())
    <div class="tracer-card mb-3">
        <h2>Other Alumni Listed <span class="text-muted fw-normal">(optional section)</span></h2>
        <table class="table table-sm">
            <thead><tr><th>Name</th><th>Address</th><th>Contact Number</th></tr></thead>
            <tbody>
                @foreach ($survey->otherGraduates as $og)
                    <tr><td>{{ $og->name }}</td><td>{{ $og->address }}</td><td>{{ $og->contact_number }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
