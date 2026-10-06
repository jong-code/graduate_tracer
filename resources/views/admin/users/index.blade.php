@extends('layouts.app')
@section('title', 'Manage Users')

@section('content')
<div class="tracer-wrapper" style="max-width: 900px;">
    <div class="tracer-header d-flex justify-content-between align-items-start">
        <div>
            <h1>Manage Users</h1>
            <p>Assign roles: user (graduate), faculty, or admin</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn-tracer-submit" data-bs-toggle="modal" data-bs-target="#surveyNotificationModal">Survey Notification</button>
            <a href="{{ route('admin.users.create') }}" class="btn-tracer-submit">+ Add User</a>
        </div>
    </div>

    <div class="modal fade" id="surveyNotificationModal" tabindex="-1" aria-labelledby="surveyNotificationModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="surveyNotificationModalLabel">Send Survey Notification?</h5>
                    <button type="button" class="btn-close" id="sn-close-btn" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="sn-confirm-view">
                        <p>This will email every user who has not yet submitted their survey, one at a time a few seconds apart. <strong>Keep this tab open until it finishes</strong> - closing it or navigating away will pause the run partway through (safe to resume later; already-sent users won't be re-emailed).</p>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sn-force-resend">
                            <label class="form-check-label" for="sn-force-resend">
                                Force resend - also email users notified within the last 20 hours
                            </label>
                        </div>
                    </div>
                    <div id="sn-progress-view" class="d-none">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong id="sn-current-name">Starting...</strong>
                            <span id="sn-state" class="badge bg-secondary">Sending...</span>
                        </div>
                        <div class="progress mb-2" style="height: 8px;">
                            <div id="sn-progress-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                        <div class="d-flex flex-wrap gap-3">
                            <div>Total: <strong id="sn-total">0</strong></div>
                            <div>Sent: <strong id="sn-sent">0</strong></div>
                            <div>Failed: <strong id="sn-failed">0</strong></div>
                            <div>Remaining: <strong id="sn-remaining">0</strong></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="sn-cancel-btn">Cancel</button>
                    <button type="button" class="btn-tracer-submit" id="sn-start-btn">Send Notification</button>
                </div>
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalEl = document.getElementById('surveyNotificationModal');
            const modal = new bootstrap.Modal(modalEl);
            const confirmView = document.getElementById('sn-confirm-view');
            const progressView = document.getElementById('sn-progress-view');
            const startBtn = document.getElementById('sn-start-btn');
            const cancelBtn = document.getElementById('sn-cancel-btn');
            const closeBtn = document.getElementById('sn-close-btn');
            const currentNameEl = document.getElementById('sn-current-name');
            const stateEl = document.getElementById('sn-state');
            const barEl = document.getElementById('sn-progress-bar');
            const totalEl = document.getElementById('sn-total');
            const sentEl = document.getElementById('sn-sent');
            const failedEl = document.getElementById('sn-failed');
            const remainingEl = document.getElementById('sn-remaining');
            const forceCheckbox = document.getElementById('sn-force-resend');

            const recipientsUrl = @json(route('admin.users.survey-notification-recipients'));
            const sendOneUrlTemplate = @json(route('admin.users.survey-notification-send-one', ['user' => '__ID__']));
            const csrfToken = @json(csrf_token());
            const SEND_DELAY_MS = 7000; // stagger between sends, same pacing as before

            document.getElementById('surveyNotificationModal').addEventListener('show.bs.modal', function () {
                confirmView.classList.remove('d-none');
                progressView.classList.add('d-none');
                startBtn.classList.remove('d-none');
                cancelBtn.classList.remove('d-none');
                closeBtn.classList.remove('d-none');
                forceCheckbox.checked = false;
            });

            function sleep(ms) {
                return new Promise((resolve) => setTimeout(resolve, ms));
            }

            async function runSendLoop(recipients, force) {
                let sent = 0;
                let failed = 0;
                const total = recipients.length;

                totalEl.textContent = total;
                remainingEl.textContent = total;

                for (let i = 0; i < recipients.length; i++) {
                    const recipient = recipients[i];
                    currentNameEl.textContent = 'Sending to ' + recipient.name + '...';

                    try {
                        const sendOneUrl = sendOneUrlTemplate.replace('__ID__', recipient.id) + (force ? '?force=1' : '');
                        const res = await fetch(sendOneUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                        });
                        const data = await res.json();

                        if (data.sent) {
                            sent++;
                        } else if (!data.skipped) {
                            failed++;
                        }
                        // skipped (already submitted/deactivated since the list was built) counts as neither
                    } catch (e) {
                        failed++;
                    }

                    const done = i + 1;
                    sentEl.textContent = sent;
                    failedEl.textContent = failed;
                    remainingEl.textContent = total - done;
                    barEl.style.width = Math.round((done / total) * 100) + '%';

                    if (done < total) {
                        await sleep(SEND_DELAY_MS);
                    }
                }

                currentNameEl.textContent = 'Finished';
                stateEl.textContent = 'Finished';
                stateEl.classList.remove('bg-secondary');
                stateEl.classList.add('bg-success');
                closeBtn.classList.remove('d-none');
            }

            startBtn.addEventListener('click', async function () {
                startBtn.disabled = true;
                cancelBtn.classList.add('d-none');
                closeBtn.classList.add('d-none');
                confirmView.classList.add('d-none');
                progressView.classList.remove('d-none');
                currentNameEl.textContent = 'Loading recipient list...';

                const force = forceCheckbox.checked;

                try {
                    const url = recipientsUrl + (force ? '?force=1' : '');
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();

                    if (!data.recipients || data.recipients.length === 0) {
                        currentNameEl.textContent = 'No users are pending - everyone has either submitted or is inactive.';
                        stateEl.textContent = 'Done';
                        stateEl.classList.remove('bg-secondary');
                        stateEl.classList.add('bg-success');
                        closeBtn.classList.remove('d-none');
                        startBtn.disabled = false;
                        return;
                    }

                    await runSendLoop(data.recipients, force);
                } catch (e) {
                    currentNameEl.textContent = 'Something went wrong starting the notification run.';
                    closeBtn.classList.remove('d-none');
                } finally {
                    startBtn.disabled = false;
                }
            });
        });
    </script>


    <div class="tracer-card">
        @include('partials.name-filter', ['action' => route('admin.users.index'), 'target' => '#userResults', 'userFilters' => true, 'sortable' => true])
        <div id="userResults">
        <p class="small text-muted" data-result-summary>{{ $users->total() }} accounts found.</p>
        <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Email Verified</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse ($users as $u)
                    <tr>
                        <td>{{ Illuminate\Support\Str::upper($u->displayName()) }}</td>
                        <td>{{ $u->email }}</td>
                        <td><span class="badge bg-secondary">{{ ucfirst($u->role) }}</span></td>
                        <td><span class="badge {{ $u->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $u->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            @if ($u->email_verified_at)
                                <span class="badge bg-success" title="Verified {{ $u->email_verified_at->format('M j, Y g:ia') }}">Verified</span>
                            @else
                                <span class="badge bg-warning text-dark">Unverified</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if ($u->survey?->submitted_at)
                                <a href="{{ route('admin.templates.survey-preview', $u->survey) }}" class="btn btn-sm btn-outline-secondary">Survey</a>
                            @endif
                            <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-outline-secondary position-relative">
                                Edit
                                @if ($u->needsNameUpdate())
                                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"
                                          title="Missing Last Name - please update"></span>
                                @endif
                            </a>
                            <form method="POST" action="{{ route('admin.users.destroy', $u) }}" class="d-inline" id="delete-form-{{ $u->id }}">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-sm btn-outline-danger" data-delete-name="{{ $u->displayName() }}" data-delete-form="delete-form-{{ $u->id }}" onclick="openDeleteModal(this)">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No accounts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        {{ $users->links() }}
        </div>
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
                <p>This permanently removes the account, its draft or submitted survey, personal information and addresses, education and exams, course reasons, training, employment answers and recorded locations, referred graduates, program registrations, and GCash reward record.</p>
                <p class="small text-muted mb-0">Reports and the map will no longer include this graduate. Audit logs and previously downloaded exports or backups remain. To keep survey history while stopping access, edit the account and set its status to Inactive instead.</p>
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
@include('partials.realtime-name-search')
<script>
    let deleteModal;
    let deleteFormId = null;
    let countdownTimer = null;

    function openDeleteModal(button) {
        deleteFormId = button.dataset.deleteForm;
        document.getElementById('deleteUserName').textContent = button.dataset.deleteName;

        const confirmBtn = document.getElementById('confirmDeleteBtn');
        confirmBtn.innerHTML = 'Delete user (<span id="deleteCountdown">5</span>)';
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
