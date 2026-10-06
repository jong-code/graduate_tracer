<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;

/**
 * Identical to Laravel's built-in VerifyEmail notification (same email
 * content, same signed-URL building) - the only addition is the public
 * $mailer property. Laravel's MailChannel checks for a property named
 * exactly `mailer` on the notification (via property_exists) and, if
 * present, sends through that named mailer from config/mail.php's
 * 'mailers' array instead of the app's default one. That's the hook
 * ResilientMailerService relies on to route a given send through 'brevo'
 * or the fallback 'smtp' mailer.
 */
class VerifyEmailNotification extends BaseVerifyEmail
{
    public function __construct(public string $mailer)
    {
    }
}
