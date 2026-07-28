@extends('layouts.app')
@section('title', 'Manage Users')

@section('content')
<div class="tracer-wrapper" style="max-width: 900px;">
    <div class="tracer-header d-flex justify-content-between align-items-start">
        <div>
            <h1>Manage Users</h1>
            <p>Assign roles: user (graduate), faculty, or admin</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn-tracer-submit">+ Add User</a>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="tracer-card">
        <table class="table align-middle">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @foreach ($users as $u)
                    <tr>
                        <td>{{ $u->name }}</td>
                        <td>{{ $u->email }}</td>
                        <td><span class="badge bg-secondary">{{ ucfirst($u->role) }}</span></td>
                        <td>{{ $u->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="text-end">
                            @if ($u->survey?->submitted_at)
                                <a href="{{ route('admin.surveys.reason', $u->survey) }}" class="btn btn-sm btn-outline-secondary">Survey</a>
                            @endif
                            <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.users.destroy', $u) }}" class="d-inline" id="delete-form-{{ $u->id }}">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-sm btn-outline-danger" data-delete-name="{{ $u->name }}" data-delete-form="delete-form-{{ $u->id }}" onclick="openDeleteModal(this)">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $users->links() }}
    </div>
</div>

<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteUserName"></strong>? This cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" disabled onclick="confirmDelete()">Delete user (<span id="deleteCountdown">5</span>)</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let deleteModal;
    let deleteFormId = null;
    let countdownTimer = null;

    function openDeleteModal(button) {
        deleteFormId = button.dataset.deleteForm;
        document.getElementById('deleteUserName').textContent = button.dataset.deleteName;

        const confirmBtn = document.getElementById('confirmDeleteBtn');
        const countdownEl = document.getElementById('deleteCountdown');
        let secondsLeft = 5;
        confirmBtn.disabled = true;
        countdownEl.textContent = secondsLeft;

        clearInterval(countdownTimer);
        countdownTimer = setInterval(() => {
            secondsLeft -= 1;
            if (secondsLeft <= 0) {
                clearInterval(countdownTimer);
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Delete user';
            } else {
                countdownEl.textContent = secondsLeft;
            }
        }, 1000);

        if (!deleteModal) {
            deleteModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
        }
        deleteModal.show();
    }

    // Stop the countdown if the modal is dismissed before it finishes, so
    // reopening it (for the same or a different user) always starts fresh.
    document.getElementById('deleteUserModal').addEventListener('hidden.bs.modal', () => {
        clearInterval(countdownTimer);
        deleteFormId = null;
    });

    function confirmDelete() {
        if (deleteFormId) {
            document.getElementById(deleteFormId).submit();
        }
    }
</script>
@endsection
