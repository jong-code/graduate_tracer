<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserDeletionTest extends TestCase
{
    public function test_user_deletion_cascades_survey_records_and_preserves_audit_history(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null, 'database.connections.sqlite.foreign_key_constraints' => true]);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            foreach (['name', 'email', 'email_hash', 'password', 'role'] as $column) $table->text($column);
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        // Exercise the real cascade declarations, omitting MySQL-only encryption migrations.
        foreach ([
            '2026_07_16_000002_create_academic_programs_table.php',
            '2026_07_16_000003_create_school_years_table.php',
            '2026_07_16_000004_create_audit_logs_table.php',
            '2026_07_16_000005_create_graduate_tracer_survey_table.php',
            '2026_07_16_000006_create_survey_child_tables.php',
            '2026_07_20_000001_add_residence_city_to_general_information.php',
            '2026_07_24_000001_create_other_graduates_table.php',
            '2026_07_24_000002_create_user_number_table.php',
            '2026_07_28_000002_create_graduate_program_table.php',
            '2026_07_28_000003_create_address_table.php',
            '2026_08_03_000003_create_location_table.php',
        ] as $migration) (require database_path('migrations/'.$migration))->up();

        $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'unused', 'role' => 'admin', 'is_active' => true]);
        $graduate = User::create(['name' => 'Graduate', 'email' => 'graduate@example.test', 'password' => 'unused', 'role' => 'user', 'is_active' => true]);
        $program = DB::table('academic_programs')->insertGetId(['name' => 'Computer Science', 'code' => 'BSCS']);
        $year = DB::table('school_years')->insertGetId(['label' => '2025-2026']);
        $survey = DB::table('graduate_tracer_survey')->insertGetId(['user_id' => $graduate->id,
            'academic_program_id' => $program, 'school_year_id' => $year, 'submitted_at' => now()]);
        $information = DB::table('general_information')->insertGetId(['survey_id' => $survey, 'name' => 'Graduate',
            'mobile_number' => '09171234567', 'civil_status' => 'single', 'sex' => 'male',
            'birthday' => '2000-01-01', 'region_of_origin' => 'Caraga']);
        DB::table('address')->insert(['general_information_id' => $information, 'current_street' => 'Example']);
        DB::table('educational_background')->insert(['survey_id' => $survey, 'degree' => 'BSCS',
            'college_university' => 'NEMSU', 'year_graduated' => '2026']);
        DB::table('professional_exams')->insert(['survey_id' => $survey, 'exam_name' => 'Example']);
        DB::table('course_reasons')->insert(['survey_id' => $survey, 'level' => 'undergraduate', 'reason_key' => 'passion']);
        DB::table('trainings')->insert(['survey_id' => $survey, 'title' => 'Example']);
        $employment = DB::table('employment_data')->insertGetId(['survey_id' => $survey, 'employment_status' => 'yes']);
        DB::table('not_employed_reasons_17')->insert(['employment_data_id' => $employment, 'reason_key' => 'advance_study']);
        DB::table('job_reasons_23to25')->insert(['employment_data_id' => $employment, 'reason_type' => 'staying',
            'reason_key' => 'special_skill', 'number' => 23]);
        DB::table('competencies')->insert(['employment_data_id' => $employment, 'competency_key' => 'communication']);
        DB::table('location')->insert(['employment_data_id' => $employment, 'longitude' => 126, 'latitude' => 8]);
        DB::table('other_graduates')->insert(['survey_id' => $survey, 'name' => 'Referral']);
        DB::table('user_number')->insert(['user_id' => $graduate->id, 'number' => '09171234567']);
        DB::table('graduate_program')->insert(['user_id' => $graduate->id, 'academic_program_id' => $program, 'school_year_id' => $year]);
        $oldLog = AuditLog::create(['user_id' => $graduate->id, 'action' => 'example']);

        $this->actingAs($admin)->delete(route('admin.users.destroy', $graduate))->assertRedirect();
        foreach (['graduate_tracer_survey', 'general_information', 'address', 'educational_background',
            'professional_exams', 'course_reasons', 'trainings', 'employment_data', 'not_employed_reasons_17',
            'job_reasons_23to25', 'competencies', 'location', 'other_graduates', 'user_number', 'graduate_program'] as $table) {
            $this->assertSame(0, DB::table($table)->count(), $table.' must cascade.');
        }
        $this->assertDatabaseMissing('users', ['id' => $graduate->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseHas('academic_programs', ['id' => $program]);
        $this->assertDatabaseHas('school_years', ['id' => $year]);
        $this->assertNull($oldLog->fresh()->user_id);
        $this->assertSame('graduate@example.test', AuditLog::where('action', 'user_deleted')->first()->meta['email']);
    }
}
