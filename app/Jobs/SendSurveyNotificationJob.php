<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sends one SurveyReminderNotification for the admin-triggered "Survey
 * Notification" mass-send button on the Manage Users page.
 *
 * Dispatched once per not-yet-submitted user with a staggered ->delay()
 * (see UserManagementController::sendSurveyNotifications) so the queue
 * worker sends them one at a time, several seconds apart, instead of all
 * at once - avoiding both a Brevo/Gmail rate-limit flood and a PHP
 * request timeout, since these go out through the queue rather than
 * inline in the admin's HTTP request.
 *
 * Requires a queue worker to actually be running - see the
 * Schedule::command('queue:work ...') entry added alongside this in
 * routes/console.php, which piggybacks on the same host-level cron
 * already required for gts:send-survey-reminders.
 *
 * Uses Batchable (see UserManagementController::sendSurveyNotifications,
 * which dispatches these via Bus::batch() rather than one-by-one) so the
 * job_batches row Laravel maintains gives the Manage Users page a live
 * total/sent/failed count without this app needing its own tracking table.
 */
class SendSurveyNotificationJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Don't let Laravel's queue worker retry a failed send - a bounced or
    // rejected email should count as failed once, not hammer Brevo/Gmail
    // again automatically and skew the progress count on retries.
    public int $tries = 1;

    public function __construct(public int $userId)
    {
    }

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            return;
        }

        // A user who's already been skipped/removed shouldn't count as a
        // failure - a batch entry that's neither sent nor failed, just
        // silently not applicable (already submitted / no longer active).
        if (! $user->is_active || $user->role !== 'user' || $user->survey?->submitted_at) {
            return;
        }

        // Deliberately not caught here: letting this throw is what makes
        // Bus::batch() count it in failed_jobs, which is what the Manage
        // Users progress display reads to show the admin how many actually
        // failed vs sent (see UserManagementController::surveyNotificationStatus).
        $user->sendSurveyReminderNotification();
        $user->forceFill(['survey_reminder_sent_at' => now()])->save();
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Mass survey notification failed to send.', [
            'user_id' => $this->userId,
            'error' => $e->getMessage(),
        ]);
    }
}
