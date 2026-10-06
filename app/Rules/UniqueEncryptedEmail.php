<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * users.email is encrypted (see User::$casts), so the usual
 * unique:users,email rule can't see through the ciphertext to detect a
 * duplicate - this checks the deterministic email_hash blind index
 * instead (via User::findByEmail()), the same way AuthController@login
 * and friends already look accounts up.
 *
 * Covers the plain "is this email already taken" case used by
 * ProfileController and UserManagementController. AuthController@register
 * has its own inline closure instead of this rule - registration needs to
 * distinguish an already-verified account (reject) from an
 * already-registered-but-unverified one (different message, offers a
 * resend), which doesn't fit this rule's single pass/fail shape.
 */
class UniqueEncryptedEmail implements ValidationRule
{
    public function __construct(private readonly ?int $ignoreUserId = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $existing = User::findByEmail($value);

        if ($existing && $existing->id !== $this->ignoreUserId) {
            $fail('This email is already registered.');
        }
    }
}
