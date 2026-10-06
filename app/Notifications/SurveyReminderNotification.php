<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "You registered but haven't submitted your Graduate Tracer Survey yet"
 * reminder, sent once per user by App\Console\Commands\SendSurveyReminders.
 *
 * Carries a public $mailer property (see VerifyEmailNotification's
 * docblock for why) so ResilientMailerService can route it through
 * 'brevo' or the fallback 'smtp' mailer the same way every other
 * outbound email in the app does.
 */
class SurveyReminderNotification extends Notification
{
    public function __construct(public string $mailer)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('login');

        return (new MailMessage)
            ->mailer($this->mailer)
            ->subject('Reminder: Complete Your NEMSU Graduate Tracer Survey')
            ->greeting('Dear NEMSU Graduate,')
            ->line('Greetings from North Eastern Mindanao State University (NEMSU)!')
            ->line('Thank you for registering for the NEMSU Graduate Tracer Survey.')
            ->line('Our records indicate that you have successfully registered; however, your Graduate Tracer Survey has not yet been completed or submitted. We kindly encourage you to continue and complete the survey using the link below:')
            ->line('Graduate Tracer Survey: ' . $url)
            ->action('Go to Graduate Tracer Survey', $url)
            ->line('Your responses are very important to the University. The information you provide will help us better understand the employment experiences, career paths, and professional development of our graduates. The results will also serve as valuable input in improving our academic programs and student services.')
            ->line('We would greatly appreciate a few minutes of your time to complete the survey.')
            ->line('If you have already completed the survey upon receiving this message, please disregard this reminder.')
            ->line('Thank you for your continued support and for being part of the NEMSU alumni community.')
            ->salutation("Warm regards,\nNEMSU Graduate Tracer Survey Team\nNorth Eastern Mindanao State University");
    }
}
