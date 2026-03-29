<?php

namespace Tests\Feature;

use App\Events\TrainingExpiryDetected;
use App\Models\Certificate;
use App\Models\Incident;
use App\Models\IncidentClassification;
use App\Models\IncidentLocation;
use App\Models\IncidentType;
use App\Models\LocationType;
use App\Models\Role;
use App\Models\Subcontractor;
use App\Models\Training;
use App\Models\User;
use App\Models\WorkActivity;
use App\Models\WorkPackage;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationEventIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_incident_submission_dispatches_incident_submitted_event(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $reporter = User::factory()->create();
        $manager = User::factory()->create();

        $workerRole = Role::query()->where('name', 'Worker')->firstOrFail();
        $managerRole = Role::query()->where('name', 'Manager')->firstOrFail();

        $reporter->roles()->attach($workerRole->id);
        $manager->roles()->attach($managerRole->id);

        $incident = Incident::query()->create($this->requiredIncidentAttributes($reporter, [
            'reported_by' => $reporter->id,
            'title' => 'Workflow transition check',
            'description' => 'Verify transition writes workflow log.',
            'location' => 'Test Site',
            'datetime' => now(),
            'classification' => 'Major',
            'status' => 'draft',
        ]));

        $this->actingAs($manager)
            ->post(route('incidents.transition', $incident), [
                'to_status' => 'draft_submitted',
                'remarks' => 'Manager transitioned draft.',
            ])
            ->assertRedirect(route('incidents.show', $incident));

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'status' => 'draft_submitted',
        ]);

        $this->assertDatabaseHas('incident_workflow_logs', [
            'incident_id' => $incident->id,
            'from_status' => 'draft',
            'to_status' => 'draft_submitted',
        ]);
    }

    public function test_partial_incident_approval_dispatches_approval_required_event(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $reporter = User::factory()->create();
        $manager = User::factory()->create();
        $hod = User::factory()->create();

        $workerRole = Role::query()->where('name', 'Worker')->firstOrFail();
        $managerRole = Role::query()->where('name', 'Manager')->firstOrFail();
        $hodRole = Role::query()->firstOrCreate(['name' => 'HOD HSSE'], ['slug' => 'hod-hsse', 'description' => 'HOD HSSE role']);

        $reporter->roles()->attach($workerRole->id);
        $manager->roles()->attach($managerRole->id);
        $hod->roles()->attach($hodRole->id);

        $incident = Incident::query()->create($this->requiredIncidentAttributes($reporter, [
            'title' => 'Unauthorized transition check',
            'description' => 'Verify invalid transition is rejected.',
            'location' => 'Unit 8',
            'datetime' => now(),
            'classification' => 'Critical',
            'status' => 'draft',
        ]));

        $this->actingAs($manager)
            ->post(route('incidents.transition', $incident), ['to_status' => 'draft_submitted'])
            ->assertRedirect(route('incidents.show', $incident));

        $this->actingAs($hod)
            ->post(route('incidents.transition', $incident), ['to_status' => 'draft_reviewed'])
            ->assertRedirect(route('incidents.show', $incident));

        $this->actingAs($hod)
            ->from(route('incidents.show', $incident))
            ->post(route('incidents.transition', $incident), ['to_status' => 'closed'])
            ->assertRedirect(route('incidents.show', $incident))
            ->assertSessionHasErrors('status');
    }

    public function test_expiring_certificate_scan_dispatches_training_expiry_event(): void
    {
        Event::fake([TrainingExpiryDetected::class]);

        $user = User::factory()->create();
        $training = Training::query()->create([
            'title' => 'Forklift Safety',
            'certificate_validity_days' => 365,
            'is_active' => true,
        ]);

        $certificate = Certificate::query()->create([
            'training_id' => $training->id,
            'user_id' => $user->id,
            'file_path' => 'certificates/forklift.pdf',
            'original_name' => 'forklift.pdf',
            'size' => 1200,
            'issued_at' => now()->subMonths(11)->toDateString(),
            'expires_at' => now()->addDays(7)->toDateString(),
        ]);

        Artisan::call('trainings:notify-expiring-certificates', ['--days' => 30]);

        Event::assertDispatched(TrainingExpiryDetected::class, function (TrainingExpiryDetected $event) use ($certificate): bool {
            return $event->certificate->is($certificate);
        });
    }

    private function requiredIncidentAttributes(User $reporter, array $overrides = []): array
    {
        $locationType = LocationType::query()->firstOrCreate(
            ['code' => 'LT-TST'],
            ['name' => 'Test Location Type', 'is_active' => true]
        );

        $location = IncidentLocation::query()->firstOrCreate(
            ['code' => 'LOC-TST'],
            ['name' => 'Test Location', 'location_type_id' => $locationType->id, 'is_active' => true]
        );

        if ((int) $location->location_type_id !== (int) $locationType->id) {
            $location->update(['location_type_id' => $locationType->id]);
        }

        $incidentType = IncidentType::query()->firstOrCreate(
            ['code' => 'IT-TST'],
            ['name' => 'Test Incident Type', 'is_active' => true]
        );

        $classification = IncidentClassification::query()->firstOrCreate(
            ['code' => 'IC-TST'],
            ['name' => 'Test Classification', 'is_active' => true]
        );

        $workPackage = WorkPackage::query()->firstOrCreate(
            ['code' => 'WP-TST'],
            ['name' => 'Test Work Package', 'is_active' => true]
        );

        $workActivity = WorkActivity::query()->firstOrCreate(
            ['code' => 'WA-TST'],
            ['name' => 'Test Work Activity', 'is_active' => true]
        );

        $subcontractor = Subcontractor::query()->first();

        return array_merge([
            'reported_by' => $reporter->id,
            'incident_reference_number' => 'INC-TST-'.Str::upper(Str::random(8)),
            'title' => 'Test Incident',
            'description' => 'Test incident description.',
            'incident_description' => 'Test incident description.',
            'incident_type_id' => $incidentType->id,
            'location_type_id' => $locationType->id,
            'location_id' => $location->id,
            'location' => $location->name,
            'other_location' => $location->name,
            'datetime' => now(),
            'incident_date' => now()->toDateString(),
            'incident_time' => now()->format('H:i:s'),
            'classification' => 'Major',
            'classification_id' => $classification->id,
            'status' => 'draft',
            'work_package_id' => $workPackage->id,
            'work_activity_id' => $workActivity->id,
            'immediate_response' => 'Immediate response executed.',
            'subcontractor_id' => $subcontractor?->id,
            'person_in_charge' => 'Test PIC',
            'subcontractor_contact_number' => '+60123456789',
        ], $overrides);
    }
}
