<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Optional part of the GTS questionnaire (end of Section D): the
        // graduate may voluntarily list other alumni from the same
        // institution (name, address, contact number) to help the
        // institution track down more respondents. Entirely optional, so
        // every column is nullable and the whole set of rows can be empty.
        Schema::create('other_graduates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained('graduate_tracer_survey')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->string('contact_number', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('other_graduates');
    }
};
