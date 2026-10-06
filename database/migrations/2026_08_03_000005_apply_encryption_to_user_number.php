<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Widen for ciphertext - Crypt::encryptString() output is far
        // longer than a GCash number ever needs a plain varchar for.
        // Raw SQL rather than Schema::table(...)->change() so this
        // doesn't need doctrine/dbal installed.
        DB::statement('ALTER TABLE user_number MODIFY number TEXT NOT NULL');

        // Encrypt every existing row in place. Via the query builder
        // (DB::table), not Eloquent - UserNumber is about to be given an
        // 'encrypted' cast, and running this through Eloquent afterwards
        // would try to decrypt values that aren't ciphertext yet.
        DB::table('user_number')->orderBy('id')->get()->each(function ($row) {
            DB::table('user_number')->where('id', $row->id)->update([
                'number' => Crypt::encryptString($row->number),
            ]);
        });
    }

    public function down(): void
    {
        DB::table('user_number')->orderBy('id')->get()->each(function ($row) {
            DB::table('user_number')->where('id', $row->id)->update([
                'number' => Crypt::decryptString($row->number),
            ]);
        });

        DB::statement('ALTER TABLE user_number MODIFY number VARCHAR(255) NOT NULL');
    }
};
