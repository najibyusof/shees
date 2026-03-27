<?php

namespace Tests\Feature;

use App\Models\NcrReport;
use App\Models\Role;
use App\Models\SiteAudit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteAuditWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_audit_workflow_requires_distinct_manager_and_safety_approvals(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $owner = User::factory()->create();
        $manager = User::factory()->create();
        $safetyOfficer = User::factory()->create();

        $workerRole = Role::query()->where('name', 'Worker')->firstOrFail();
        $managerRole = Role::query()->where('name', 'Manager')->firstOrFail();
        $safetyRole = Role::query()->where('name', 'Safety Officer')->firstOrFail();

        $owner->roles()->attach($workerRole->id);
        $manager->roles()->attach($managerRole->id);
        $safetyOfficer->roles()->attach($safetyRole->id);

        $this->actingAs($owner)
            ->post(route('site-audits.store'), [
                'site_name' => 'Plant A',
                'area' => 'Blending',
                'audit_type' => 'internal',
                'scheduled_for' => now()->addDays(2)->format('Y-m-d'),
                'scope' => 'Weekly quality and safety audit',
                'status' => 'draft',
            ])
            ->assertRedirect(route('site-audits.index'));

        $audit = SiteAudit::query()->latest('id')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('site-audits.submit', $audit))
            ->assertRedirect(route('site-audits.show', $audit));

        $this->assertDatabaseHas('site_audits', [
            'id' => $audit->id,
            'status' => 'submitted',
        ]);

        $this->actingAs($manager)
            ->post(route('site-audits.approve', $audit), [
                'remarks' => 'Manager review complete.',
            ])
            ->assertRedirect(route('site-audits.show', $audit));

        $this->assertDatabaseHas('site_audits', [
            'id' => $audit->id,
            'status' => 'under_review',
        ]);

        $this->actingAs($safetyOfficer)
            ->post(route('site-audits.approve', $audit), [
                'remarks' => 'Safety review complete.',
            ])
            ->assertRedirect(route('site-audits.show', $audit));

        $this->assertDatabaseHas('site_audits', [
            'id' => $audit->id,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('site_audit_approvals', [
            'site_audit_id' => $audit->id,
            'decision' => 'approved',
            'approver_role' => 'Manager',
        ]);

        $this->assertDatabaseHas('site_audit_approvals', [
            'site_audit_id' => $audit->id,
            'decision' => 'approved',
            'approver_role' => 'Safety Officer',
        ]);
    }

    public function test_site_audit_can_track_ncr_root_cause_and_corrective_actions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $owner = User::factory()->create();
        $assignee = User::factory()->create();

        $workerRole = Role::query()->where('name', 'Worker')->firstOrFail();
        $owner->roles()->attach($workerRole->id);
        $assignee->roles()->attach($workerRole->id);

        $audit = SiteAudit::query()->create([
            'created_by' => $owner->id,
            'reference_no' => 'AUD-TEST-1001',
            'site_name' => 'Plant B',
            'area' => 'Packaging',
            'audit_type' => 'internal',
            'status' => 'in_progress',
            'scheduled_for' => now()->toDateString(),
        ]);

        $this->actingAs($owner)
            ->post(route('site-audits.ncrs.store', $audit), [
                'title' => 'Calibration deviation',
                'description' => 'Gauge out of tolerance during sampling.',
                'severity' => 'major',
                'owner_id' => $owner->id,
                'root_cause' => 'Calibration interval exceeded.',
                'containment_action' => 'Stopped line and quarantined lot.',
                'corrective_action_plan' => 'Introduce calibration alerts and weekly verification.',
                'due_date' => now()->addDays(7)->format('Y-m-d'),
                'status' => 'open',
            ])
            ->assertRedirect(route('site-audits.show', $audit));

        $ncr = NcrReport::query()->latest('id')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('ncr-reports.corrective-actions.store', $ncr), [
                'title' => 'Calibrate and verify gauge set',
                'description' => 'Complete full calibration and verification cycle for all gauges.',
                'assigned_to' => $assignee->id,
                'due_date' => now()->addDays(3)->format('Y-m-d'),
                'status' => 'open',
            ])
            ->assertRedirect(route('site-audits.show', $audit));

        $this->assertDatabaseHas('ncr_reports', [
            'id' => $ncr->id,
            'site_audit_id' => $audit->id,
            'root_cause' => 'Calibration interval exceeded.',
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('corrective_actions', [
            'ncr_report_id' => $ncr->id,
            'assigned_to' => $assignee->id,
            'status' => 'open',
        ]);
    }

    public function test_non_owner_without_ncr_permission_cannot_create_ncr_on_audit(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $owner = User::factory()->create();
        $otherWorker = User::factory()->create();

        $workerRole = Role::query()->where('name', 'Worker')->firstOrFail();
        $owner->roles()->attach($workerRole->id);
        $otherWorker->roles()->attach($workerRole->id);

        $audit = SiteAudit::query()->create([
            'created_by' => $owner->id,
            'reference_no' => 'AUD-TEST-1002',
            'site_name' => 'Plant C',
            'area' => 'Utility',
            'audit_type' => 'internal',
            'status' => 'in_progress',
            'scheduled_for' => now()->toDateString(),
        ]);

        $this->actingAs($otherWorker)
            ->post(route('site-audits.ncrs.store', $audit), [
                'title' => 'Unauthorized NCR',
                'description' => 'Attempt by non-owner worker',
                'severity' => 'minor',
                'status' => 'open',
            ])
            ->assertForbidden();
    }
}
