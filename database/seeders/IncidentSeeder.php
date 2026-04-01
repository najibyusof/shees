<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\AttachmentCategory;
use App\Models\AttachmentType;
use App\Models\CauseType;
use App\Models\DamageType;
use App\Models\ExternalParty;
use App\Models\FactorType;
use App\Models\Incident;
use App\Models\IncidentApproval;
use App\Models\IncidentAttachment;
use App\Models\IncidentClassification;
use App\Models\IncidentComment;
use App\Models\IncidentCommentReply;
use App\Models\IncidentLocation;
use App\Models\IncidentStatus;
use App\Models\IncidentType;
use App\Models\LocationType;
use App\Models\Subcontractor;
use App\Models\User;
use App\Models\VictimType;
use App\Models\WorkActivity;
use App\Models\WorkPackage;
use Database\Seeders\Support\SeedDataGenerator;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Carbon\Carbon;

class IncidentSeeder extends Seeder
{
    private const ANALYTICS_TARGET_TOTAL = 170;

    private const ANALYTICS_HARD_CAP = 200;

    public function run(): void
    {
        $faker = class_exists('Faker\\Factory')
            ? \Faker\Factory::create()
            : new SeedDataGenerator();

        $startingIncidents = Incident::query()->count();
        if ($startingIncidents >= self::ANALYTICS_TARGET_TOTAL && $startingIncidents <= self::ANALYTICS_HARD_CAP) {
            return;
        }

        if ($startingIncidents >= self::ANALYTICS_HARD_CAP) {
            return;
        }

        $reporters = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Worker', 'Supervisor']))
            ->get();

        $safetyOfficers = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Safety Officer'))
            ->get();

        $managers = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Manager'))
            ->get();

        $hodHsseUsers = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'HOD HSSE'))
            ->get();

        $apsbPdUsers = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'APSB PD'))
            ->get();

        $mrtsUsers = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'MRTS'))
            ->get();

        $commenters = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Worker', 'Supervisor', 'Safety Officer', 'Manager', 'HOD HSSE', 'APSB PD', 'MRTS']))
            ->get();

        if ($reporters->isEmpty() || $managers->isEmpty() || $hodHsseUsers->isEmpty() || $apsbPdUsers->isEmpty() || $mrtsUsers->isEmpty()) {
            return;
        }

        $incidentTypes = IncidentType::query()->active()->ordered()->get();
        $incidentStatuses = IncidentStatus::query()->active()->ordered()->get();
        $classifications = IncidentClassification::query()->active()->ordered()->get();
        $locations = IncidentLocation::query()->active()->ordered()->get();
        $workPackages = WorkPackage::query()->active()->ordered()->get();
        $subcontractors = Subcontractor::query()->active()->ordered()->get();
        $causeTypes = CauseType::query()->active()->ordered()->get();
        $victimTypes = VictimType::query()->active()->ordered()->get();
        $damageTypes = DamageType::query()->active()->ordered()->get();
        $factorTypes = FactorType::query()->active()->ordered()->get();
        $workActivities = WorkActivity::query()->active()->ordered()->get();
        $externalParties = ExternalParty::query()->active()->ordered()->get();
        $attachmentTypes = AttachmentType::query()->active()->ordered()->get();
        $attachmentCategories = AttachmentCategory::query()->active()->ordered()->get();

        if ($locations->isEmpty()) {
            $siteLocationType = LocationType::query()->firstOrCreate(
                ['code' => 'site'],
                ['name' => 'Site', 'is_active' => true, 'sort_order' => 0]
            );

            collect([
                ['code' => 'plant_a', 'name' => 'Plant A'],
                ['code' => 'warehouse_a', 'name' => 'Warehouse A'],
                ['code' => 'loading_dock', 'name' => 'Loading Dock'],
            ])->each(function (array $row, int $index) use ($siteLocationType): void {
                IncidentLocation::query()->firstOrCreate(
                    ['code' => $row['code']],
                    [
                        'name' => $row['name'],
                        'location_type_id' => $siteLocationType->id,
                        'is_active' => true,
                        'sort_order' => $index,
                    ]
                );
            });

            $locations = IncidentLocation::query()->active()->ordered()->get();
        }

        if ($workPackages->isEmpty()) {
            collect([
                ['code' => 'wp-civil', 'name' => 'Civil Works'],
                ['code' => 'wp-mech', 'name' => 'Mechanical Works'],
                ['code' => 'wp-elect', 'name' => 'Electrical Works'],
            ])->each(function (array $row, int $index): void {
                WorkPackage::query()->firstOrCreate(
                    ['code' => $row['code']],
                    ['name' => $row['name'], 'is_active' => true, 'sort_order' => $index]
                );
            });

            $workPackages = WorkPackage::query()->active()->ordered()->get();
        }

        if ($workActivities->isEmpty()) {
            collect([
                ['code' => 'wa-lifting', 'name' => 'Lifting Operation'],
                ['code' => 'wa-hot-work', 'name' => 'Hot Work'],
                ['code' => 'wa-confined', 'name' => 'Confined Space Work'],
            ])->each(function (array $row, int $index): void {
                WorkActivity::query()->firstOrCreate(
                    ['code' => $row['code']],
                    ['name' => $row['name'], 'is_active' => true, 'sort_order' => $index]
                );
            });

            $workActivities = WorkActivity::query()->active()->ordered()->get();
        }

        if ($incidentTypes->isEmpty()) {
            collect([
                ['code' => 'safety', 'name' => 'Safety Incident'],
                ['code' => 'environment', 'name' => 'Environmental Incident'],
                ['code' => 'property', 'name' => 'Property Damage Incident'],
            ])->each(function (array $row, int $index): void {
                IncidentType::query()->firstOrCreate(
                    ['code' => $row['code']],
                    ['name' => $row['name'], 'is_active' => true, 'sort_order' => $index]
                );
            });

            $incidentTypes = IncidentType::query()->active()->ordered()->get();
        }

        if ($classifications->isEmpty()) {
            collect([
                ['code' => 'minor', 'name' => 'Minor'],
                ['code' => 'major', 'name' => 'Major'],
                ['code' => 'critical', 'name' => 'Critical'],
            ])->each(function (array $row, int $index): void {
                IncidentClassification::query()->firstOrCreate(
                    ['code' => $row['code']],
                    ['name' => $row['name'], 'is_active' => true, 'sort_order' => $index]
                );
            });

            $classifications = IncidentClassification::query()->active()->ordered()->get();
        }

        if ($subcontractors->isEmpty()) {
            collect([
                ['code' => 'sub-alpha', 'name' => 'Alpha Industrial Services'],
                ['code' => 'sub-bravo', 'name' => 'Bravo Engineering'],
                ['code' => 'sub-charlie', 'name' => 'Charlie Maintenance'],
            ])->each(function (array $row, int $index) use ($faker): void {
                Subcontractor::query()->firstOrCreate(
                    ['code' => $row['code']],
                    [
                        'name' => $row['name'],
                        'contact_person' => $faker->name(),
                        'contact_number' => $faker->numerify('+60#########'),
                        'is_active' => true,
                        'sort_order' => $index,
                    ]
                );
            });

            $subcontractors = Subcontractor::query()->active()->ordered()->get();
        }

        if ($externalParties->isEmpty()) {
            collect([
                ['code' => 'ep-regulator', 'name' => 'Regulatory Agency'],
                ['code' => 'ep-client', 'name' => 'Client Representative'],
                ['code' => 'ep-vendor', 'name' => 'Equipment Vendor'],
            ])->each(function (array $row, int $index) use ($faker): void {
                ExternalParty::query()->firstOrCreate(
                    ['code' => $row['code']],
                    [
                        'name' => $row['name'],
                        'contact_person' => $faker->name(),
                        'contact_number' => $faker->numerify('+60#########'),
                        'is_active' => true,
                        'sort_order' => $index,
                    ]
                );
            });

            $externalParties = ExternalParty::query()->active()->ordered()->get();
        }

        $statuses = [
            'draft', 'draft', 'draft', 'draft',
            'draft_submitted', 'draft_submitted', 'draft_submitted', 'draft_submitted',
            'draft_reviewed', 'draft_reviewed', 'draft_reviewed', 'draft_reviewed',
            'final_submitted', 'final_submitted', 'final_submitted', 'final_submitted',
            'final_reviewed', 'final_reviewed', 'final_reviewed', 'final_reviewed',
            'pending_closure', 'pending_closure', 'pending_closure', 'pending_closure',
            'closed', 'closed', 'closed', 'closed',
        ];

        $maxStatusToSeed = max(0, min(count($statuses), $this->remainingIncidentCapacity() - 4));
        $statusesToSeed = array_slice($statuses, 0, $maxStatusToSeed);

        foreach ($statusesToSeed as $status) {
            $reporter = $reporters->random();
            $manager = $managers->random();
            $hodHsse = $hodHsseUsers->random();
            $apsbPd = $apsbPdUsers->random();
            $mrts = $mrtsUsers->random();

            $statusAgeDays = match ($status) {
                'draft' => random_int(0, 20),
                'draft_submitted' => random_int(3, 35),
                'draft_reviewed' => random_int(7, 50),
                'final_submitted' => random_int(15, 70),
                'final_reviewed' => random_int(20, 100),
                'pending_closure' => random_int(25, 130),
                'closed' => random_int(35, 170),
                default => random_int(1, 90),
            };

            $occurredAt = now()->subDays($statusAgeDays)->subHours(random_int(0, 20));
            $submittedAt = in_array($status, ['draft_submitted', 'draft_reviewed', 'final_submitted', 'final_reviewed', 'pending_closure', 'closed'], true)
                ? (clone $occurredAt)->addHours(random_int(1, 8))
                : null;
            $reviewedAt = in_array($status, ['draft_reviewed', 'final_submitted', 'final_reviewed', 'pending_closure', 'closed'], true)
                ? ($submittedAt ? (clone $submittedAt)->addHours(random_int(1, 24)) : now())
                : null;
            $finalSubmittedAt = in_array($status, ['final_submitted', 'final_reviewed', 'pending_closure', 'closed'], true)
                ? ($reviewedAt ? (clone $reviewedAt)->addHours(random_int(1, 12)) : now())
                : null;
            $finalReviewedAt = in_array($status, ['final_reviewed', 'pending_closure', 'closed'], true)
                ? ($finalSubmittedAt ? (clone $finalSubmittedAt)->addHours(random_int(1, 12)) : now())
                : null;
            $pendingClosureAt = in_array($status, ['pending_closure', 'closed'], true)
                ? ($finalReviewedAt ? (clone $finalReviewedAt)->addHours(random_int(1, 8)) : now())
                : null;
            $closedAt = $status === 'closed'
                ? ($pendingClosureAt ? (clone $pendingClosureAt)->addHours(random_int(1, 8)) : now())
                : null;

            $incidentCreatedAt = (clone $occurredAt)->subMinutes(random_int(5, 90));
            $incidentUpdatedAt = $closedAt
                ?? $pendingClosureAt
                ?? $finalReviewedAt
                ?? $finalSubmittedAt
                ?? $reviewedAt
                ?? $submittedAt
                ?? $incidentCreatedAt;

            $incidentType = $this->pickRandom($incidentTypes);
            $classification = $this->pickRandom($classifications);
            $reclassification = random_int(0, 10) > 7 ? $this->pickRandom($classifications) : null;
            $location = $this->pickRandom($locations);
            $workPackage = $this->pickRandom($workPackages);
            $workActivity = $this->pickRandom($workActivities);
            $subcontractor = $this->pickRandom($subcontractors);
            $rootCause = $this->pickRandom($causeTypes);
            $statusRecord = $incidentStatuses->firstWhere('code', $status);
            $description = $faker->sentence(16);
            $otherLocation = $location?->name ?? $faker->randomElement(['Warehouse A', 'Plant 1', 'Boiler Room', 'Loading Dock', 'Chemical Storage']);

            $incident = Incident::query()->create([
                'reported_by' => $reporter->id,
                'submitted_by' => $submittedAt ? $manager->id : null,
                'reviewed_by' => $reviewedAt ? $hodHsse->id : null,
                'approved_by' => $finalReviewedAt ? $mrts->id : null,
                'rejected_by' => null,
                'incident_reference_number' => $this->makeReferenceNumber(),
                'status' => $status,
                'status_id' => $statusRecord?->id,
                'incident_type_id' => $incidentType?->id,
                'description' => $description,
                'incident_description' => $description,
                'location' => $otherLocation,
                'location_id' => $location?->id,
                'location_type_id' => $location?->location_type_id,
                'other_location' => $otherLocation,
                'classification' => $classification?->name ?? $faker->randomElement(Incident::CLASSIFICATIONS),
                'classification_id' => $classification?->id,
                'reclassification_id' => $reclassification?->id,
                'work_package_id' => $workPackage?->id,
                'work_activity_id' => $workActivity?->id,
                'immediate_response' => $faker->sentence(12),
                'subcontractor_id' => $subcontractor?->id,
                'person_in_charge' => $faker->name(),
                'subcontractor_contact_number' => $subcontractor?->contact_number ?? $faker->numerify('+60#########'),
                'gps_location' => $faker->latitude().', '.$faker->longitude(),
                'activity_during_incident' => $faker->sentence(10),
                'type_of_accident' => $faker->randomElement(['Slip', 'Fall', 'Chemical Exposure', 'Near Miss', 'Equipment Contact']),
                'basic_effect' => $faker->sentence(8),
                'conclusion' => $faker->sentence(10),
                'close_remark' => $status === 'closed' ? $faker->sentence(8) : null,
                'rootcause_id' => $rootCause?->id,
                'other_rootcause' => random_int(0, 10) > 8 ? $faker->words(3, true) : null,
                'datetime' => $occurredAt,
                'incident_date' => $occurredAt->toDateString(),
                'incident_time' => $occurredAt->format('H:i:s'),
                'submitted_at' => $submittedAt,
                'reviewed_at' => $reviewedAt,
                'approved_at' => $closedAt ?? $finalReviewedAt,
                'rejected_at' => null,
                'rejection_reason' => null,
                'temporary_id' => (string) Str::uuid(),
                'local_created_at' => $incidentCreatedAt,
                'title' => 'Incident: '.$faker->randomElement([
                    'Forklift near miss',
                    'Chemical spill in utility room',
                    'PPE non-compliance at loading dock',
                    'Minor fire alarm activation',
                    'Slip hazard near wash area',
                ]),
                'created_at' => $incidentCreatedAt,
                'updated_at' => $incidentUpdatedAt,
            ]);

            $attachmentCount = random_int(1, 3);
            for ($i = 0; $i < $attachmentCount; $i++) {
                $ext = $faker->randomElement(['pdf', 'jpg', 'png']);
                $name = $faker->slug(3).'-'.$faker->numerify('####');
                $attachmentType = $this->pickRandom($attachmentTypes);
                $attachmentCategory = $this->pickRandom($attachmentCategories);

                IncidentAttachment::query()->create([
                    'incident_id' => $incident->id,
                    'attachment_type_id' => $attachmentType?->id,
                    'attachment_category_id' => $attachmentCategory?->id,
                    'original_name' => $name.'.'.$ext,
                    'filename' => $name.'.'.$ext,
                    'path' => 'incidents/'.$name.'.'.$ext,
                    'description' => $faker->sentence(6),
                    'mime_type' => $ext === 'pdf' ? 'application/pdf' : 'image/'.$ext,
                    'size' => random_int(75_000, 2_500_000),
                    'temporary_id' => (string) Str::uuid(),
                    'local_created_at' => (clone $occurredAt)->subMinutes(random_int(1, 30)),
                ]);
            }

            $commentCount = random_int(2, 5);
            for ($i = 0; $i < $commentCount; $i++) {
                $commenter = $commenters->random();
                $commentText = $faker->sentence(14);
                $commentType = $faker->randomElement(['general', 'investigation', 'action', 'review']);

                if ($status === 'closed' && $i === 0) {
                    $commenter = $mrts;
                    $commentText = 'Final review completed and closure confirmed after all actions were verified.';
                    $commentType = 'review';
                }

                $comment = IncidentComment::query()->create([
                    'incident_id' => $incident->id,
                    'user_id' => $commenter->id,
                    'comment_type' => $commentType,
                    'comment' => $commentText,
                    'temporary_id' => (string) Str::uuid(),
                    'local_created_at' => now()->subDays(random_int(0, 15)),
                    'created_at' => now()->subDays(random_int(0, 15)),
                    'updated_at' => now()->subDays(random_int(0, 15)),
                ]);

                if (random_int(0, 10) > 7) {
                    IncidentCommentReply::query()->create([
                        'incident_comment_id' => $comment->id,
                        'user_id' => $commenters->random()->id,
                        'reply' => $faker->sentence(10),
                        'temporary_id' => (string) Str::uuid(),
                        'local_created_at' => now()->subDays(random_int(0, 10)),
                    ]);
                }
            }

            $this->seedIncidentAggregate(
                $incident,
                $faker,
                $victimTypes,
                $damageTypes,
                $causeTypes,
                $factorTypes,
                $workActivities,
                $externalParties,
                false
            );

            if (in_array($status, ['draft_reviewed', 'final_submitted', 'final_reviewed', 'pending_closure', 'closed'], true)) {
                IncidentApproval::query()->create([
                    'incident_id' => $incident->id,
                    'approver_id' => $hodHsse->id,
                    'approver_role' => 'HOD HSSE',
                    'decision' => 'approved',
                    'remarks' => 'Draft reviewed and validated by HOD HSSE.',
                    'decided_at' => $reviewedAt ? (clone $reviewedAt)->addMinutes(30) : now(),
                ]);
            }

            if (in_array($status, ['final_submitted', 'final_reviewed', 'pending_closure', 'closed'], true)) {
                IncidentApproval::query()->create([
                    'incident_id' => $incident->id,
                    'approver_id' => $apsbPd->id,
                    'approver_role' => 'APSB PD',
                    'decision' => 'approved',
                    'remarks' => 'Final submission confirmed by APSB PD.',
                    'decided_at' => $finalSubmittedAt ? (clone $finalSubmittedAt)->addMinutes(30) : now(),
                ]);
            }

            if (in_array($status, ['final_reviewed', 'pending_closure', 'closed'], true)) {
                IncidentApproval::query()->create([
                    'incident_id' => $incident->id,
                    'approver_id' => $mrts->id,
                    'approver_role' => 'MRTS',
                    'decision' => 'approved',
                    'remarks' => 'Final review accepted by MRTS.',
                    'decided_at' => $finalReviewedAt ? (clone $finalReviewedAt)->addMinutes(30) : now(),
                ]);
            }

            if (in_array($status, ['pending_closure', 'closed'], true)) {
                IncidentApproval::query()->create([
                    'incident_id' => $incident->id,
                    'approver_id' => $hodHsse->id,
                    'approver_role' => 'HOD HSSE',
                    'decision' => 'approved',
                    'remarks' => 'Pending closure stage confirmed by HOD HSSE.',
                    'decided_at' => $pendingClosureAt ? (clone $pendingClosureAt)->addMinutes(30) : now(),
                ]);
            }

            if ($status === 'closed') {
                IncidentApproval::query()->create([
                    'incident_id' => $incident->id,
                    'approver_id' => $mrts->id,
                    'approver_role' => 'MRTS',
                    'decision' => 'approved',
                    'remarks' => 'Incident officially closed by MRTS.',
                    'decided_at' => $closedAt ?? now(),
                ]);
            }
        }

        // Scenario set: 3 high-risk incidents with full workflow and corrective action trail.
        $maxHighRiskScenarios = min(3, max(0, $this->remainingIncidentCapacity() - 1));
        for ($i = 1; $i <= $maxHighRiskScenarios; $i++) {
            $reporter = $reporters->random();
            $manager = $managers->random();
            $hodHsse = $hodHsseUsers->random();
            $apsbPd = $apsbPdUsers->random();
            $mrts = $mrtsUsers->random();

            $occurredAt = now()->subDays(random_int(10, 25))->subHours(random_int(2, 10));
            $submittedAt = (clone $occurredAt)->addHours(random_int(1, 3));
            $reviewedAt = (clone $submittedAt)->addHours(random_int(2, 8));
            $finalSubmittedAt = (clone $reviewedAt)->addHours(random_int(2, 6));
            $finalReviewedAt = (clone $finalSubmittedAt)->addHours(random_int(2, 10));
            $pendingClosureAt = (clone $finalReviewedAt)->addHours(random_int(2, 6));
            $closedAt = (clone $pendingClosureAt)->addHours(random_int(2, 6));
            $correctiveActionAt = (clone $closedAt)->addHours(random_int(6, 24));

            $incidentType = $this->pickRandom($incidentTypes);
            $critical = $classifications->firstWhere('code', 'critical') ?? $this->pickRandom($classifications);
            $location = $this->pickRandom($locations);
            $statusRecord = $incidentStatuses->firstWhere('code', 'closed');
            $rootCause = $this->pickRandom($causeTypes);
            $workPackage = $this->pickRandom($workPackages);
            $workActivity = $this->pickRandom($workActivities);
            $subcontractor = $this->pickRandom($subcontractors);
            $locationLabel = $location?->name ?? $faker->randomElement(['Warehouse A', 'Plant 1', 'Boiler Room', 'Loading Dock', 'Chemical Storage']);

            $incident = Incident::query()->create([
                'reported_by' => $reporter->id,
                'submitted_by' => $manager->id,
                'reviewed_by' => $hodHsse->id,
                'approved_by' => $mrts->id,
                'rejected_by' => null,
                'incident_reference_number' => $this->makeReferenceNumber(),
                'title' => 'High Risk: '.$faker->randomElement([
                    'Ammonia leak near compressor bay',
                    'Scaffold collapse near loading area',
                    'Electrical panel arc flash event',
                ]),
                'incident_type_id' => $incidentType?->id,
                'classification' => $critical?->name ?? 'Critical',
                'classification_id' => $critical?->id,
                'status_id' => $statusRecord?->id,
                'location' => $locationLabel,
                'location_id' => $location?->id,
                'location_type_id' => $location?->location_type_id,
                'other_location' => $locationLabel,
                'status' => 'closed',
                'work_package_id' => $workPackage?->id,
                'work_activity_id' => $workActivity?->id,
                'incident_description' => 'Critical incident requiring immediate controls, executive visibility, and post-approval corrective actions.',
                'immediate_response' => 'Emergency stop initiated and area isolated.',
                'subcontractor_id' => $subcontractor?->id,
                'person_in_charge' => $faker->name(),
                'subcontractor_contact_number' => $subcontractor?->contact_number ?? $faker->numerify('+60#########'),
                'gps_location' => $faker->latitude().', '.$faker->longitude(),
                'activity_during_incident' => $faker->sentence(10),
                'type_of_accident' => $faker->randomElement(['Explosion Risk', 'Structural Failure', 'Arc Flash']),
                'basic_effect' => 'Operations temporarily suspended.',
                'conclusion' => 'Requires system-level engineering controls.',
                'close_remark' => 'Closed with mandatory follow-up actions.',
                'rootcause_id' => $rootCause?->id,
                'datetime' => $occurredAt,
                'incident_date' => $occurredAt->toDateString(),
                'incident_time' => $occurredAt->format('H:i:s'),
                'submitted_at' => $submittedAt,
                'reviewed_at' => $reviewedAt,
                'approved_at' => $closedAt,
                'rejected_at' => null,
                'rejection_reason' => null,
                'description' => 'Critical incident requiring immediate controls, executive visibility, and post-approval corrective actions.',
                'temporary_id' => (string) Str::uuid(),
                'local_created_at' => (clone $occurredAt)->subMinutes(random_int(10, 90)),
                'created_at' => (clone $occurredAt)->subMinutes(random_int(20, 120)),
                'updated_at' => $closedAt,
            ]);

            foreach (['initial-scene-photo.jpg', 'investigation-report.pdf', 'corrective-action-plan.pdf'] as $doc) {
                $attachmentType = $this->pickRandom($attachmentTypes);
                $attachmentCategory = $this->pickRandom($attachmentCategories);
                IncidentAttachment::query()->create([
                    'incident_id' => $incident->id,
                    'attachment_type_id' => $attachmentType?->id,
                    'attachment_category_id' => $attachmentCategory?->id,
                    'original_name' => $doc,
                    'filename' => $doc,
                    'path' => 'incidents/high-risk-'.$incident->id.'-'.$doc,
                    'description' => 'High-risk incident supporting document',
                    'mime_type' => str_ends_with($doc, '.pdf') ? 'application/pdf' : 'image/jpeg',
                    'size' => random_int(150_000, 2_200_000),
                    'temporary_id' => (string) Str::uuid(),
                    'local_created_at' => (clone $occurredAt)->subMinutes(random_int(1, 30)),
                ]);
            }

            $comment = IncidentComment::query()->create([
                'incident_id' => $incident->id,
                'user_id' => $reporter->id,
                'comment_type' => 'investigation',
                'comment' => 'Initial emergency controls applied and area isolated.',
                'temporary_id' => (string) Str::uuid(),
                'local_created_at' => $submittedAt,
                'created_at' => $submittedAt,
                'updated_at' => $submittedAt,
            ]);

            IncidentCommentReply::query()->create([
                'incident_comment_id' => $comment->id,
                'user_id' => $hodHsse->id,
                'reply' => 'Confirmed. Continue evidence capture and stabilize process conditions.',
                'temporary_id' => (string) Str::uuid(),
                'local_created_at' => (clone $submittedAt)->addMinutes(30),
            ]);

            IncidentComment::query()->create([
                'incident_id' => $incident->id,
                'user_id' => $hodHsse->id,
                'comment_type' => 'review',
                'comment' => 'Draft review completed; forwarding for final submission.',
                'temporary_id' => (string) Str::uuid(),
                'local_created_at' => $reviewedAt,
                'created_at' => $reviewedAt,
                'updated_at' => $reviewedAt,
            ]);

            IncidentComment::query()->create([
                'incident_id' => $incident->id,
                'user_id' => $apsbPd->id,
                'comment_type' => 'action',
                'comment' => 'Final submission confirmed. Proceeding to MRTS final review.',
                'temporary_id' => (string) Str::uuid(),
                'local_created_at' => $finalSubmittedAt,
                'created_at' => $finalSubmittedAt,
                'updated_at' => $finalSubmittedAt,
            ]);

            IncidentComment::query()->create([
                'incident_id' => $incident->id,
                'user_id' => $mrts->id,
                'comment_type' => 'action',
                'comment' => 'Final review passed. Closure completed and corrective action opened.',
                'temporary_id' => (string) Str::uuid(),
                'local_created_at' => $correctiveActionAt,
                'created_at' => $correctiveActionAt,
                'updated_at' => $correctiveActionAt,
            ]);

            $this->seedIncidentAggregate(
                $incident,
                $faker,
                $victimTypes,
                $damageTypes,
                $causeTypes,
                $factorTypes,
                $workActivities,
                $externalParties,
                true
            );

            IncidentApproval::query()->create([
                'incident_id' => $incident->id,
                'approver_id' => $hodHsse->id,
                'approver_role' => 'HOD HSSE',
                'decision' => 'approved',
                'remarks' => 'Technical draft review complete and controls validated.',
                'decided_at' => (clone $reviewedAt)->addMinutes(30),
            ]);

            IncidentApproval::query()->create([
                'incident_id' => $incident->id,
                'approver_id' => $apsbPd->id,
                'approver_role' => 'APSB PD',
                'decision' => 'approved',
                'remarks' => 'Final submission acknowledged with mandatory corrective action follow-up.',
                'decided_at' => $finalSubmittedAt,
            ]);

            IncidentApproval::query()->create([
                'incident_id' => $incident->id,
                'approver_id' => $mrts->id,
                'approver_role' => 'MRTS',
                'decision' => 'approved',
                'remarks' => 'Final review and closure completed.',
                'decided_at' => $closedAt,
            ]);

            AuditLog::query()->create([
                'user_id' => $mrts->id,
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

        // Scenario: escalation when incident is not closed within 3 days.
        if ($this->remainingIncidentCapacity() > 0) {
            $escalationReporter = $reporters->random();
            $escalationManager = $managers->random();
            $escalationHodHsse = $hodHsseUsers->random();
            $escalationApsbPd = $apsbPdUsers->random();
            $escalationMrts = $mrtsUsers->random();
            $escalationOccurredAt = now()->subDays(8);
            $escalationSubmittedAt = (clone $escalationOccurredAt)->addHours(2);
            $escalatedAt = (clone $escalationSubmittedAt)->addDays(3)->addHours(2);
            $escalationFinalSubmittedAt = (clone $escalatedAt)->addHours(6);
            $escalationFinalReviewedAt = (clone $escalationFinalSubmittedAt)->addHours(6);
            $escalationPendingClosureAt = (clone $escalationFinalReviewedAt)->addHours(4);
            $escalationClosedAt = (clone $escalationPendingClosureAt)->addHours(4);

            $major = $classifications->firstWhere('code', 'major') ?? $this->pickRandom($classifications);
            $location = $this->pickRandom($locations);
            $workPackage = $this->pickRandom($workPackages);
            $workActivity = $this->pickRandom($workActivities);
            $subcontractor = $this->pickRandom($subcontractors);
            $rootCause = $this->pickRandom($causeTypes);
            $statusRecord = $incidentStatuses->firstWhere('code', 'closed');
            $incidentType = $this->pickRandom($incidentTypes);
            $locationLabel = $location?->name ?? $faker->randomElement(['Warehouse A', 'Plant 1', 'Boiler Room', 'Loading Dock', 'Chemical Storage']);

            $escalatedIncident = Incident::query()->create([
                'reported_by' => $escalationReporter->id,
                'submitted_by' => $escalationManager->id,
                'reviewed_by' => $escalationHodHsse->id,
                'approved_by' => $escalationMrts->id,
                'incident_reference_number' => $this->makeReferenceNumber(),
                'incident_type_id' => $incidentType?->id,
                'status_id' => $statusRecord?->id,
                'classification_id' => $major?->id,
                'work_package_id' => $workPackage?->id,
                'location_id' => $location?->id,
                'location_type_id' => $location?->location_type_id,
                'work_activity_id' => $workActivity?->id,
                'other_location' => $locationLabel,
                'incident_description' => 'Closure SLA breached; escalated after 3 days pending final workflow stages.',
                'immediate_response' => 'Temporary controls remained active while approval was pending.',
                'subcontractor_id' => $subcontractor?->id,
                'person_in_charge' => $faker->name(),
                'subcontractor_contact_number' => $subcontractor?->contact_number ?? $faker->numerify('+60#########'),
                'gps_location' => $faker->latitude().', '.$faker->longitude(),
                'activity_during_incident' => $faker->sentence(10),
                'type_of_accident' => 'Confined Space Permit Delay',
                'basic_effect' => 'Escalation required due to SLA breach.',
                'conclusion' => 'Workflow escalation policy effective and traceable.',
                'close_remark' => 'Closed post-escalation workflow completion.',
                'rootcause_id' => $rootCause?->id,
                'status' => 'closed',
                'datetime' => $escalationOccurredAt,
                'incident_date' => $escalationOccurredAt->toDateString(),
                'incident_time' => $escalationOccurredAt->format('H:i:s'),
                'submitted_at' => $escalationSubmittedAt,
                'reviewed_at' => $escalatedAt,
                'approved_at' => $escalationClosedAt,
                'title' => 'Escalation Case: Delayed approval for confined space incident',
                'location' => $locationLabel,
                'classification' => $major?->name ?? 'Major',
                'description' => 'Closure SLA breached; escalated after 3 days pending final workflow stages.',
                'temporary_id' => (string) Str::uuid(),
                'local_created_at' => (clone $escalationOccurredAt)->subMinutes(random_int(15, 60)),
                'created_at' => (clone $escalationOccurredAt)->subMinutes(random_int(20, 90)),
                'updated_at' => $escalationClosedAt,
            ]);

            IncidentComment::query()->create([
                'incident_id' => $escalatedIncident->id,
                'user_id' => $escalationHodHsse->id,
                'comment_type' => 'review',
                'comment' => 'No closure after 72 hours; escalating per workflow SLA.',
                'temporary_id' => (string) Str::uuid(),
                'local_created_at' => $escalatedAt,
                'created_at' => $escalatedAt,
                'updated_at' => $escalatedAt,
            ]);

            $this->seedIncidentAggregate(
                $escalatedIncident,
                $faker,
                $victimTypes,
                $damageTypes,
                $causeTypes,
                $factorTypes,
                $workActivities,
                $externalParties,
                true
            );

            IncidentApproval::query()->create([
                'incident_id' => $escalatedIncident->id,
                'approver_id' => $escalationHodHsse->id,
                'approver_role' => 'HOD HSSE',
                'decision' => 'approved',
                'remarks' => 'Draft review done; pending final submission sign-off.',
                'decided_at' => $escalatedAt,
            ]);

            IncidentApproval::query()->create([
                'incident_id' => $escalatedIncident->id,
                'approver_id' => $escalationApsbPd->id,
                'approver_role' => 'APSB PD',
                'decision' => 'approved',
                'remarks' => 'Final submission approved post-escalation.',
                'decided_at' => $escalationFinalSubmittedAt,
            ]);

            IncidentApproval::query()->create([
                'incident_id' => $escalatedIncident->id,
                'approver_id' => $escalationMrts->id,
                'approver_role' => 'MRTS',
                'decision' => 'approved',
                'remarks' => 'Final review and closure completed post-escalation.',
                'decided_at' => $escalationClosedAt,
            ]);

            AuditLog::query()->create([
                'user_id' => $escalationHodHsse->id,
                'module' => 'incidents',
                'action' => 'escalate',
                'auditable_type' => Incident::class,
                'auditable_id' => $escalatedIncident->id,
                'metadata' => [
                    'description' => 'Incident escalated after >3 days pending workflow closure.',
                    'hours_to_escalation' => 74,
                    'seeded' => true,
                ],
                'created_at' => $escalatedAt,
                'updated_at' => $escalatedAt,
            ]);
        }

        $this->seedAnalyticsVolumeIncidents(
            self::ANALYTICS_TARGET_TOTAL,
            $reporters,
            $managers,
            $hodHsseUsers,
            $mrtsUsers,
            $incidentTypes,
            $classifications,
            $locations,
            $workPackages,
            $workActivities,
            $subcontractors,
            $causeTypes,
            $incidentStatuses
        );
    }

    private function makeReferenceNumber(): string
    {
        do {
            $reference = 'INC-SD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Incident::query()->where('incident_reference_number', $reference)->exists());

        return $reference;
    }

    private function pickRandom(Collection $items): mixed
    {
        return $items->isNotEmpty() ? $items->random() : null;
    }

    /**
     * @return array<int, int>
     */
    private function randomSubsetIds(Collection $items, int $max): array
    {
        if ($items->isEmpty()) {
            return [];
        }

        return $items
            ->shuffle()
            ->take(random_int(1, min($max, $items->count())))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function seedIncidentAggregate(
        Incident $incident,
        mixed $faker,
        Collection $victimTypes,
        Collection $damageTypes,
        Collection $causeTypes,
        Collection $factorTypes,
        Collection $workActivities,
        Collection $externalParties,
        bool $rich
    ): void {
        $baseTime = $incident->datetime ?? now();
        $chronologyCount = $rich ? 3 : random_int(1, 2);

        for ($index = 0; $index < $chronologyCount; $index++) {
            $eventAt = (clone $baseTime)->addMinutes(($index + 1) * 20);
            $incident->chronologies()->create([
                'event_date' => $eventAt->toDateString(),
                'event_time' => $eventAt->format('H:i:s'),
                'events' => $faker->sentence(14),
                'sort_order' => $index,
                'temporary_id' => (string) Str::uuid(),
                'local_created_at' => $eventAt,
            ]);
        }

        $victimRows = $rich ? random_int(1, 2) : random_int(0, 1);
        for ($index = 0; $index < $victimRows; $index++) {
            $victimType = $this->pickRandom($victimTypes);
            $incident->victims()->create([
                'victim_type_id' => $victimType?->id,
                'name' => $faker->name(),
                'identification' => strtoupper($faker->bothify('ID####??')),
                'occupation' => $faker->jobTitle(),
                'age' => random_int(20, 58),
                'nationality' => $faker->country(),
                'working_experience' => random_int(1, 20).' years',
                'nature_of_injury' => $faker->randomElement(['Bruise', 'Sprain', 'Cut', 'Burn', 'No injury']),
                'body_injured' => $faker->randomElement(['Hand', 'Leg', 'Shoulder', 'Back', 'None']),
                'treatment' => $faker->sentence(8),
                'temporary_id' => (string) Str::uuid(),
                'local_created_at' => now()->subDays(random_int(0, 20)),
            ]);
        }

        $witnessRows = $rich ? random_int(1, 2) : random_int(0, 1);
        for ($index = 0; $index < $witnessRows; $index++) {
            $incident->witnesses()->create([
                'name' => $faker->name(),
                'designation' => $faker->jobTitle(),
                'identification' => strtoupper($faker->bothify('WIT###??')),
                'temporary_id' => (string) Str::uuid(),
                'local_created_at' => now()->subDays(random_int(0, 20)),
            ]);
        }

        $teamRows = $rich ? random_int(2, 3) : random_int(1, 2);
        for ($index = 0; $index < $teamRows; $index++) {
            $incident->investigationTeamMembers()->create([
                'name' => $faker->name(),
                'designation' => $faker->jobTitle(),
                'contact_number' => $faker->numerify('+60#########'),
                'company' => $faker->company(),
                'temporary_id' => (string) Str::uuid(),
                'local_created_at' => now()->subDays(random_int(0, 20)),
            ]);
        }

        $damageRows = $rich ? random_int(1, 2) : random_int(0, 1);
        for ($index = 0; $index < $damageRows; $index++) {
            $damageType = $this->pickRandom($damageTypes);
            $incident->damages()->create([
                'damage_type_id' => $damageType?->id,
                'estimate_cost' => random_int(500, 15000),
                'temporary_id' => (string) Str::uuid(),
                'local_created_at' => now()->subDays(random_int(0, 20)),
            ]);
        }

        $incident->immediateActions()->create([
            'action_taken' => $faker->sentence(10),
            'company' => $faker->company(),
            'temporary_id' => (string) Str::uuid(),
            'local_created_at' => now()->subDays(random_int(0, 20)),
        ]);

        $incident->plannedActions()->create([
            'action_taken' => $faker->sentence(10),
            'expected_date' => now()->addDays(random_int(5, 20))->toDateString(),
            'actual_date' => $rich ? now()->addDays(random_int(21, 35))->toDateString() : null,
            'temporary_id' => (string) Str::uuid(),
            'local_created_at' => now()->subDays(random_int(0, 20)),
        ]);

        $incident->immediateCauses()->sync($this->randomSubsetIds($causeTypes, 3));
        $incident->contributingFactors()->sync($this->randomSubsetIds($factorTypes, 3));
        $incident->workActivities()->sync($this->randomSubsetIds($workActivities, 3));
        $incident->externalParties()->sync($this->randomSubsetIds($externalParties, 3));
    }

    private function seedAnalyticsVolumeIncidents(
        int $targetTotal,
        Collection $reporters,
        Collection $managers,
        Collection $hodHsseUsers,
        Collection $mrtsUsers,
        Collection $incidentTypes,
        Collection $classifications,
        Collection $locations,
        Collection $workPackages,
        Collection $workActivities,
        Collection $subcontractors,
        Collection $causeTypes,
        Collection $incidentStatuses
    ): void {
        $hardCap = self::ANALYTICS_HARD_CAP;
        $current = Incident::query()->count();
        $toCreate = max(0, min($targetTotal, $hardCap) - $current);

        if ($toCreate <= 0) {
            return;
        }

        $statusWeights = [
            'draft' => 22,
            'draft_submitted' => 18,
            'draft_reviewed' => 16,
            'final_submitted' => 14,
            'final_reviewed' => 12,
            'pending_closure' => 8,
            'closed' => 10,
        ];

        $monthWeights = [
            1 => 0.8,
            2 => 0.9,
            3 => 1.2,
            4 => 1.0,
            5 => 1.15,
            6 => 1.35,
            7 => 1.25,
            8 => 0.85,
            9 => 0.95,
            10 => 1.3,
            11 => 1.1,
            12 => 0.7,
        ];

        Incident::factory()
            ->count($toCreate)
            ->sequence(function (Sequence $sequence) use (
                $statusWeights,
                $monthWeights,
                $reporters,
                $managers,
                $hodHsseUsers,
                $mrtsUsers,
                $incidentTypes,
                $classifications,
                $locations,
                $workPackages,
                $workActivities,
                $subcontractors,
                $causeTypes,
                $incidentStatuses
            ): array {
                $status = $this->weightedChoice($statusWeights);
                $occurredAt = $this->sampleIncidentDate($monthWeights);
                $submittedAt = in_array($status, ['draft_submitted', 'draft_reviewed', 'final_submitted', 'final_reviewed', 'pending_closure', 'closed'], true)
                    ? (clone $occurredAt)->addHours(random_int(2, 18))
                    : null;
                $reviewedAt = in_array($status, ['draft_reviewed', 'final_submitted', 'final_reviewed', 'pending_closure', 'closed'], true)
                    ? ($submittedAt ? (clone $submittedAt)->addHours(random_int(2, 24)) : null)
                    : null;
                $closedAt = $status === 'closed'
                    ? ($reviewedAt ? (clone $reviewedAt)->addHours(random_int(6, 48)) : (clone $occurredAt)->addDays(random_int(3, 8)))
                    : null;

                $location = $this->pickRandom($locations);
                $classification = $this->pickRandom($classifications);
                $workPackage = $this->pickRandom($workPackages);
                $workActivity = $this->pickRandom($workActivities);
                $incidentType = $this->pickRandom($incidentTypes);
                $subcontractor = $this->pickRandom($subcontractors);
                $rootCause = $this->pickRandom($causeTypes);
                $statusRecord = $incidentStatuses->firstWhere('code', $status);
                $reporter = $this->pickRandom($reporters);

                return [
                    'reported_by' => $reporter?->id,
                    'submitted_by' => $submittedAt ? $this->pickRandom($managers)?->id : null,
                    'reviewed_by' => $reviewedAt ? $this->pickRandom($hodHsseUsers)?->id : null,
                    'approved_by' => $closedAt ? $this->pickRandom($mrtsUsers)?->id : null,
                    'incident_reference_number' => $this->makeReferenceNumber(),
                    'status' => $status,
                    'status_id' => $statusRecord?->id,
                    'incident_type_id' => $incidentType?->id,
                    'classification' => $classification?->name ?? 'Minor',
                    'classification_id' => $classification?->id,
                    'location' => $location?->name,
                    'location_id' => $location?->id,
                    'location_type_id' => $location?->location_type_id,
                    'other_location' => $location?->name,
                    'work_package_id' => $workPackage?->id,
                    'work_activity_id' => $workActivity?->id,
                    'subcontractor_id' => $subcontractor?->id,
                    'subcontractor_contact_number' => $subcontractor?->contact_number,
                    'rootcause_id' => $rootCause?->id,
                    'datetime' => $occurredAt,
                    'incident_date' => $occurredAt->toDateString(),
                    'incident_time' => $occurredAt->format('H:i:s'),
                    'submitted_at' => $submittedAt,
                    'reviewed_at' => $reviewedAt,
                    'approved_at' => $closedAt,
                    'created_at' => (clone $occurredAt)->subMinutes(random_int(10, 80)),
                    'updated_at' => $closedAt ?? (clone $occurredAt)->addHours(random_int(1, 72)),
                ];
            })
            ->state(fn (array $attrs): array => [
                'title' => 'Patterned incident #'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
                'description' => $attrs['description'] ?? 'Seeded for analytics trend realism.',
                'incident_description' => $attrs['incident_description'] ?? 'Seeded for analytics trend realism.',
            ])
            ->create();
    }

    private function remainingIncidentCapacity(): int
    {
        return max(0, self::ANALYTICS_HARD_CAP - Incident::query()->count());
    }

    /**
     * @param array<string,int|float> $weights
     */
    private function weightedChoice(array $weights): string
    {
        $total = array_sum($weights);
        $point = mt_rand(1, (int) max(1, $total));
        $cursor = 0;

        foreach ($weights as $value => $weight) {
            $cursor += (int) $weight;
            if ($point <= $cursor) {
                return (string) $value;
            }
        }

        return (string) array_key_first($weights);
    }

    /**
     * @param array<int,float> $monthWeights
     */
    private function sampleIncidentDate(array $monthWeights): Carbon
    {
        while (true) {
            $date = now()
                ->subDays(random_int(0, 360))
                ->startOfDay()
                ->addHours(random_int(6, 20))
                ->addMinutes(random_int(0, 59));

            $weekdayWeight = in_array($date->dayOfWeekIso, [6, 7], true) ? 0.35 : 1.0;
            $monthWeight = $monthWeights[$date->month] ?? 1.0;
            $acceptance = min(0.98, ($weekdayWeight * $monthWeight) / 1.4);

            if (mt_rand(1, 1000) <= (int) round($acceptance * 1000)) {
                return $date;
            }
        }
    }
}


