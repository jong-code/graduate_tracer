<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ResilientMailerService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // An isolated SQLite schema: the application's encryption migrations use MySQL SQL.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null, 'cache.default' => 'array', 'session.driver' => 'array']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->text('email');
            $table->string('email_hash')->unique();
            $table->string('password');
            $table->string('role')->default('user');
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    private function account(): User
    {
        return User::create(['name' => 'Security Test', 'email' => 'test@example.test',
            'password' => Hash::make('OldPassword123!'), 'role' => 'user', 'is_active' => true]);
    }

    private function resetPayload(string $token): array
    {
        return ['email' => 'TEST@example.test', 'token' => $token,
            'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!'];
    }

    public function test_expired_reset_token_cannot_change_password(): void
    {
        $user = $this->account();
        $token = str_repeat('a', 64);
        DB::table('password_reset_tokens')->insert(['email' => $user->email,
            'token' => Hash::make($token), 'created_at' => now()->subHours(2)]);
        $this->post('/reset-password', $this->resetPayload($token))->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('OldPassword123!', $user->fresh()->password));
    }

    public function test_valid_reset_is_single_use_and_rotates_remember_token(): void
    {
        $user = $this->account();
        $user->forceFill(['remember_token' => 'old-remember-token'])->save();
        $token = str_repeat('b', 64);
        DB::table('password_reset_tokens')->insert(['email' => $user->email,
            'token' => Hash::make($token), 'created_at' => now()->subMinutes(5)]);
        $this->post('/reset-password', $this->resetPayload($token))->assertRedirect('/login')->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
        $this->assertNotSame('old-remember-token', $user->fresh()->remember_token);
        $this->assertSame(0, DB::table('password_reset_tokens')->count());
        $this->post('/reset-password', $this->resetPayload($token))->assertSessionHasErrors('email');
    }

    public function test_wrong_token_cannot_change_password(): void
    {
        $user = $this->account();
        DB::table('password_reset_tokens')->insert(['email' => $user->email,
            'token' => Hash::make(str_repeat('c', 64)), 'created_at' => now()]);
        $this->post('/reset-password', $this->resetPayload(str_repeat('d', 64)))->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('OldPassword123!', $user->fresh()->password));
    }

    public function test_reset_email_can_be_resent_after_sixty_seconds(): void
    {
        $user = $this->account();
        $this->mock(ResilientMailerService::class)->shouldReceive('send')->once();
        DB::table('password_reset_tokens')->insert(['email' => $user->email,
            'token' => Hash::make('old'), 'created_at' => now()->subMinutes(2)]);
        $this->post('/forgot-password', ['email' => 'TEST@example.test'])->assertSessionHas('status');
        $this->assertFalse(Hash::check('old', DB::table('password_reset_tokens')->first()->token));
    }

    public function test_reset_email_is_not_resent_within_sixty_seconds(): void
    {
        $user = $this->account();
        $this->mock(ResilientMailerService::class)->shouldNotReceive('send');
        DB::table('password_reset_tokens')->insert(['email' => $user->email,
            'token' => Hash::make('old'), 'created_at' => now()->subSeconds(30)]);
        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');
        $this->assertTrue(Hash::check('old', DB::table('password_reset_tokens')->first()->token));
    }

    public function test_login_attempts_are_limited_across_source_ips(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.$i])
                ->post('/login', ['email' => 'missing@example.test', 'password' => 'wrong'])->assertStatus(302);
        }
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.6'])
            ->post('/login', ['email' => 'MISSING@example.test', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_password_change_rejects_existing_session(): void
    {
        $user = $this->account();
        $oldHash = $user->password;
        $user->forceFill(['password' => Hash::make('NewPassword123!')])->save();
        $this->actingAs($user)->withSession(['password_hash_web' => $oldHash])
            ->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_responses_have_security_headers(): void
    {
        $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')->assertHeader('Referrer-Policy', 'same-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), payment=(), usb=(), geolocation=(self)')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
            ->assertHeader('X-Permitted-Cross-Domain-Policies', 'none')
            ->assertHeader('Content-Security-Policy', "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'none'; form-action 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com; img-src 'self' data: https://unpkg.com https://tile.openstreetmap.org; font-src 'self' data:; connect-src 'self'; upgrade-insecure-requests");
    }

    public function test_map_allows_origin_referrers_for_tiles_without_relaxing_other_pages(): void
    {
        Schema::create('school_years', function (Blueprint $table) {
            $table->id();
            $table->string('label');
        });
        $admin = $this->account();
        $admin->update(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.map'))
            ->assertOk()->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('https://tile.openstreetmap.org/{z}/{x}/{y}.png', false)
            ->assertDontSee('https://{s}.tile.openstreetmap.org', false);
        $this->get('/')->assertHeader('Referrer-Policy', 'same-origin');
    }

    public function test_https_responses_enable_hsts(): void
    {
        $this->get('https://localhost/')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_authenticated_responses_cannot_be_stored_by_caches(): void
    {
        $this->actingAs($this->account())->get('/')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Pragma', 'no-cache');
    }

    public function test_graduate_cannot_access_staff_records_or_exports(): void
    {
        $this->actingAs($this->account());

        foreach (['/admin/users', '/admin/map/locations', '/admin/templates', '/admin/analytics/export'] as $path) {
            $this->get($path)->assertForbidden();
        }
    }

    public function test_survey_rejects_unknown_choice_keys_and_excess_repeat_rows(): void
    {
        Schema::create('school_years', function (Blueprint $table) {
            $table->id();
            $table->string('label');
        });
        Schema::create('graduate_program', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('school_year_id')->nullable();
        });
        Schema::create('academic_programs', function (Blueprint $table) {
            $table->id();
        });
        $user = $this->account();
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($user)->postJson('/user/survey', [
            'education' => array_fill(0, 11, [
                'degree' => 'Example', 'college_university' => 'Example',
                'year_graduated' => '2026',
            ]),
            'reasons_undergrad' => ['unrecognized_choice'],
            'reasons_not_employed' => ['arbitrary_value'],
            'competencies_useful' => ['arbitrary_value'],
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'education', 'reasons_undergrad.0', 'reasons_not_employed.0', 'competencies_useful.0',
        ]);
    }
}
