<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Captured once, at survey submission time, via the browser's
        // Geolocation API alongside the Disclosure & Consent Agreement
        // checkbox in Section D. One location per submission - hence the
        // unique employment_data_id (each survey has exactly one
        // employment_data row already, see create_survey_child_tables).
        Schema::create('location', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employment_data_id')->unique()->constrained('employment_data')->cascadeOnDelete();
            $table->decimal('longitude', 10, 7);
            $table->decimal('latitude', 10, 7);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location');
    }
};
