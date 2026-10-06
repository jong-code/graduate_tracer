<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\SurveyReminderNotification;
use App\Notifications\VerifyEmailNotification;
use App\Services\ResilientMailerService;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable, MustVerifyEmailTrait;

    protected $fillable = [
        'name', 'last_name', 'middle_name', 'email', 'password', 'role',
        'consent_given', 'consent_given_at', 'identity_verified_at', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'name' => 'encrypted',
        'last_name' => 'encrypted',
        'middle_name' => 'encrypted',
        'email' => 'encrypted',
        'email_verified_at' => 'datetime',
        'survey_reminder_sent_at' => 'datetime',
        'consent_given' => 'boolean',
        'consent_given_at' => 'datetime',
        'identity_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * The one place the email_hash blind-index formula is actually
     * computed - everywhere else (booted() below, findByEmail(),
     * RoleSeeder, the encryption migration) calls this instead of
     * inlining hash('sha256', strtolower(...)) themselves, so the formula
     * only ever needs to change in one place.
     */
    public static function hashEmail(string $email): string
    {
        return hash('sha256', strtolower($email));
    }

    /**
     * email is encrypted (see $casts above), so it can no longer be looked
     * up with a plain `WHERE email = ?` - the same address encrypts to a
     * different ciphertext every time. email_hash is a deterministic
     * SHA-256 "blind index" of the lowercased address, kept in sync here
     * automatically, and is what login/registration/seeding actually
     * query against instead (see AuthController and RoleSeeder).
     * Deliberately left out of $fillable - it's derived, never set
     * directly.
     */
    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if ($user->isDirty('email')) {
                $user->email_hash = static::hashEmail($user->email);

                // A change to the email address invalidates any prior
                // verification - the existing email_verified_at timestamp
                // only ever attested to the *previous* address. Without
                // this, editing the profile email would be a way to carry
                // a verified flag over to an address that was never
                // actually confirmed. Only applies to existing rows -
                // a brand-new user is already unverified by default.
                if ($user->exists) {
                    $user->email_verified_at = null;
                }
            }
        });
    }

    public static function findByEmail(string $email): ?self
    {
        return static::where('email_hash', static::hashEmail($email))->first();
    }

    /**
     * "Last Name, Name Middle Name" - the display order requested for the
     * admin Users/Integrations/Survey Templates pages. Falls back
     * gracefully for legacy accounts that predate the last_name/middle_name
     * columns (see needsNameUpdate() below, which flags exactly those
     * accounts so an admin can fill them in).
     */
    public function displayName(): string
    {
        $given = trim($this->name.' '.($this->middle_name ?? ''));

        if (blank($this->last_name)) {
            return $given !== '' ? $given : $this->name;
        }

        return trim($this->last_name.', '.$given);
    }

    /**
     * True for accounts created before the Last Name/Middle Name split
     * (or otherwise missing a last name) - drives the red-dot indicator
     * on the admin Users page so an admin knows which records still need
     * to be completed.
     */
    public function needsNameUpdate(): bool
    {
        return blank($this->last_name);
    }

    /**
     * Sort key for the "sorting in PHP after decryption" approach used on
     * every admin page that lists users (name/last_name are encrypted, so
     * they can't be sorted at the DB level - see UserManagementController,
     * SystemSettingsController). Sorts by Last Name; accounts with no last
     * name on file sort first under A-Z (last, under Z-A) rather than
     * erroring or being silently dropped.
     */
    public function nameSortKey(): string
    {
        return mb_strtoupper(trim($this->last_name ?? ''));
    }

    /** Match decrypted name parts and email, with name words in any order. */
    public function matchesNameSearch(string $search): bool
    {
        $haystack = mb_strtolower($this->displayName().' '.$this->email);
        $words = preg_split('/[\s,]+/u', mb_strtolower(trim($search)), -1, PREG_SPLIT_NO_EMPTY);

        foreach ($words as $word) {
            if (! str_contains($haystack, $word)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Overrides MustVerifyEmailTrait's default (which just does
     * $this->notify(new \Illuminate\Auth\Notifications\VerifyEmail)) so
     * this goes through ResilientMailerService's Brevo-first, Gmail-backup
     * routing instead of always using the app's single default mailer.
     */
    public function sendEmailVerificationNotification(): void
    {
        app(ResilientMailerService::class)->send(
            $this,
            fn (string $mailer) => new VerifyEmailNotification($mailer)
        );
    }

    /**
     * Overrides CanResetPassword's default (from the base Authenticatable
     * class) for the same reason as sendEmailVerificationNotification()
     * above.
     */
    public function sendPasswordResetNotification($token): void
    {
        app(ResilientMailerService::class)->send(
            $this,
            fn (string $mailer) => new ResetPasswordNotification($token, $mailer)
        );
    }

    /**
     * "You still haven't submitted your Graduate Tracer Survey" reminder -
     * see App\Console\Commands\SendSurveyReminders, which sends this once
     * (survey_reminder_sent_at tracks that) to any 'user'-role account
     * that registered 3+ days ago and has no submitted survey yet.
     */
    public function sendSurveyReminderNotification(): void
    {
        app(ResilientMailerService::class)->send(
            $this,
            fn (string $mailer) => new SurveyReminderNotification($mailer)
        );
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isFaculty(): bool
    {
        return $this->role === 'faculty';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Every "Graduate/Alumni" role user has at most one tracer survey.
     */
    public function survey()
    {
        return $this->hasOne(GraduateTracerSurvey::class);
    }

    public function graduatePrograms()
    {
        return $this->hasMany(GraduateProgram::class);
    }

    /**
     * GCash number submitted for the free-load reward, if any.
     */
    public function userNumber()
    {
        return $this->hasOne(UserNumber::class);
    }

    public function dashboardRoute(): string
    {
        return match ($this->role) {
            'admin' => 'admin.dashboard',
            'faculty' => 'faculty.dashboard',
            default => 'user.dashboard',
        };
    }
}
