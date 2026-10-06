<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Rules\UniqueEncryptedEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    // A user notified this recently is treated as "already handled" even
    // though their survey is still unsubmitted - this is what stops a
    // resumed run (admin closed the tab partway and clicked the button
    // again) from immediately re-emailing everyone the first run already
    // reached, since eligibility is otherwise based purely on
    // survey.submitted_at being null.
    private const RENOTIFY_COOLDOWN_HOURS = 20;

    public function index()
    {
        // name is encrypted (see User::$casts) - ciphertext sorts and
        // filters meaninglessly at the DB level, so everything below
        // (name sort, role filter, verified filter) runs in PHP against
        // the already-decrypted collection, and pagination is done
        // manually over that filtered collection instead of relying on
        // ->orderBy()/->where()->paginate().
        $page = (int) request('page', 1);
        $perPage = 10;

        $role = request('role');
        $verified = request('verified'); // 'yes' | 'no'
        $search = trim((string) request('search', ''));
        $status = request('status');
        $sort = request('sort', 'asc') === 'desc' ? 'desc' : 'asc'; // name A-Z / Z-A

        $sorted = $sort === 'desc'
            ? User::with('survey')->get()->sortByDesc(fn ($u) => $u->nameSortKey())->values()
            : User::with('survey')->get()->sortBy(fn ($u) => $u->nameSortKey())->values();

        if (in_array($role, ['user', 'faculty', 'admin'], true)) {
            $sorted = $sorted->where('role', $role)->values();
        }

        if ($verified === 'yes') {
            $sorted = $sorted->filter(fn ($u) => $u->email_verified_at !== null)->values();
        } elseif ($verified === 'no') {
            $sorted = $sorted->filter(fn ($u) => $u->email_verified_at === null)->values();
        }

        if (in_array($status, ['active', 'inactive'], true)) {
            $sorted = $sorted->where('is_active', $status === 'active')->values();
        }
        $sorted = $sorted->filter(fn ($u) => $u->matchesNameSearch($search))->values();

        $users = new \Illuminate\Pagination\LengthAwarePaginator(
            $sorted->forPage($page, $perPage),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('admin.users.index', compact('users', 'role', 'verified', 'sort', 'search', 'status'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'last_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required', 'email',
                new UniqueEncryptedEmail(),
            ],
            'password' => ['required', 'min:8'],
            'role' => ['required', 'in:user,faculty,admin'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'last_name' => $validated['last_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        // Admin-provisioned accounts, same as RoleSeeder's admin/faculty
        // accounts, are trusted at creation time rather than routed through
        // the self-registration email-verification flow (see AuthController
        // and EmailVerificationController) - otherwise an admin-created
        // graduate account would be locked out of its own dashboard until
        // it clicked an email link for an address it didn't choose to
        // verify itself. email_verified_at is deliberately left out of
        // User::$fillable, so it's set here with forceFill() rather than
        // through the mass-assigned $validated array above.
        $user->forceFill(['email_verified_at' => now()])->save();

        AuditLog::record('user_created', $user, ['role' => $user->role]);

        return redirect()->route('admin.users.index')->with('status', 'User created.');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'last_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required', 'email',
                new UniqueEncryptedEmail(ignoreUserId: $user->id),
            ],
            'role' => ['required', 'in:user,faculty,admin'],
            'is_active' => ['required', 'boolean'],
        ]);

        $previousRole = $user->role;
        $user->update($validated);

        // Same admin-provisioned trust as UserManagementController@store -
        // an admin correcting a typo'd email shouldn't accidentally lock a
        // graduate out of their own account via the 'verified' route gate
        // (see routes/web.php) until they notice and click a new link.
        if ($user->wasChanged('email')) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        if ($previousRole !== $user->role) {
            AuditLog::record('user_role_changed', $user, ['from' => $previousRole, 'to' => $user->role]);
        }

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }

    public function destroy(User $user)
    {
        AuditLog::record('user_deleted', $user, ['email' => $user->email]);
        $user->delete();

        return back()->with('status', 'User deleted.');
    }

    /**
     * Shared eligibility rule for both endpoints below: a graduate_tracer_
     * survey row is created right at registration (see AuthController@
     * register), not only on final submission, so whereDoesntHave('survey')
     * alone would wrongly match almost no one - "not yet submitted" means
     * submitted_at is null, whether that's because the row has no survey at
     * all (legacy/edge case) or a draft row exists but was never finished.
     * The recent-notification cooldown stops a resumed run from
     * immediately re-emailing everyone the previous, interrupted run
     * already reached.
     */
    private function pendingSurveyNotificationUsersQuery(bool $force = false)
    {
        $query = User::query()
            ->where('role', 'user')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereDoesntHave('survey')
                    ->orWhereHas('survey', fn ($q) => $q->whereNull('submitted_at'));
            });

        // The admin's "Force resend" checkbox skips this clause entirely,
        // so it still only ever reaches users who genuinely haven't
        // submitted (the check above), just without the recency guard.
        if (! $force) {
            $query->where(function ($query) {
                $query->whereNull('survey_reminder_sent_at')
                    ->orWhere('survey_reminder_sent_at', '<', now()->subHours(self::RENOTIFY_COOLDOWN_HOURS));
            });
        }

        return $query;
    }

    /**
     * Step 1 of the browser-driven "Survey Notification" flow (see the JS
     * in admin.users.index): returns the ordered list of user ids the
     * admin's browser will then send to one at a time via sendOne() below,
     * pacing itself with its own setTimeout delay between each call. There
     * is deliberately no queue, job, or cron involved anywhere in this
     * feature - the browser tab staying open *is* what drives sending, by
     * design (see conversation: shared hosting cron reliability made a
     * background queue worker impractical to depend on here).
     */
    public function surveyNotificationRecipients(Request $request)
    {
        $force = $request->boolean('force');

        $users = $this->pendingSurveyNotificationUsersQuery($force)
            ->get()
            ->sortBy(fn ($u) => $u->name)
            ->values();

        AuditLog::record('survey_notifications_mass_started', null, ['count' => $users->count(), 'force' => $force]);

        return response()->json([
            'total' => $users->count(),
            'recipients' => $users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values(),
        ]);
    }

    /**
     * Step 2: sends to exactly one user, called once per user by the
     * browser's JS loop. Re-checks eligibility at send time (not just when
     * the list was built a few seconds/minutes earlier in step 1) in case
     * this same user submitted, was deactivated, or was already notified
     * by a second admin tab in the meantime. $force carries the admin's
     * "Force resend" choice through from step 1 so this recheck applies
     * the same rule, not the default cooldown-enforcing one.
     */
    public function sendSurveyNotificationToOne(Request $request, User $user)
    {
        $force = $request->boolean('force');

        $stillEligible = $this->pendingSurveyNotificationUsersQuery($force)
            ->whereKey($user->getKey())
            ->exists();

        if (! $stillEligible) {
            return response()->json(['sent' => false, 'skipped' => true]);
        }

        try {
            $user->sendSurveyReminderNotification();
            $user->forceFill(['survey_reminder_sent_at' => now()])->save();

            return response()->json(['sent' => true]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['sent' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
