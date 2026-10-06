@extends('layouts.app')
@section('title', 'Survey Content')

@section('content')
<div class="tracer-wrapper">
    <div class="tracer-header"><h1>Survey Content — {{ $survey->generalInformation?->name }}</h1></div>

    <div class="alert alert-secondary">This view was logged in the audit log with your stated reason.</div>

    <div class="tracer-card mb-3">
        <h2 class="h5">Export as DOCX</h2>
        <p class="text-muted">Fills the currently configured survey template with this graduate's answers. Also logged separately from the view above.</p>
        <form method="POST" action="{{ route('admin.surveys.export-docx', $survey) }}">
            @csrf
            <div class="mb-2">
                <label class="form-label">Reason for export <span class="text-danger">*</span></label>
                <textarea name="reason" class="form-control" rows="2" required minlength="10" placeholder="e.g. Printing a copy for the graduate's institutional file"></textarea>
            </div>
            @error('export')
                <div class="alert alert-danger py-2">{{ $message }}</div>
            @enderror
            <button type="submit" class="btn-tracer-submit">Export as DOCX</button>
        </form>
    </div>

    <div class="tracer-card">
        <h2>Section A — General Information</h2>
        <p><strong>Name:</strong> {{ $survey->generalInformation?->name }}<br>
        <strong>Permanent Address:</strong> {{ $survey->generalInformation?->address?->formattedPermanentAddress() ?: '—' }}<br>
        <strong>Current Address:</strong> {{ $survey->generalInformation?->address?->formattedCurrentAddress() ?: '—' }}<br>
        <strong>Mobile:</strong> {{ $survey->generalInformation?->mobile_number }}<br>
        <strong>Program:</strong> {{ $survey->academicProgram->name ?? '—' }}<br>
        <strong>School Year:</strong> {{ $survey->schoolYear->label ?? '—' }}</p>
    </div>

    @if ($survey->otherGraduates->isNotEmpty())
        <div class="tracer-card">
            <h2>Referred Alumni <span class="text-muted fw-normal">(voluntary, optional section)</span></h2>
            <p class="text-muted">
                Listed voluntarily by this respondent to help reach other graduates. These individuals did not
                submit this data themselves and have not consented to it directly - handle accordingly.
            </p>
            <table class="table table-sm">
                <thead>
                    <tr><th>Name</th><th>Address</th><th>Contact Number</th></tr>
                </thead>
                <tbody>
                    @foreach ($survey->otherGraduates as $og)
                        <tr>
                            <td>{{ $og->name ?? '—' }}</td>
                            <td>{{ $og->address ?? '—' }}</td>
                            <td>{{ $og->contact_number ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
