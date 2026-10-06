<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Widen every column that will hold ciphertext instead of plain
        // values - Crypt::encryptString() output is far longer than the
        // original varchar/decimal columns can hold (a short name or a
        // single coordinate both encrypt to a ~200+ character payload).
        // Raw SQL rather than Schema::table(...)->change() so this doesn't
        // need doctrine/dbal installed.
        DB::statement('ALTER TABLE users MODIFY name TEXT NOT NULL');
        DB::statement('ALTER TABLE users MODIFY email TEXT NOT NULL');
        DB::statement('ALTER TABLE general_information MODIFY name TEXT NOT NULL');
        DB::statement('ALTER TABLE general_information MODIFY email TEXT NULL');
        DB::statement('ALTER TABLE general_information MODIFY mobile_number TEXT NOT NULL');
        DB::statement('ALTER TABLE location MODIFY longitude TEXT NOT NULL');
        DB::statement('ALTER TABLE location MODIFY latitude TEXT NOT NULL');

        // 2. users.email is encrypted (non-deterministic - the same
        // plaintext produces different ciphertext every time), so it can
        // no longer be matched with `WHERE email = ?` for login or
        // uniqueness checks. email_hash is a deterministic SHA-256 blind
        // index of the lowercased email, used for exact-match lookups
        // instead - see User::booted() and AuthController::login().
        if (! Schema::hasColumn('users', 'email_hash')) {
            Schema::table('users', function ($table) {
                $table->char('email_hash', 64)->nullable()->after('email');
            });
        }

        // Drop the old plain-email unique index - it can never actually
        // enforce uniqueness once email is encrypted (two encryptions of
        // the same address never produce the same ciphertext), and its
        // replacement (a unique index on email_hash) is added after the
        // backfill below.
        try {
            Schema::table('users', function ($table) {
                $table->dropUnique(['email']);
            });
        } catch (\Throwable $e) {
            // Index didn't exist under the default Laravel name - nothing
            // to drop, safe to continue.
        }

        // 3. Encrypt every existing plaintext row in place. Done via the
        // query builder (DB::table), not Eloquent - the models are about
        // to be given 'encrypted' casts, and running this through Eloquent
        // afterwards would try to decrypt values that aren't ciphertext
        // yet, throwing on every row. User::hashEmail() below is just a
        // static string helper though (no Eloquent/DB access), so it's
        // safe to call here despite that.
        DB::table('users')->orderBy('id')->get()->each(function ($user) {
            DB::table('users')->where('id', $user->id)->update([
                'name' => Crypt::encryptString($user->name),
                'email' => Crypt::encryptString($user->email),
                'email_hash' => \App\Models\User::hashEmail($user->email),
            ]);
        });

        DB::table('general_information')->orderBy('id')->get()->each(function ($gi) {
            DB::table('general_information')->where('id', $gi->id)->update([
                'name' => Crypt::encryptString($gi->name),
                'email' => $gi->email !== null ? Crypt::encryptString($gi->email) : null,
                'mobile_number' => Crypt::encryptString($gi->mobile_number),
            ]);
        });

        DB::table('location')->orderBy('id')->get()->each(function ($loc) {
            DB::table('location')->where('id', $loc->id)->update([
                'longitude' => Crypt::encryptString((string) $loc->longitude),
                'latitude' => Crypt::encryptString((string) $loc->latitude),
            ]);
        });

        // 4. Now that every row has a real hash, enforce uniqueness on it.
        Schema::table('users', function ($table) {
            $table->unique('email_hash');
        });
    }

    public function down(): void
    {
        Schema::table('users', function ($table) {
            $table->dropUnique(['email_hash']);
        });

        DB::table('users')->orderBy('id')->get()->each(function ($user) {
            DB::table('users')->where('id', $user->id)->update([
                'name' => Crypt::decryptString($user->name),
                'email' => Crypt::decryptString($user->email),
            ]);
        });

        DB::table('general_information')->orderBy('id')->get()->each(function ($gi) {
            DB::table('general_information')->where('id', $gi->id)->update([
                'name' => Crypt::decryptString($gi->name),
                'email' => $gi->email !== null ? Crypt::decryptString($gi->email) : null,
                'mobile_number' => Crypt::decryptString($gi->mobile_number),
            ]);
        });

        DB::table('location')->orderBy('id')->get()->each(function ($loc) {
            DB::table('location')->where('id', $loc->id)->update([
                'longitude' => Crypt::decryptString($loc->longitude),
                'latitude' => Crypt::decryptString($loc->latitude),
            ]);
        });

        Schema::table('users', function ($table) {
            $table->dropColumn('email_hash');
        });

        DB::statement('ALTER TABLE users MODIFY name VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE general_information MODIFY name VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE general_information MODIFY email VARCHAR(255) NULL');
        DB::statement('ALTER TABLE general_information MODIFY mobile_number VARCHAR(50) NOT NULL');
        DB::statement('ALTER TABLE location MODIFY longitude DECIMAL(10,7) NOT NULL');
        DB::statement('ALTER TABLE location MODIFY latitude DECIMAL(10,7) NOT NULL');

        Schema::table('users', function ($table) {
            $table->unique('email');
        });
    }
};
