<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Laravel's own default migrations already create this table on a
     * fresh install, so this is guarded the same way as the
     * email_verified_at migration - safe to run whether or not it's
     * already there. Note: the 'email' column here is the plaintext
     * address the person typed into the forgot-password form, used only
     * as this table's own lookup key - it's unrelated to (and doesn't
     * need to match) the encrypted users.email column. See
     * PasswordResetController for how the two are connected.
     */
    public function up(): void
    {
        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
