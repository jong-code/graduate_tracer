@extends('layouts.app')
@section('title', 'Survey Templates')

@section('content')
<div class="tracer-wrapper" style="max-width: 850px;">
    <div class="tracer-header"><h1>Survey Templates</h1></div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="tracer-card mb-3">
        <h2 class="h5">Current Template</h2>
        @if ($templateExists)
            <p class="mb-1">
                <span class="text-success fw-semibold">✓ gts_template.docx</span> is configured
                ({{ $templateSizeKb }} KB, updated {{ \Illuminate\Support\Carbon::createFromTimestamp($templateUpdatedAt)->format('M j, Y g:ia') }}).
            </p>
            <p class="text-muted mb-0">Used whenever an admin previews or exports a graduate's answers below. To replace it, upload a new <code>gts_template.docx</code> to <code>public/templates/</code> on the server.</p>
        @else
            <p class="text-warning fw-semibold mb-0">No template found at <code>public/templates/gts_template.docx</code> - preview/export won't work until one is added there.</p>
        @endif
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="tracer-card mt-3">
        <h2 class="h5">Export a Graduate's Survey</h2>
        <p class="text-muted">Fills the template above with each graduate's submitted answers. Preview opens the result without saving a file to your computer; Export downloads it.</p>

        @if ($users->isEmpty())
            <p class="text-muted mb-0">No submitted surveys yet.</p>
        @else
            <table class="table table-sm align-middle">
                <thead><tr><th>Graduate</th><th>Email</th><th>Academic Program</th><th>Submitted</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @foreach ($users as $u)
                        <tr>
                            <td>{{ $u->name }}</td>
                            <td>{{ $u->email }}</td>
                            <td>{{ $u->survey->academicProgram->name ?? '—' }}</td>
                            <td>{{ $u->survey->submitted_at->format('M j, Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.templates.survey-preview', $u->survey) }}" class="btn btn-sm btn-outline-secondary">Preview</a>
                                <a href="{{ route('admin.templates.survey-export', $u->survey) }}" class="btn btn-sm btn-outline-secondary">Export</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
