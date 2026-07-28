@extends('layouts.app')
@section('title', 'Register')

@section('content')
<div class="tracer-wrapper" style="max-width: 460px;">
    <div class="tracer-header text-center">
        <h1>Create your account</h1>
        <p>New graduates register below. Tell us your academic program and school year so we can get your survey ready.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="tracer-card">
        <form method="POST" action="{{ route('register') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
            </div>

            <hr>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Academic Program <span class="req">*</span></label>
                    <select name="academic_program_id" class="form-select" required>
                        <option value="" disabled {{ empty(old('academic_program_id')) ? 'selected' : '' }}>Select your program</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" {{ (string) old('academic_program_id') === (string) $program->id ? 'selected' : '' }}>
                                {{ $program->name }} ({{ $program->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">School Year <span class="req">*</span></label>
                    <select name="school_year_id" class="form-select" required>
                        <option value="" disabled {{ empty(old('school_year_id')) ? 'selected' : '' }}>Select school year</option>
                        @foreach ($schoolYears as $schoolYear)
                            <option value="{{ $schoolYear->id }}"
                                {{ (string) old('school_year_id', $schoolYear->is_current ? (string) $schoolYear->id : '') === (string) $schoolYear->id ? 'selected' : '' }}>
                                {{ $schoolYear->label }}{{ $schoolYear->is_current ? ' (current)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <button type="submit" class="btn-tracer-submit w-100">Register</button>
        </form>
        <p class="text-center mt-3 mb-0">
            <a href="{{ route('login') }}">Already have an account? Log in</a>
        </p>
    </div>
</div>
@endsection
