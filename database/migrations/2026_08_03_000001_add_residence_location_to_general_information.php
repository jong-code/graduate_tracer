<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Section A: "Location of Residence" - city vs municipality. This
        // column previously existed (added in the 2026_07_20 patch) but was
        // dropped by 2026_07_28_000003_create_address_table when the wide
        // permanent_address field was split into the address table. It's
        // re-added here since the wizard now collects it again as its own
        // checkbox question (City / Municipality), independent of the
        // address table - it answers "is this residence a city or a
        // municipality?", not the address itself.
        Schema::table('general_information', function (Blueprint $table) {
            $table->string('residence_location', 20)->nullable()->after('region_of_origin');
        });
    }

    public function down(): void
    {
        Schema::table('general_information', function (Blueprint $table) {
            $table->dropColumn('residence_location');
        });
    }
};
