<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('address', function (Blueprint $table) {
            $table->id();
            $table->foreignId('general_information_id')->unique()->constrained('general_information')->cascadeOnDelete();
            $table->string('current_street')->nullable();
            $table->string('current_barangay')->nullable();
            $table->string('current_municipality')->nullable();
            $table->string('current_province')->nullable();
            $table->string('permanent_street')->nullable();
            $table->string('permanent_barangay')->nullable();
            $table->string('permanent_municipality')->nullable();
            $table->string('permanent_province')->nullable();
        });

        // Superseded by the address table above - the single free-text
        // permanent_address field is now Street/Barangay/Municipality/
        // Province broken out, and residence_city_municipality/province
        // (which used to double as "current address" via the region-of-
        // origin cascade) are now the address table's current_* columns
        // instead. NOTE: this drops existing data in these columns for any
        // already-submitted surveys - back up the database first if that
        // data matters before running this migration on a live dataset.
        Schema::table('general_information', function (Blueprint $table) {
            $table->dropColumn(['permanent_address', 'province', 'residence_city_municipality', 'residence_location']);
        });
    }

    public function down(): void
    {
        Schema::table('general_information', function (Blueprint $table) {
            $table->string('permanent_address')->nullable();
            $table->string('province', 100)->nullable();
            $table->string('residence_city_municipality')->nullable();
            $table->string('residence_location', 20)->nullable();
        });

        Schema::dropIfExists('address');
    }
};
