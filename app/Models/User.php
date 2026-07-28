<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role',
        'consent_given', 'consent_given_at', 'identity_verified_at', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'consent_given' => 'boolean',
        'consent_given_at' => 'datetime',
        'identity_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

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
