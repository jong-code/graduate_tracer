<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A standalone record of "this graduate registered under this
        // program, this school year" - captured once at registration time
        // (see AuthController::register()). This is intentionally separate
        // from graduate_tracer_survey, which already carries its own
        // academic_program_id/school_year_id for the survey itself; that
        // wasn't changed, so nothing that already reads $survey->academicProgram
        // or $survey->schoolYear needed to be touched.
        Schema::create('graduate_program', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('academic_program_id')->constrained('academic_programs')->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('graduate_program');
    }
};
