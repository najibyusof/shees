<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Incident;
use App\Models\IncidentApproval;
use App\Models\IncidentAttachment;
use App\Models\IncidentComment;
use App\Models\User;
use Database\Seeders\Support\SeedDataGenerator;
use Database\Factories\IncidentFactory;
use Illuminate\Database\Seeder;

class IncidentSeeder extends Seeder
{
    public function run(): void
    {
        $faker = class_exists('Faker\\Factory')
            ? \Faker\Factory::create()
            : new SeedDataGenerator();

        $reporters = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Worker', 'Supervisor']))
            ->get();

        $safetyOfficers = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Safety Officer'))
            ->get();

        $managers = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Manager'))
            ->get();

        $commenters = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Worker', 'Supervisor', 'Safety Officer', 'Manager']))
            ->get();

        if ($reporters->isEmpty() || $safetyOfficers->isEmpty() || $managers->isEmpty()) {
            return;
        }

        $statuses = [
            'draft', 'draft', 'draft', 'draft',
            'submitted', 'submitted', 'submitted', 'submitted',
            'under_review', 'under_review', 'under_review', 'under_review',
            'approved', 'approved', 'approved', 'approved',
            'rejected', 'rejected', 'rejected', 'rejected',
        ];

        foreach ($statuses as $status) {
            $reporter = $reporters->random();
            $safetyOfficer = $safetyOfficers->random();
            $manager = $managers->random();

            $occurredAt = now()->subDays(random_int(1, 90))->subHours(random_int(0, 20));
            $submittedAt = in_array($status, ['submitted', 'under_review', 'approved', 'rejected'], true)
                ? (clone $occurredAt)->addHours(random_int(1, 8))
                : null;
            $reviewedAt = in_array($status, ['under_review', 'approved', 'rejected'], true)
                ? ($submittedAt ? (clone $submittedAt)->addHours(random_int(1, 24)) : now())
                : null;

            $incident = IncidentFactory::new()->create([
                'reported_by' => $reporter->id,
                'submitted_by' => $submittedAt ? $reporter->id : null,
                'reviewed_by' => $reviewedAt ? $safetyOfficer->id : null,
                'approved_by' => $status === 'approved' ? $manager->id : null,
                'rejected_by' => $status === 'rejected' ? $manager->id : null,
                'status' => $status,
                'datetime' => $occurredAt,
                'submitted_at' => $submittedAt,
                'reviewed_at' => $reviewedAt,
                'approved_at' => $status === 'approved' && $reviewedAt ? (clone $reviewedAt)->addHours(random_int(1, 8)) : null,
                'rejected_at' => $status === 'rejected' && $reviewedAt ? (clone $reviewedAt)->addHours(random_int(1, 8)) : null,
                'rejection_reason' => $status === 'rejected' ? 'Insufficient containment evidence from initial report.' : null,
                'title' => 'Incident: '.$faker->randomElement([
                    'Forklift near miss',
                    'Chemical spill in utility room',
                    'PPE non-compliance at loading dock',
                    'Minor fire alarm activation',
                    'Slip hazard near wash area',
                ]),
            ]);

            $attachmentCount = random_int(1, 3);
            for ($i = 0; $i < $attachmentCount; $i++) {
                $ext = $faker->randomElement(['pdf', 'jpg', 'png']);
                $name = $faker->slug(3).'-'.$faker->numerify('####');

                IncidentAttachment::query()->create([
                    'incident_id' => $incident->id,
                    'original_name' => $name.'.'.$ext,
                    'path' => 'incidents/'.$name.'.'.$ext,
                    'mime_type' => $ext === 'pdf' ? 'application/pdf' : 'image/'.$ext,
                    'size' => random_int(75_000, 2_500_000),
                ]);
            }

            $commentCount = random_int(2, 5);
            for ($i = 0; $i < $commentCount; $i++) {
                $commenter = $commenters->random();
                $commentText = $faker->sentence(14);

                if ($status === 'rejected' && $i === 0) {
                    $commenter = $manager;
                    $commentText = 'Rejected pending additional hazard controls and revised investigation notes.';
                }

                IncidentComment::query()->create([
                    'incident_id' => $incident->id,
                    'user_id' => $commenter->id,
                    'comment' => $commentText,
                    'created_at' => now()->subDays(random_int(0, 15)),
                    'updated_at' => now()->subDays(random_int(0, 15)),
                ]);
            }

            if ($status === 'approved') {
                IncidentApproval::query()->create([
                    'incident_id' => $incident->id,
                    'approver_id' => $safetyOfficer->id,
                    'approver_role' => 'Safety Officer',
                    'decision' => 'approved',
                    'remarks' => 'Safety controls validated and accepted.',
                    'decided_at' => $reviewedAt ? (clone $reviewedAt)->addMinutes(30) : now(),
                ]);

                IncidentApproval::query()->create([
                    'incident_id' => $incident->id,
                    'approver_id' => $manager->id,
                    'approver_role' => 'Manager',
                    'decision' => 'approved',
                    'remarks' => 'Manager approval completed for closure.',
                    'decided_at' => $incident->approved_at ?? now(),
                ]);
            }

            if ($status === 'rejected') {
                IncidentApproval::query()->create([
                    'incident_id' => $incident->id,
                    'approver_id' => $manager->id,
                    'approver_role' => 'Manager',
                    'decision' => 'rejected',
                    'remarks' => 'Rejected due to unresolved root cause details.',
                    'decided_at' => $incident->rejected_at ?? now(),
                ]);
            }
        }

        // Scenario set: 3 high-risk incidents with full workflow and corrective action trail.
        for ($i = 1; $i <= 3; $i++) {
            $reporter = $reporters->random();
            $safetyOfficer = $safetyOfficers->random();
            $manager = $managers->random();

            $occurredAt = now()->subDays(random_int(10, 25))->subHours(random_int(2, 10));
            $submittedAt = (clone $occurredAt)->addHours(random_int(1, 3));
            $reviewedAt = (clone $submittedAt)->addHours(random_int(2, 8));
            $approvedAt = (clone $reviewedAt)->addHours(random_int(2, 10));
            $correctiveActionAt = (clone $approvedAt)->addHours(random_int(6, 24));

            $incident = IncidentFactory::new()->create([
                'reported_by' => $reporter->id,
                'submitted_by' => $reporter->id,
                'reviewed_by' => $safetyOfficer->id,
                'approved_by' => $manager->id,
                'rejected_by' => null,
                'title' => 'High Risk: '.$faker->randomElement([
                    'Ammonia leak near compressor bay',
                    'Scaffold collapse near loading area',
                    'Electrical panel arc flash event',
                ]),
                'classification' => 'Critical',
                'status' => 'approved',
                'datetime' => $occurredAt,
                'submitted_at' => $submittedAt,
                'reviewed_at' => $reviewedAt,
                'approved_at' => $approvedAt,
                'rejected_at' => null,
                'rejection_reason' => null,
                'description' => 'Critical incident requiring immediate controls, executive visibility, and post-approval corrective actions.',
            ]);

            foreach (['initial-scene-photo.jpg', 'investigation-report.pdf', 'corrective-action-plan.pdf'] as $doc) {
                IncidentAttachment::query()->create([
                    'incident_id' => $incident->id,
                    'original_name' => $doc,
                    'path' => 'incidents/high-risk-'.$incident->id.'-'.$doc,
                    'mime_type' => str_ends_with($doc, '.pdf') ? 'application/pdf' : 'image/jpeg',
                    'size' => random_int(150_000, 2_200_000),
                ]);
            }

            IncidentComment::query()->create([
                'incident_id' => $incident->id,
                'user_id' => $reporter->id,
                'comment' => 'Initial emergency controls applied and area isolated.',
                'created_at' => $submittedAt,
                'updated_at' => $submittedAt,
            ]);

            IncidentComment::query()->create([
                'incident_id' => $incident->id,
                'user_id' => $safetyOfficer->id,
                'comment' => 'Root cause review completed; recommending management approval.',
                'created_at' => $reviewedAt,
                'updated_at' => $reviewedAt,
            ]);

            IncidentComment::query()->create([
                'incident_id' => $incident->id,
                'user_id' => $manager->id,
                'comment' => 'Approved. Corrective action assignment required within 24 hours.',
                'created_at' => $approvedAt,
                'updated_at' => $approvedAt,
            ]);

            IncidentComment::query()->create([
                'incident_id' => $incident->id,
                'user_id' => $safetyOfficer->id,
                'comment' => 'Corrective action opened: install additional engineering controls and retrain line personnel.',
                'created_at' => $correctiveActionAt,
                'updated_at' => $correctiveActionAt,
            ]);

            IncidentApproval::query()->create([
                'incident_id' => $incident->id,
                'approver_id' => $safetyOfficer->id,
                'approver_role' => 'Safety Officer',
                'decision' => 'approved',
                'remarks' => 'Technical review complete and controls validated.',
                'decided_at' => (clone $reviewedAt)->addMinutes(30),
            ]);

            IncidentApproval::query()->create([
                'incident_id' => $incident->id,
                'approver_id' => $manager->id,
                'approver_role' => 'Manager',
                'decision' => 'approved',
                'remarks' => 'Approved with mandatory corrective action follow-up.',
                'decided_at' => $approvedAt,
            ]);

            AuditLog::query()->create([
                'user_id' => $safetyOfficer->id,
                'module' => 'incidents',
                'action' => 'corrective_action',
                'auditable_type' => Incident::class,
                'auditable_id' => $incident->id,
                'metadata' => [
                    'description' => 'Corrective action initiated after high-risk incident approval.',
                    'seeded' => true,
                ],
                'created_at' => $correctiveActionAt,
                'updated_at' => $correctiveActionAt,
            ]);
        }

        // Scenario: escalation when incident is not approved within 3 days.
        $escalationReporter = $reporters->random();
        $escalationSafety = $safetyOfficers->random();
        $escalationManager = $managers->random();
        $escalationOccurredAt = now()->subDays(8);
        $escalationSubmittedAt = (clone $escalationOccurredAt)->addHours(2);
        $escalatedAt = (clone $escalationSubmittedAt)->addDays(3)->addHours(2);
        $escalationApprovedAt = (clone $escalatedAt)->addHours(10);

        $escalatedIncident = IncidentFactory::new()->create([
            'reported_by' => $escalationReporter->id,
            'submitted_by' => $escalationReporter->id,
            'reviewed_by' => $escalationSafety->id,
            'approved_by' => $escalationManager->id,
            'status' => 'approved',
            'datetime' => $escalationOccurredAt,
            'submitted_at' => $escalationSubmittedAt,
            'reviewed_at' => $escalatedAt,
            'approved_at' => $escalationApprovedAt,
            'title' => 'Escalation Case: Delayed approval for confined space incident',
            'classification' => 'Major',
            'description' => 'Approval SLA breached; escalated to manager after 3 days without final approval.',
        ]);

        IncidentComment::query()->create([
            'incident_id' => $escalatedIncident->id,
            'user_id' => $escalationSafety->id,
            'comment' => 'No final approval after 72 hours; escalating to manager per workflow SLA.',
            'created_at' => $escalatedAt,
            'updated_at' => $escalatedAt,
        ]);

        IncidentApproval::query()->create([
            'incident_id' => $escalatedIncident->id,
            'approver_id' => $escalationSafety->id,
            'approver_role' => 'Safety Officer',
            'decision' => 'approved',
            'remarks' => 'Technical review done; pending management sign-off.',
            'decided_at' => $escalatedAt,
        ]);

        IncidentApproval::query()->create([
            'incident_id' => $escalatedIncident->id,
            'approver_id' => $escalationManager->id,
            'approver_role' => 'Manager',
            'decision' => 'approved',
            'remarks' => 'Approved post-escalation.',
            'decided_at' => $escalationApprovedAt,
        ]);

        AuditLog::query()->create([
            'user_id' => $escalationSafety->id,
            'module' => 'incidents',
            'action' => 'escalate',
            'auditable_type' => Incident::class,
            'auditable_id' => $escalatedIncident->id,
            'metadata' => [
                'description' => 'Incident escalated to manager after >3 days pending approval.',
                'hours_to_escalation' => 74,
                'seeded' => true,
            ],
            'created_at' => $escalatedAt,
            'updated_at' => $escalatedAt,
        ]);
    }
}
