<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LiveNameFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null, 'session.driver' => 'array']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            foreach (['name', 'last_name', 'middle_name', 'email', 'email_hash', 'password', 'role'] as $column) {
                $table->text($column)->nullable();
            }
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('graduate_tracer_survey', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('academic_program_id')->nullable();
            $table->timestamp('submitted_at')->nullable();
        });
        Schema::create('academic_programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('user_number', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->text('number');
            $table->boolean('is_done')->default(false);
        });
    }

    private function account(string $name, string $last, string $role = 'user'): User
    {
        return User::create(['name' => $name, 'last_name' => $last, 'middle_name' => 'Labrador',
            'email' => strtolower($name).'@example.test', 'password' => 'unused', 'role' => $role,
            'is_active' => true]);
    }

    public function test_live_user_search_matches_decrypted_names_with_combined_filters(): void
    {
        $admin = $this->account('Staff', 'Admin', 'admin');
        $match = $this->account('Joseph', 'Alferez');
        $match->forceFill(['email_verified_at' => now()])->save();
        $this->account('Another', 'Graduate');
        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'search' => 'alferez, JOSEPH', 'role' => 'user', 'verified' => 'yes', 'status' => 'active',
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $response->assertOk()->assertSee('ALFEREZ, JOSEPH LABRADOR')->assertDontSee('another@example.test')
            ->assertSee('1 accounts found.')->assertSee('id="userResults"', false);
        $this->assertNotSame($match->name, DB::table('users')->where('id', $match->id)->value('name'));
        $this->get(route('admin.users.index', ['search' => 'Joseph', 'status' => 'inactive']))
            ->assertOk()->assertSee('No accounts found.');
    }

    public function test_search_filters_before_pagination_and_preserves_query(): void
    {
        $admin = $this->account('Staff', 'Admin', 'admin');
        for ($i = 0; $i < 12; $i++) $this->account('Graduate'.$i, 'Matching');
        $response = $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'Matching', 'page' => 2]));
        $response->assertOk()->assertSee('12 accounts found.')
            ->assertViewHas('users', fn ($users) => $users->count() === 2 && $users->total() === 12)
            ->assertSee('search=Matching', false);
    }

    public function test_faculty_can_search_submitted_templates_and_integrations_by_email(): void
    {
        $faculty = $this->account('Faculty', 'Viewer', 'faculty');
        $graduate = $this->account('Joseph', 'Alferez');
        $other = $this->account('Another', 'Graduate');
        $draft = $this->account('Draft', 'Graduate');
        $program = DB::table('academic_programs')->insertGetId(['name' => 'Computer Science']);
        foreach ([$graduate, $other, $draft] as $user) {
            DB::table('graduate_tracer_survey')->insert(['user_id' => $user->id, 'academic_program_id' => $program,
                'submitted_at' => $user === $draft ? null : now()]);
        }
        $this->actingAs($faculty)->get(route('admin.templates', ['search' => 'joseph@example.test']))
            ->assertOk()->assertSee('Alferez, Joseph Labrador')->assertDontSee('another@example.test')
            ->assertSee('1 submitted surveys found.')->assertSee('id="templateResults"', false);
        $this->get(route('admin.templates', ['search' => 'Draft']))->assertOk()
            ->assertSee('No submitted surveys match this search.');
        $this->get(route('admin.integrations', ['search' => 'Alferez']))->assertOk()
            ->assertSee('Alferez, Joseph Labrador')->assertDontSee('another@example.test')
            ->assertSee('id="integrationResults"', false)->assertSee('1 graduates found.');
        $this->get(route('admin.users.index'))->assertForbidden();
    }
}
