<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SeederScenarioCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seed_includes_required_real_world_scenarios(): void
    {
        $this->seed();

        $highRiskApproved = DB::table('incidents')
            ->where('classification', 'Critical')
            ->where('status', 'approved')
            ->where('title', 'like', 'High Risk:%')
            ->count();

        $failedAudits = DB::table('site_audits')
            ->where('status', 'rejected')
            ->where('reference_no', 'like', 'AUD-FAIL-%')
            ->count();

        $workersWithExpiredCertificates = DB::table('users')
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->join('certificates', 'certificates.user_id', '=', 'users.id')
            ->where('roles.name', 'Worker')
            ->whereDate('certificates.expires_at', '<', now()->toDateString())
            ->distinct('users.id')
            ->count('users.id');

        $overdueTrainings = DB::table('trainings')
            ->where('title', 'like', 'Overdue Mandatory Training %')
            ->count();

        $escalations = DB::table('audit_logs')
            ->where('module', 'incidents')
            ->where('action', 'escalate')
            ->count();

        $this->assertGreaterThanOrEqual(3, $highRiskApproved, 'Expected at least 3 approved high-risk incidents.');
        $this->assertGreaterThanOrEqual(2, $failedAudits, 'Expected at least 2 failed audits.');
        $this->assertGreaterThanOrEqual(5, $workersWithExpiredCertificates, 'Expected at least 5 workers with expired certificates.');
        $this->assertGreaterThanOrEqual(3, $overdueTrainings, 'Expected at least 3 overdue trainings.');
        $this->assertGreaterThanOrEqual(1, $escalations, 'Expected at least 1 escalation case.');
    }
}
