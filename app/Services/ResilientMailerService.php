<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Routes every verification/reset email through Brevo first, and only
 * touches the original Gmail SMTP setup as a fallback - either because
 * today's Brevo quota is used up, or because a send through Brevo actually
 * failed outright (wrong credentials, Brevo having an outage, etc.).
 *
 * Two mailers are expected in config/mail.php's 'mailers' array:
 * - 'brevo'  - the new Brevo SMTP transport (primary)
 * - 'smtp'   - the existing Gmail SMTP transport (backup, unchanged)
 *
 * Usage (see User::sendEmailVerificationNotification() /
 * sendPasswordResetNotification()): callers pass a closure that builds
 * their notification given a mailer name, rather than a ready-made
 * notification - that's what lets this class retry with a *different*
 * notification instance (carrying the fallback mailer name) after a
 * failed attempt, instead of resending the exact same object twice.
 */
class ResilientMailerService
{
    private const PRIMARY_MAILER = 'brevo';
    private const FALLBACK_MAILER = 'smtp';

    /**
     * @param  User  $notifiable
     * @param  callable(string $mailer): \Illuminate\Notifications\Notification  $makeNotification
     */
    public function send(User $notifiable, callable $makeNotification): void
    {
        $mailer = $this->currentMailer();

        try {
            $notifiable->notify($makeNotification($mailer));

            if ($mailer === self::PRIMARY_MAILER) {
                $this->recordPrimarySend();
            }
        } catch (TransportExceptionInterface $e) {
            if ($mailer !== self::PRIMARY_MAILER) {
                // Already on the fallback mailer - nothing left to fail
                // over to, so let the caller see the real error instead
                // of silently swallowing a genuine delivery failure.
                throw $e;
            }

            Log::warning('Brevo mail send failed - falling back to backup SMTP.', [
                'notifiable_id' => $notifiable->getKey(),
                'error' => $e->getMessage(),
            ]);

            $notifiable->notify($makeNotification(self::FALLBACK_MAILER));
        }
    }

    /**
     * 'brevo' while today's send count is under the configured daily
     * limit, 'smtp' (the original Gmail setup) once it's been reached -
     * checked proactively so a day that's already exhausted its quota
     * doesn't spend time on a Brevo attempt that Brevo would just reject.
     */
    private function currentMailer(): string
    {
        return $this->primarySentToday() < $this->dailyLimit() ? self::PRIMARY_MAILER : self::FALLBACK_MAILER;
    }

    private function dailyLimit(): int
    {
        return (int) config('mail.brevo_daily_limit', 300);
    }

    private function primarySentToday(): int
    {
        return (int) Cache::get($this->cacheKey(), 0);
    }

    private function recordPrimarySend(): void
    {
        $key = $this->cacheKey();

        // Cache::add() only writes if the key doesn't exist yet, so two
        // requests landing at the same moment on the first send of the
        // day can't both reset the counter to 0 out from under each
        // other - increment() below is then always working off the same
        // counter regardless of which request's add() actually landed.
        Cache::add($key, 0, now()->endOfDay());
        Cache::increment($key);
    }

    private function cacheKey(): string
    {
        return 'mail:brevo:sent:' . now()->toDateString();
    }
}
