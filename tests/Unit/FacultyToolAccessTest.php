<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FacultyToolAccessTest extends TestCase
{
    public function test_faculty_and_admin_share_only_the_requested_tool_routes(): void
    {
        $sharedRoutes = [
            'admin.analytics',
            'admin.analytics.export',
            'admin.templates',
            'admin.templates.survey-preview',
            'admin.templates.survey-export',
            'admin.integrations',
            'admin.integrations.mark-done',
            'admin.map',
            'admin.map.locations',
        ];

        foreach ($sharedRoutes as $name) {
            $this->assertContains(
                'role:admin,faculty',
                Route::getRoutes()->getByName($name)->gatherMiddleware(),
                "{$name} should be available to active administrators and faculty."
            );
        }
    }

    public function test_management_routes_remain_admin_only(): void
    {
        $adminOnlyRoutes = [
            'admin.users.index',
            'admin.programs.index',
            'admin.school-years.index',
            'admin.audit-logs.index',
            'admin.surveys.reason',
        ];

        foreach ($adminOnlyRoutes as $name) {
            $middleware = Route::getRoutes()->getByName($name)->gatherMiddleware();

            $this->assertContains('role:admin', $middleware, "{$name} must remain admin-only.");
            $this->assertNotContains('role:admin,faculty', $middleware, "{$name} must not be shared with faculty.");
        }
    }
}
