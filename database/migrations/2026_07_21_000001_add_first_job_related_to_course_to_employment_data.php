<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // GTS Q24: "Is your first job related to the course you took up in
        // college?" - this gates whether Q25 (reasons for accepting the
        // job) or Q26 (reasons for changing job) is the relevant one to
        // show/collect, alongside Q22 (is_first_job). It was missing from
        // the original schema even though the questionnaire asks it.
        Schema::table('employment_data', function (Blueprint $table) {
            $table->boolean('first_job_related_to_course')->nullable()->after('is_first_job');
        });
    }

    public function down(): void
    {
        Schema::table('employment_data', function (Blueprint $table) {
            $table->dropColumn('first_job_related_to_course');
        });
    }
};
