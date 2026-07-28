<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Section A
        Schema::create('general_information', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->unique()->constrained('graduate_tracer_survey')->cascadeOnDelete();
            $table->string('name');
            $table->string('permanent_address');
            $table->string('telephone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('mobile_number', 50);
            $table->string('civil_status', 20);
            $table->string('sex', 10);
            $table->date('birthday');
            $table->string('region_of_origin', 50);
            $table->string('province', 100)->nullable();
            $table->string('residence_location', 20);
        });

        // Section B
        Schema::create('educational_background', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained('graduate_tracer_survey')->cascadeOnDelete();
            $table->string('degree');
            $table->string('college_university');
            $table->string('year_graduated', 10);
            $table->string('honors')->nullable();
        });

        Schema::create('professional_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained('graduate_tracer_survey')->cascadeOnDelete();
            $table->string('exam_name', 100);
            $table->date('date_taken')->nullable();
            $table->string('rating')->nullable();
        });

        Schema::create('course_reasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained('graduate_tracer_survey')->cascadeOnDelete();
            $table->string('level'); // 'undergraduate' or 'graduate'
            $table->string('reason_key');
            $table->string('other_text')->nullable();
        });

        // Section C
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained('graduate_tracer_survey')->cascadeOnDelete();
            $table->string('title');
            $table->string('duration_credits')->nullable();
            $table->string('institution')->nullable();
        });

        // Section D
        Schema::create('employment_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->unique()->constrained('graduate_tracer_survey')->cascadeOnDelete();
            $table->string('employment_status'); // yes / no / never_employed
            $table->string('present_employment_status')->nullable();
            $table->text('self_employed_skills')->nullable();
            $table->string('present_occupation')->nullable();
            $table->string('business_line')->nullable();
            $table->string('place_of_work', 100)->nullable();
            $table->string('is_first_job', 10)->nullable();
            $table->string('first_job_duration')->nullable();
            $table->string('how_found_first_job')->nullable();
            $table->string('time_to_land_first_job')->nullable();
            $table->string('job_level_first')->nullable();
            $table->string('job_level_current')->nullable();
            $table->string('initial_gross_monthly_earning')->nullable();
            $table->string('curriculum_relevant')->nullable();
            $table->text('curriculum_suggestions')->nullable();
        });

        Schema::create('not_employed_reasons_17', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employment_data_id')->constrained('employment_data')->cascadeOnDelete();
            $table->string('reason_key');
            $table->string('other_text')->nullable();
        });

        Schema::create('job_reasons_23to25', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employment_data_id')->constrained('employment_data')->cascadeOnDelete();
            $table->string('reason_type'); // staying / accepting / changing
            $table->string('reason_key');
            $table->integer('number'); // literal GTS question number - see JobReason::QUESTION_NUMBERS
            $table->string('other_text')->nullable();
        });

        Schema::create('competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employment_data_id')->constrained('employment_data')->cascadeOnDelete();
            $table->string('competency_key');
            $table->string('other_text')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competencies');
        Schema::dropIfExists('job_reasons_23to25');
        Schema::dropIfExists('not_employed_reasons_17');
        Schema::dropIfExists('employment_data');
        Schema::dropIfExists('trainings');
        Schema::dropIfExists('course_reasons');
        Schema::dropIfExists('professional_exams');
        Schema::dropIfExists('educational_background');
        Schema::dropIfExists('general_information');
    }
};
