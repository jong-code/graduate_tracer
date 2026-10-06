<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FacultyDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Isolated schema: production encryption migrations contain MySQL-specific SQL.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null, 'cache.default' => 'array', 'session.driver' => 'array']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->text('email');
            $table->string('email_hash')->unique();
            $table->string('password');
            $table->string('role');
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('academic_programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('graduate_tracer_survey', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('academic_program_id');
            $table->timestamp('submitted_at')->nullable();
        });
        Schema::create('employment_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('survey_id');
            $table->string('employment_status');
            $table->boolean('curriculum_relevant')->nullable();
        });
    }

    private function account(string $role): User
    {
        return User::create(['name' => 'Test '.$role, 'email' => uniqid().'@example.test',
            'password' => Hash::make('TestPassword123!'), 'role' => $role, 'is_active' => true]);
    }

    private function survey(User $user, int $program, bool $submitted = true): int
    {
        $id = DB::table('graduate_tracer_survey')->insertGetId(['user_id' => $user->id,
            'academic_program_id' => $program, 'submitted_at' => $submitted ? now() : null]);
        DB::table('employment_data')->insert(['survey_id' => $id, 'employment_status' => 'yes',
            'curriculum_relevant' => true]);
        return $id;
    }

    public function test_faculty_dashboard_has_tools_and_a_helpful_empty_state(): void
    {
        $response = $this->actingAs($this->account('faculty'))->get(route('faculty.dashboard'));
        $response->assertOk()->assertSee('Faculty workspace')->assertSee('No completed surveys yet')
            ->assertSee('Analytics')->assertSee('Survey Templates')->assertSee('Integrations')->assertSee('View Map');
        foreach (['admin.analytics', 'admin.templates', 'admin.integrations', 'admin.map'] as $route) {
            $response->assertSee(route($route), false);
        }
        $response->assertDontSee(route('admin.users.index'), false)->assertDontSee('NaN')->assertDontSee('Infinity');
    }

    public function test_reports_exclude_drafts_and_surveys_of_accounts_promoted_to_staff(): void
    {
        $faculty = $this->account('faculty');
        $graduate = $this->account('user');
        $promoted = $this->account('user');
        $program = DB::table('academic_programs')->insertGetId(['name' => 'Computer Science']);
        $this->survey($graduate, $program);
        $this->survey($graduate, $program, false);
        $this->survey($promoted, $program);
        $this->survey($faculty, $program);
        $this->survey($this->account('admin'), $program);
        $promoted->update(['role' => 'faculty']);

        $this->actingAs($faculty)->get(route('faculty.dashboard'))->assertOk()
            ->assertViewHas('totalSubmitted', 1)
            ->assertViewHas('employmentBreakdown', fn ($rows) => $rows->sum('total') === 1)
            ->assertViewHas('byProgram', fn ($rows) => $rows->sum('total') === 1)
            ->assertViewHas('curriculumRelevance', fn ($rows) => $rows->sum('total') === 1)
            ->assertSee('Computer Science')->assertSee('1 of 1 employment responses');
    }

    public function test_missing_relevance_is_not_counted_as_a_negative_response(): void
    {
        $program = DB::table('academic_programs')->insertGetId(['name' => 'Information Technology']);
        $survey = $this->survey($this->account('user'), $program);
        DB::table('employment_data')->where('survey_id', $survey)->update(['curriculum_relevant' => null]);
        $this->actingAs($this->account('faculty'))->get(route('faculty.dashboard'))->assertOk()
            ->assertSee('No curriculum relevance responses available yet.');
    }

    public function test_faculty_cannot_access_user_management(): void
    {
        $this->actingAs($this->account('faculty'))->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_inactive_faculty_cannot_access_dashboard_or_tools(): void
    {
        $faculty = $this->account('faculty');
        $faculty->update(['is_active' => false]);
        $this->actingAs($faculty)->get(route('faculty.dashboard'))->assertForbidden();
        $this->actingAs($faculty)->get(route('admin.analytics'))->assertForbidden();
    }

    public function test_promoted_accounts_surveys_cannot_be_previewed_or_exported(): void
    {
        $program = DB::table('academic_programs')->insertGetId(['name' => 'Computer Science']);
        $promoted = $this->account('user');
        $survey = $this->survey($promoted, $program);
        $promoted->update(['role' => 'admin']);
        $this->actingAs($this->account('faculty'));
        $this->get(route('admin.templates.survey-preview', $survey))->assertNotFound();
        $this->get(route('admin.templates.survey-export', $survey))->assertNotFound();
    }

    public function test_graduate_cannot_access_faculty_dashboard_or_tools(): void
    {
        $user = $this->account('user');
        foreach (['faculty.dashboard', 'admin.analytics', 'admin.templates', 'admin.integrations', 'admin.map'] as $route) {
            $this->actingAs($user)->get(route($route))->assertForbidden();
        }
    }
}
