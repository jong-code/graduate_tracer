@extends('layouts.app')
@section('title', 'Integrations')

@section('content')
<div class="tracer-wrapper" style="max-width: 800px;">
    <div class="tracer-header"><h1>Integrations - Free Load Reward</h1></div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="tracer-card">
        <p class="text-muted">GCash numbers graduates submitted from their dashboard after completing their survey, in exchange for a free-load reward. Mark a row "Done" once the reward has actually been sent - that's a manual step, nothing here sends anything automatically.</p>

        <table class="table table-sm align-middle">
            <thead><tr><th>Name</th><th>GCash Number</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse ($users as $u)
                    <tr>
                        <td>{{ $u->name }}</td>
                        <td>{{ $u->userNumber?->number ?? '—' }}</td>
                        <td>
                            @if (! $u->userNumber)
                                <span class="text-muted">No number submitted</span>
                            @elseif ($u->userNumber->is_done)
                                <span class="text-success fw-semibold">✓ Done</span>
                            @else
                                <span class="text-warning fw-semibold">Pending</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if ($u->userNumber && ! $u->userNumber->is_done)
                                <form method="POST" action="{{ route('admin.integrations.mark-done', $u->userNumber) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success">Done</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">No graduates yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
