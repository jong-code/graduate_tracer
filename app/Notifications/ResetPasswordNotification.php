<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;

/**
 * See VerifyEmailNotification's docblock - same idea, applied to the
 * password-reset email instead.
 */
class ResetPasswordNotification extends BaseResetPassword
{
    public function __construct(string $token, public string $mailer)
    {
        parent::__construct($token);
    }
}
