<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class SharedUiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
    }

    private function signIn(string $role): User
    {
        $user = new User(['name' => 'Preview Account', 'email' => 'preview@example.test',
            'role' => $role, 'is_active' => true]);
        $user->id = 100;
        $user->setRelation('survey', null);
        $this->actingAs($user);
        return $user;
    }

    public function test_guest_login_has_accessible_inputs_and_shared_branding(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('auth-brand', false)
            ->assertSee('for="loginEmail"', false)->assertSee('autocomplete="current-password"', false)
            ->assertSee('Skip to main content')->assertSee('tracer-ui.js')
            ->assertDontSee('id="tracerSidebar"', false);
    }

    public function test_admin_dashboard_has_complete_navigation_and_summary_links(): void
    {
        $this->signIn('admin');
        $view = $this->view('admin.dashboard', ['totalUsers' => 120, 'totalPrograms' => 5,
            'totalSchoolYears' => 3, 'totalSurveysSubmitted' => 80]);
        $view->assertSee('System overview')->assertSee('Admin workspace')
            ->assertSee('120')->assertSee('80')->assertSee('workspace-metrics', false);
        foreach (['admin.users.index', 'admin.programs.index', 'admin.school-years.index',
            'admin.audit-logs.index', 'admin.analytics', 'admin.templates', 'admin.integrations', 'admin.map'] as $route) {
            $view->assertSee(route($route), false);
        }
        $this->assertSame(1, substr_count((string) $view, 'id="navAccountSection"'));
    }

    public function test_graduate_navigation_does_not_display_staff_tools(): void
    {
        $this->signIn('user');
        $view = $this->view('user.dashboard', ['survey' => null, 'submissionStatus' => 'not_started', 'userNumber' => null]);
        $view->assertSee('Graduate workspace')->assertSee('My Survey')->assertSee('My Profile')
            ->assertSee('Not started')->assertSee('Start survey')
            ->assertDontSee(route('admin.analytics'), false)->assertDontSee(route('admin.users.index'), false);
        $this->assertSame(1, substr_count((string) $view, 'id="navAccountSection"'));
    }

    public function test_empty_user_list_has_scrollable_table_and_bootstrap_pagination(): void
    {
        $this->signIn('admin');
        $users = new LengthAwarePaginator([], 0, 10);
        $this->view('admin.users.index', compact('users'))->assertSee('No accounts found.')
            ->assertSee('table-responsive', false)->assertSee('Actions');
        $this->assertSame('pagination::bootstrap-5', LengthAwarePaginator::$defaultView);
    }

    public function test_program_and_school_year_forms_have_visible_labels(): void
    {
        $this->signIn('admin');
        $this->view('admin.programs.index', ['departments' => collect(), 'programs' => collect()])
            ->assertSee('Add department')->assertSee('Add program')->assertSee('Department');
        $this->view('admin.school-years.index', ['schoolYears' => collect()])
            ->assertSee('for="schoolYearLabel"', false)->assertSee('Academic year');
    }

    public function test_registration_and_password_recovery_share_the_auth_design(): void
    {
        $this->view('auth.register', ['programs' => collect(), 'schoolYears' => collect()])
            ->assertSee('auth-brand', false)->assertSee('Create your account');
        $this->view('auth.reset-password', ['token' => 'preview-token', 'email' => 'preview@example.test'])
            ->assertSee('auth-brand', false)->assertSee('autocomplete="new-password"', false);
    }

    public function test_graduate_dashboard_preserves_draft_and_submitted_actions(): void
    {
        $this->signIn('user');
        $this->view('user.dashboard', ['survey' => null, 'submissionStatus' => 'draft', 'userNumber' => null])
            ->assertSee('Draft in progress')->assertSee('Continue survey');
        $this->view('user.dashboard', ['survey' => (object) ['submitted_at' => now()],
            'submissionStatus' => 'submitted', 'userNumber' => null])
            ->assertSee('Update my answers')->assertSee('Preview my survey')
            ->assertSee('for="rewardNumber"', false);
    }

    public function test_analytics_renders_section_controls_and_donut_totals(): void
    {
        $this->signIn('faculty');
        $chart = ['key' => 'general_information_sex', 'section' => 'A', 'question' => 'Sex',
            'labels' => ['Male', 'Female'], 'counts' => [9, 1]];
        $this->view('admin.analytics.index', ['charts' => [$chart], 'totalRespondents' => 10,
            'sections' => ['A' => ['title' => 'A. General Information', 'charts' => [$chart]]]])
            ->assertSee('Total respondents')->assertSee('data-analytics-section="A"', false)
            ->assertSee('data-section-panel="A"', false)->assertSee('tracer-pie-center', false)
            ->assertSee('90%')->assertSee('Export CSV');
    }
}
