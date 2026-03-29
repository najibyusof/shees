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

        $incidentCount = DB::table('incidents')
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

        $incidentAuditLogs = DB::table('audit_logs')
            ->where('module', 'incidents')
            ->count();

        $this->assertGreaterThanOrEqual(1, $incidentCount, 'Expected at least 1 seeded incident.');
        $this->assertGreaterThanOrEqual(2, $failedAudits, 'Expected at least 2 failed audits.');
        $this->assertGreaterThanOrEqual(5, $workersWithExpiredCertificates, 'Expected at least 5 workers with expired certificates.');
        $this->assertGreaterThanOrEqual(3, $overdueTrainings, 'Expected at least 3 overdue trainings.');
        $this->assertGreaterThanOrEqual(1, $incidentAuditLogs, 'Expected incident lifecycle audit coverage from seeded activity.');
    }
}
