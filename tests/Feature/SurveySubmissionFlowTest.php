<?php

namespace Tests\Feature;

use App\Models\EmploymentData;
use App\Models\GraduateProgram;
use App\Models\GraduateTracerSurvey;
use App\Models\User;
use App\Models\UserNumber;
use App\Services\BusinessLineCatalog;
use App\Services\CollegeSkillsCatalog;
use App\Services\OccupationCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SurveySubmissionFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null, 'session.driver' => 'array', 'cache.default' => 'array']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            foreach (['name', 'last_name', 'middle_name', 'email', 'email_hash', 'password', 'role'] as $column) $table->text($column)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('consent_given')->default(false);
            $table->timestamp('consent_given_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        foreach ([
            '2026_07_16_000002_create_academic_programs_table.php',
            '2026_07_16_000003_create_school_years_table.php',
            '2026_07_16_000005_create_graduate_tracer_survey_table.php',
            '2026_07_16_000006_create_survey_child_tables.php',
            '2026_07_20_000001_add_residence_city_to_general_information.php',
            '2026_07_21_000001_add_first_job_related_to_course_to_employment_data.php',
            '2026_07_24_000001_create_other_graduates_table.php',
            '2026_07_24_000002_create_user_number_table.php',
            '2026_07_28_000002_create_graduate_program_table.php',
            '2026_07_28_000003_create_address_table.php',
            '2026_08_03_000001_add_residence_location_to_general_information.php',
            '2026_08_03_000003_create_location_table.php',
        ] as $migration) (require database_path('migrations/'.$migration))->up();
        Schema::table('general_information', function (Blueprint $table) {
            $table->text('last_name')->nullable();
            $table->text('middle_name')->nullable();
        });
        Schema::create('self_employed_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employment_data_id')->constrained('employment_data')->cascadeOnDelete();
            $table->string('skill_name', 200);
        });
        DB::table('academic_programs')->insert(['id' => 1, 'name' => 'Computer Science', 'code' => 'BSCS']);
        DB::table('school_years')->insert([
            ['id' => 1, 'label' => '2024-2025', 'is_current' => false],
            ['id' => 2, 'label' => '2025-2026', 'is_current' => true],
        ]);
    }

    private function graduate(): User
    {
        $user = User::create(['name' => 'Example', 'last_name' => 'Graduate', 'email' => uniqid().'@example.test',
            'password' => 'unused', 'role' => 'user', 'is_active' => true]);
        $user->forceFill(['email_verified_at' => now()])->save();
        GraduateProgram::create(['user_id' => $user->id, 'academic_program_id' => 1, 'school_year_id' => 1]);
        GraduateTracerSurvey::create(['user_id' => $user->id, 'academic_program_id' => 1, 'school_year_id' => 1]);
        return $user;
    }

    private function answers(): array
    {
        return [
            'last_name' => 'Graduate', 'name' => 'Example', 'birthday' => '2000-01-01',
            'mobile_number' => '09171234567', 'civil_status' => 'single', 'sex' => 'male',
            'region_of_origin' => 'Caraga', 'residence_location' => 'city',
            'current_street' => 'Example', 'current_municipality' => 'Tandag',
            'permanent_street' => 'Example', 'permanent_municipality' => 'Tandag',
            'current_barangay' => 'Dagocdoc', 'permanent_barangay' => 'Dagocdoc',
            'education' => [['degree' => 'BSCS', 'college_university' => 'NEMSU', 'year_graduated' => '2025-2026']],
            'trainings' => [['title' => 'Example training', 'duration_credits' => '1 day', 'institution' => 'NEMSU']],
            'advance_study_reason' => 'professional_development',
            'employment_status' => 'yes', 'present_employment_status' => 'self_employed',
            'self_employed_skills' => [['id' => '', 'name' => 'Custom community robotics'], ['id' => '', 'name' => '']],
            'present_occupation' => 'Custom robotics specialist', 'business_line' => 'Custom community robotics services',
            'place_of_work' => 'local', 'is_first_job' => '1', 'first_job_related_to_course' => '1',
            'reasons_staying' => ['special_skill'], 'reasons_accepting' => ['related_course'],
            'consent_agreement' => '1', 'consent_latitude' => 8, 'consent_longitude' => 126,
        ];
    }

    public function test_submission_saves_custom_answers_updates_school_year_and_opens_reward_popup(): void
    {
        $user = $this->graduate();
        $other = $this->graduate();
        $this->actingAs($user)->get(route('user.survey'))->assertOk()
            ->assertSee('name="education[0][year_graduated]"', false)->assertSee('value="2025-2026"', false);
        $this->post(route('user.survey.store'), $this->answers())->assertSessionHasNoErrors()
            ->assertRedirect(route('user.dashboard'))->assertSessionHas('show_reward_modal', true);
        $this->assertDatabaseHas('graduate_program', ['user_id' => $user->id, 'school_year_id' => 2]);
        $this->assertDatabaseHas('graduate_tracer_survey', ['user_id' => $user->id, 'school_year_id' => 2]);
        $this->assertDatabaseHas('graduate_program', ['user_id' => $other->id, 'school_year_id' => 1]);
        $this->assertDatabaseHas('educational_background', ['year_graduated' => '2025-2026']);
        $employment = EmploymentData::whereHas('survey', fn ($q) => $q->where('user_id', $user->id))->firstOrFail();
        $this->assertSame('Custom robotics specialist', $employment->present_occupation);
        $this->assertSame('Custom community robotics services', $employment->business_line);
        $this->assertSame(['Custom community robotics'], $employment->selfEmployedSkills->pluck('skill_name')->all());
        $this->get(route('user.dashboard'))->assertOk()->assertSee('id="freeLoadRewardModal"', false)
            ->assertSee('getOrCreateInstance(document.getElementById(\'freeLoadRewardModal\')).show()', false);
        $this->get(route('user.survey'))->assertOk()->assertSee('Custom robotics specialist')
            ->assertSee('value="2025-2026" selected', false);
        $this->actingAs($user->fresh())->post(route('user.gcash-number.save'), ['number' => '09171234567'])
            ->assertSessionHasNoErrors()->assertRedirect(route('user.dashboard'));
        $this->assertSame('09171234567', UserNumber::where('user_id', $user->id)->firstOrFail()->number);
    }

    public function test_unknown_year_or_catalog_ids_are_rejected_without_changing_registration(): void
    {
        $user = $this->graduate();
        $answers = $this->answers();
        $answers['education'][0]['year_graduated'] = '2098-2099';
        $answers['self_employed_skills'][0]['id'] = 'invalid-skill';
        $answers['present_occupation_id'] = 'invalid-occupation';
        $answers['business_line_id'] = 'invalid-business';
        $this->actingAs($user)->postJson(route('user.survey.store'), $answers)->assertUnprocessable()
            ->assertJsonValidationErrors(['education.0.year_graduated', 'self_employed_skills.0.id', 'present_occupation', 'business_line']);
        $this->assertDatabaseHas('graduate_program', ['user_id' => $user->id, 'school_year_id' => 1]);
        $this->assertDatabaseCount('employment_data', 0);
    }

    public function test_catalog_selections_keep_their_canonical_names_alongside_custom_skills(): void
    {
        $user = $this->graduate();
        $skill = array_values(CollegeSkillsCatalog::lookup())[0];
        $occupation = collect(OccupationCatalog::lookup())->first(fn ($item) => OccupationCatalog::isCompatible($item, 'EMP-SELF_EMPLOYED'));
        $business = array_values(BusinessLineCatalog::lookup())[0];
        $answers = $this->answers();
        $answers['self_employed_skills'] = [
            ['id' => $skill['id'], 'name' => 'Changed client name'],
            ['id' => '', 'name' => 'Custom community robotics'],
        ];
        $answers['present_occupation_id'] = $occupation['id'];
        $answers['business_line_id'] = $business['id'];
        $this->actingAs($user)->post(route('user.survey.store'), $answers)->assertSessionHasNoErrors()
            ->assertRedirect(route('user.dashboard'));
        $employment = EmploymentData::firstOrFail();
        $this->assertSame($occupation['name'], $employment->present_occupation);
        $this->assertSame($business['name'], $employment->business_line);
        $this->assertSame([$skill['name'], 'Custom community robotics'], $employment->selfEmployedSkills->pluck('skill_name')->all());
    }

    public function test_birthdate_validation_uses_the_selected_school_year(): void
    {
        $answers = $this->answers();
        $answers['birthday'] = '2004-01-01';
        $answers['education'][0]['year_graduated'] = '2024-2025';
        $this->actingAs($this->graduate())->postJson(route('user.survey.store'), $answers)
            ->assertUnprocessable()->assertJsonValidationErrors('birthday');
        $answers['education'][0]['year_graduated'] = '2025-2026';
        $this->post(route('user.survey.store'), $answers)->assertSessionHasNoErrors()->assertRedirect(route('user.dashboard'));
    }

    public function test_reward_number_cannot_be_saved_before_survey_completion(): void
    {
        $this->actingAs($this->graduate())->post(route('user.gcash-number.save'), ['number' => '09171234567'])
            ->assertForbidden();
        $this->assertDatabaseCount('user_number', 0);
    }

    public function test_both_barangays_are_required_and_both_streets_can_be_omitted(): void
    {
        $answers = $this->answers();
        unset($answers['current_barangay'], $answers['permanent_barangay']);
        $this->actingAs($this->graduate())->postJson(route('user.survey.store'), $answers)
            ->assertUnprocessable()->assertJsonValidationErrors(['current_barangay', 'permanent_barangay']);
        $answers = $this->answers();
        unset($answers['current_street'], $answers['permanent_street']);
        $this->post(route('user.survey.store'), $answers)->assertSessionHasNoErrors()
            ->assertRedirect(route('user.dashboard'));
        $this->assertDatabaseHas('address', ['current_street' => null, 'permanent_street' => null,
            'current_barangay' => 'Dagocdoc', 'permanent_barangay' => 'Dagocdoc']);
    }
}
