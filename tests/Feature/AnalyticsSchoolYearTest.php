<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AnalyticsSchoolYearTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null, 'session.driver' => 'array', 'cache.default' => 'array']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            foreach (['name', 'email', 'email_hash', 'password', 'role'] as $column) $table->text($column);
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        foreach ([
            '2026_07_16_000002_create_academic_programs_table.php',
            '2026_07_16_000003_create_school_years_table.php',
            '2026_07_16_000005_create_graduate_tracer_survey_table.php',
            '2026_07_16_000006_create_survey_child_tables.php',
            '2026_07_21_000001_add_first_job_related_to_course_to_employment_data.php',
        ] as $migration) (require database_path('migrations/'.$migration))->up();
        DB::table('academic_programs')->insert(['id' => 1, 'name' => 'Computer Science', 'code' => 'BSCS']);
    }

    private function account(string $role = 'user'): User
    {
        return User::create(['name' => 'Analytics Test', 'email' => uniqid().'@example.test',
            'password' => 'unused', 'role' => $role, 'is_active' => true]);
    }

    private function survey(?int $year, bool $submitted = true, string $role = 'user'): int
    {
        return DB::table('graduate_tracer_survey')->insertGetId(['user_id' => $this->account($role)->id,
            'academic_program_id' => 1, 'school_year_id' => $year, 'submitted_at' => $submitted ? now() : null]);
    }

    public function test_school_year_tile_and_csv_count_only_completed_graduate_surveys(): void
    {
        $older = DB::table('school_years')->insertGetId(['label' => '2024-2025']);
        $newer = DB::table('school_years')->insertGetId(['label' => '2025-2026']);
        $survey = $this->survey($older);
        $this->survey($older);
        $this->survey($newer);
        $this->survey(null);
        $this->survey($older, false);
        $this->survey($older, true, 'faculty');
        $this->survey($older, true, 'admin');
        DB::table('general_information')->insert(['survey_id' => $survey, 'name' => 'Example',
            'permanent_address' => 'Example', 'mobile_number' => '09171234567', 'civil_status' => 'single',
            'sex' => 'male', 'birthday' => '2000-01-01', 'region_of_origin' => 'Caraga', 'residence_location' => 'city']);

        foreach (['admin', 'faculty'] as $role) {
            $response = $this->actingAs($this->account($role))->get(route('admin.analytics'));
            $response->assertOk()->assertSee('Graduates by School Year')->assertSee('2025-2026')
                ->assertSee('2024-2025')->assertSee('Not specified')->assertViewHas('totalRespondents', 4);
            $chart = collect($response->viewData('charts'))->firstWhere('key', 'graduates_by_school_year');
            $this->assertSame(['2025-2026', '2024-2025', 'Not specified'], $chart['labels']);
            $this->assertSame([1, 2, 1], $chart['counts']);
            $this->assertSame('A', $chart['section']);

            // The same rows appear in the preview modal and downloadable CSV.
            $preview = substr($response->getContent(), strpos($response->getContent(), '<div class="modal fade" id="csvPreviewModal"'));
            foreach ($chart['labels'] as $label) $this->assertStringContainsString($label, $preview);
            $export = $this->get(route('admin.analytics.export'))->assertOk();
            $stream = fopen('php://temp', 'w+');
            fwrite($stream, $export->streamedContent());
            rewind($stream);
            $rows = [];
            while (($row = fgetcsv($stream)) !== false) $rows[] = $row;
            fclose($stream);
            $this->assertSame(['Question', 'Answer', 'Count'], $rows[0]);
            $this->assertSame([
                ['Graduates by School Year', '2025-2026', '1'],
                ['Graduates by School Year', '2024-2025', '2'],
                ['Graduates by School Year', 'Not specified', '1'],
            ], array_values(array_filter($rows, fn ($row) => $row[0] === 'Graduates by School Year')));
            $this->assertContains(['Sex', 'Male', '1'], $rows);
        }
    }

    public function test_drafts_alone_do_not_populate_school_year_counts_or_export_rows(): void
    {
        $year = DB::table('school_years')->insertGetId(['label' => '2025-2026']);
        $this->survey($year, false);
        $this->actingAs($this->account('admin'))->get(route('admin.analytics'))->assertOk()
            ->assertViewHas('charts', [])->assertViewHas('totalRespondents', 0)
            ->assertSee('No submitted surveys yet');
        $export = $this->get(route('admin.analytics.export'))->assertOk();
        $this->assertSame("Question,Answer,Count\n", $export->streamedContent());
    }
}
