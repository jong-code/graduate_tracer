<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Crypt;

/**
 * Same idea as Laravel's built-in 'encrypted' cast (Crypt::encryptString /
 * Crypt::decryptString under the hood) but round-trips as a float rather
 * than a string, since longitude/latitude are used as numbers (map
 * plotting, bounds calculations) wherever they're read.
 */
class EncryptedFloat implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        return (float) Crypt::decryptString($value);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        return Crypt::encryptString((string) $value);
    }
}
