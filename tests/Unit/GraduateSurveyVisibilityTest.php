<?php

namespace Tests\Unit;

use App\Models\GraduateTracerSurvey;
use Tests\TestCase;

class GraduateSurveyVisibilityTest extends TestCase
{
    public function test_graduate_survey_scope_filters_by_the_accounts_current_user_role(): void
    {
        $query = GraduateTracerSurvey::forGraduateUsers();

        $this->assertStringContainsString('exists', strtolower($query->toSql()));
        $this->assertStringContainsString('users', strtolower($query->toSql()));
        $this->assertContains('user', $query->getBindings());
    }
}
