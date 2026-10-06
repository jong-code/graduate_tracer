<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // academic_programs.college was a free-text legacy field from
        // before the department table existed. Every program is now
        // linked to a real Department via department_id, so this column
        // is redundant and no longer read anywhere in the app.
        Schema::table('academic_programs', function (Blueprint $table) {
            $table->dropColumn('college');
        });
    }

    public function down(): void
    {
        Schema::table('academic_programs', function (Blueprint $table) {
            $table->string('college')->nullable();
        });
    }
};
