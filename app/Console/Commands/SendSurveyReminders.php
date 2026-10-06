<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Emails every 'user'-role (graduate) account that:
 *  - registered at least 3 days ago,
 *  - still has no row in graduate_tracer_survey (a row only ever gets
 *    created at final submission - see GraduateTracerController@submit -
 *    so "no row" reliably means "never submitted", not "still drafting":
 *    in-progress answers live in the browser's localStorage instead), and
 *  - hasn't already been sent this reminder (survey_reminder_sent_at).
 *
 * One-time reminder, not a daily nag: survey_reminder_sent_at is stamped
 * right after a successful send so the next run's whereNull() excludes
 * them. Intended to run once a day via the scheduler - see the
 * Schedule::command(...) entry in routes/console.php - which on shared
 * hosting (e.g. Hostinger) needs a single hPanel cron job hitting
 * `php artisan schedule:run` every minute; see this command's own
 * output/the deployment notes for the exact line to add.
 */
class SendSurveyReminders extends Command
{
    protected $signature = 'gts:send-survey-reminders';

    protected $description = 'Email graduates who registered 3+ days ago but have not submitted their tracer survey yet';

    public function handle(): int
    {
        $users = User::query()
            ->where('role', 'user')
            ->where('is_active', true)
            ->whereNull('survey_reminder_sent_at')
            ->whereDoesntHave('survey')
            ->where('created_at', '<=', now()->subDays(3))
            ->get();

        if ($users->isEmpty()) {
            $this->info('No graduates are due a survey reminder right now.');

            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            try {
                $user->sendSurveyReminderNotification();
                $user->forceFill(['survey_reminder_sent_at' => now()])->save();
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('Survey reminder email failed to send.', [
                    'user_id' => $user->getKey(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Survey reminders sent: {$sent}." . ($failed ? " Failed: {$failed} (see log)." : ''));

        return self::SUCCESS;
    }
}
