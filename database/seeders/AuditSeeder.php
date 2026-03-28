<?php

namespace Database\Seeders;

use App\Models\CorrectiveAction;
use App\Models\NcrReport;
use App\Models\SiteAudit;
use App\Models\SiteAuditApproval;
use App\Models\SiteAuditKpi;
use App\Models\User;
use Database\Seeders\Support\SeedDataGenerator;
use Illuminate\Database\Seeder;

class AuditSeeder extends Seeder
{
    public function run(): void
    {
        $faker = class_exists('Faker\\Factory')
            ? \Faker\Factory::create()
            : new SeedDataGenerator();

        $owners = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Supervisor', 'Safety Officer', 'Manager']))
            ->get();

        $managers = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Manager'))
            ->get();

        $safetyOfficers = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Safety Officer'))
            ->get();

        if ($owners->isEmpty() || $managers->isEmpty() || $safetyOfficers->isEmpty()) {
            return;
        }

        for ($i = 1; $i <= 5; $i++) {
            $owner = $owners->random();
            $manager = $managers->random();
            $safetyOfficer = $safetyOfficers->random();
            $status = $this->faker->randomElement(['submitted', 'under_review', 'approved']);
            $scheduledFor = now()->subDays(random_int(5, 40));
            $referenceNo = 'AUD-SEED-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT);

            if (SiteAudit::query()->where('reference_no', $referenceNo)->exists()) {
                continue;
            }

            $audit = SiteAudit::query()->create([
                'created_by' => $owner->id,
                'submitted_by' => $owner->id,
                'reviewed_by' => in_array($status, ['under_review', 'approved'], true) ? $safetyOfficer->id : null,
                'approved_by' => $status === 'approved' ? $manager->id : null,
                'rejected_by' => null,
                'reference_no' => $referenceNo,
                'site_name' => $this->faker->randomElement(['North Plant', 'South Plant', 'Main Facility']),
                'area' => $this->faker->randomElement(['Utilities', 'Packaging', 'Production', 'Storage']),
                'audit_type' => $this->faker->randomElement(['internal', 'external']),
                'scheduled_for' => $scheduledFor->toDateString(),
                'conducted_at' => (clone $scheduledFor)->addHours(3),
                'status' => $status,
                'kpi_score' => $this->faker->randomFloat(2, 70, 98),
                'scope' => $this->faker->sentence(12),
                'summary' => $this->faker->paragraph(2),
                'rejection_reason' => null,
                'submitted_at' => (clone $scheduledFor)->addHours(1),
                'reviewed_at' => in_array($status, ['under_review', 'approved'], true) ? (clone $scheduledFor)->addHours(6) : null,
                'approved_at' => $status === 'approved' ? (clone $scheduledFor)->addHours(10) : null,
                'rejected_at' => null,
            ]);

            SiteAuditKpi::query()->create([
                'site_audit_id' => $audit->id,
                'name' => 'PPE Compliance',
                'target_value' => 95,
                'actual_value' => $this->faker->randomFloat(2, 75, 99),
                'unit' => '%',
                'weight' => 3,
                'status' => 'completed',
                'notes' => 'Routine KPI seed data.',
            ]);

            if (in_array($status, ['under_review', 'approved'], true)) {
                SiteAuditApproval::query()->create([
                    'site_audit_id' => $audit->id,
                    'approver_id' => $safetyOfficer->id,
                    'approver_role' => 'Safety Officer',
                    'decision' => 'approved',
                    'remarks' => 'Initial review completed.',
                    'decided_at' => (clone $scheduledFor)->addHours(7),
                ]);
            }

            if ($status === 'approved') {
                SiteAuditApproval::query()->create([
                    'site_audit_id' => $audit->id,
                    'approver_id' => $manager->id,
                    'approver_role' => 'Manager',
                    'decision' => 'approved',
                    'remarks' => 'Final approval recorded.',
                    'decided_at' => (clone $scheduledFor)->addHours(10),
                ]);
            }

            $ncrCount = random_int(2, 5);
            for ($ncrNo = 1; $ncrNo <= $ncrCount; $ncrNo++) {
                $ownerUser = $owners->random();

                $ncr = NcrReport::query()->create([
                    'site_audit_id' => $audit->id,
                    'reported_by' => $owner->id,
                    'owner_id' => $ownerUser->id,
                    'verified_by' => null,
                    'reference_no' => 'NCR-SEED-'.$audit->id.'-'.str_pad((string) $ncrNo, 2, '0', STR_PAD_LEFT),
                    'title' => $this->faker->sentence(5),
                    'description' => $this->faker->paragraph(2),
                    'severity' => $this->faker->randomElement(['low', 'medium', 'high']),
                    'status' => $this->faker->randomElement(['open', 'in_progress', 'pending_verification']),
                    'root_cause' => $this->faker->sentence(10),
                    'containment_action' => $this->faker->sentence(10),
                    'corrective_action_plan' => $this->faker->sentence(12),
                    'due_date' => now()->addDays(random_int(7, 40))->toDateString(),
                    'verified_at' => null,
                    'closed_at' => null,
                ]);

                CorrectiveAction::query()->create([
                    'ncr_report_id' => $ncr->id,
                    'assigned_to' => $ownerUser->id,
                    'verified_by' => null,
                    'title' => 'Corrective Action for '.$ncr->reference_no,
                    'description' => $this->faker->sentence(12),
                    'status' => $this->faker->randomElement(['open', 'in_progress', 'completed']),
                    'due_date' => now()->addDays(random_int(5, 30))->toDateString(),
                    'completed_at' => null,
                    'verified_at' => null,
                    'completion_notes' => $this->faker->optional()->sentence(8),
                ]);
            }
        }

        // Scenario set: 2 failed audits with multiple NCRs each.
        for ($i = 1; $i <= 2; $i++) {
            $owner = $owners->random();
            $manager = $managers->random();
            $safetyOfficer = $safetyOfficers->random();
            $scheduledFor = now()->subDays(random_int(12, 35));
            $failedReferenceNo = 'AUD-FAIL-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT);

            if (SiteAudit::query()->where('reference_no', $failedReferenceNo)->exists()) {
                continue;
            }

            $failedAudit = SiteAudit::query()->create([
                'created_by' => $owner->id,
                'submitted_by' => $owner->id,
                'reviewed_by' => $safetyOfficer->id,
                'approved_by' => null,
                'rejected_by' => $manager->id,
                'reference_no' => $failedReferenceNo,
                'site_name' => $this->faker->randomElement(['North Plant', 'South Plant', 'Main Facility']),
                'area' => $this->faker->randomElement(['Packaging', 'Boiler', 'Chemical Storage']),
                'audit_type' => 'internal',
                'scheduled_for' => $scheduledFor->toDateString(),
                'conducted_at' => (clone $scheduledFor)->addHours(2),
                'status' => 'rejected',
                'kpi_score' => $this->faker->randomFloat(2, 42, 68),
                'scope' => 'Focused compliance audit for high-risk controls.',
                'summary' => 'Audit failed due to multiple high-severity non-conformances.',
                'rejection_reason' => 'Critical controls were non-compliant across multiple sections.',
                'submitted_at' => (clone $scheduledFor)->addHours(1),
                'reviewed_at' => (clone $scheduledFor)->addHours(6),
                'approved_at' => null,
                'rejected_at' => (clone $scheduledFor)->addHours(8),
            ]);

            SiteAuditApproval::query()->create([
                'site_audit_id' => $failedAudit->id,
                'approver_id' => $safetyOfficer->id,
                'approver_role' => 'Safety Officer',
                'decision' => 'approved',
                'remarks' => 'Review completed and escalated for management disposition.',
                'decided_at' => (clone $scheduledFor)->addHours(6),
            ]);

            SiteAuditApproval::query()->create([
                'site_audit_id' => $failedAudit->id,
                'approver_id' => $manager->id,
                'approver_role' => 'Manager',
                'decision' => 'rejected',
                'remarks' => 'Rejected due to unresolved critical findings.',
                'decided_at' => (clone $scheduledFor)->addHours(8),
            ]);

            $ncrCount = random_int(3, 5);
            for ($ncrNo = 1; $ncrNo <= $ncrCount; $ncrNo++) {
                $ownerUser = $owners->random();
                $severity = $this->faker->randomElement(['medium', 'high']);

                $ncr = NcrReport::query()->create([
                    'site_audit_id' => $failedAudit->id,
                    'reported_by' => $owner->id,
                    'owner_id' => $ownerUser->id,
                    'verified_by' => null,
                    'reference_no' => 'NCR-FAIL-'.$failedAudit->id.'-'.str_pad((string) $ncrNo, 2, '0', STR_PAD_LEFT),
                    'title' => 'Failed Audit NCR '.$ncrNo,
                    'description' => $this->faker->paragraph(2),
                    'severity' => $severity,
                    'status' => $this->faker->randomElement(['open', 'in_progress']),
                    'root_cause' => $this->faker->sentence(10),
                    'containment_action' => $this->faker->sentence(8),
                    'corrective_action_plan' => $this->faker->sentence(12),
                    'due_date' => now()->addDays(random_int(5, 25))->toDateString(),
                    'verified_at' => null,
                    'closed_at' => null,
                ]);

                CorrectiveAction::query()->create([
                    'ncr_report_id' => $ncr->id,
                    'assigned_to' => $ownerUser->id,
                    'verified_by' => null,
                    'title' => 'Urgent corrective action for '.$ncr->reference_no,
                    'description' => $this->faker->sentence(12),
                    'status' => $this->faker->randomElement(['open', 'in_progress']),
                    'due_date' => now()->addDays(random_int(3, 18))->toDateString(),
                    'completed_at' => null,
                    'verified_at' => null,
                    'completion_notes' => null,
                ]);
            }
        }
    }
}
