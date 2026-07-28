<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('graduate_tracer_survey', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('academic_program_id')->constrained('academic_programs');
            $table->foreignId('school_year_id')->nullable()->constrained('school_years');
            $table->string('institution_code')->nullable();
            $table->string('control_code')->nullable();
            $table->timestamp('submitted_at')->nullable()->useCurrentOnUpdate();
            $table->string('advance_study_reason')->nullable();
            $table->string('advance_study_reason_other')->nullable();
            // No created_at/updated_at - this table intentionally doesn't
            // track them (see GraduateTracerSurvey::$timestamps = false).
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('graduate_tracer_survey');
    }
};
