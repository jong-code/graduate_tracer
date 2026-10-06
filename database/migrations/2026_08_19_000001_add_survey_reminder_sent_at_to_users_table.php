<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Set the first (and only) time the "you still haven't
            // submitted your survey" reminder goes out to this user - see
            // App\Console\Commands\SendSurveyReminders. Keeps the daily
            // scheduled command from re-emailing the same graduate every
            // single day once they cross the 3-day mark.
            $table->timestamp('survey_reminder_sent_at')->nullable()->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('survey_reminder_sent_at');
        });
    }
};
