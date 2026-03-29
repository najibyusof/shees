<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\IncidentClassification;
use App\Models\IncidentLocation;
use App\Models\IncidentType;
use App\Models\LocationType;
use App\Models\Role;
use App\Models\Subcontractor;
use App\Models\User;
use App\Models\WorkActivity;
use App\Models\WorkPackage;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class IncidentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_and_update_incident(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $owner = User::factory()->create();
        $workerRole = Role::query()->where('name', 'Worker')->firstOrFail();
        $owner->roles()->attach($workerRole->id);

        $incident = Incident::query()->create($this->requiredIncidentAttributes($owner, [
            'title' => 'Near-miss on line 3',
            'description' => 'Worker slipped near wet area.',
            'incident_description' => 'Worker slipped near wet area.',
            'location' => 'Line 3',
            'classification' => 'Moderate',
            'status' => 'draft',
        ]));

        $this->actingAs($owner)
            ->get(route('incidents.show', $incident))
            ->assertOk();

        $this->actingAs($owner)
            ->put(route('incidents.update', $incident), [
                'title' => 'Near-miss on line 3 - updated',
                'description' => 'Updated details.',
                'location' => 'Line 3',
                'datetime' => now()->format('Y-m-d H:i:s'),
                'classification' => 'Moderate',
            ])
            ->assertRedirect(route('incidents.index'));

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'title' => 'Near-miss on line 3 - updated',
            'status' => 'draft',
        ]);
    }

    public function test_non_owner_worker_cannot_update_incident(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $owner = User::factory()->create();
        $otherWorker = User::factory()->create();
        $workerRole = Role::query()->where('name', 'Worker')->firstOrFail();

        $owner->roles()->attach($workerRole->id);
        $otherWorker->roles()->attach($workerRole->id);

        $incident = Incident::query()->create($this->requiredIncidentAttributes($owner, [
            'title' => 'Forklift minor collision',
            'description' => 'No injuries reported.',
            'incident_description' => 'No injuries reported.',
            'location' => 'Warehouse A',
            'classification' => 'Minor',
            'status' => 'draft',
        ]));

        $this->actingAs($otherWorker)
            ->put(route('incidents.update', $incident), [
                'title' => 'Edited by non-owner',
                'description' => 'Attempted edit',
                'location' => 'Warehouse A',
                'datetime' => now()->format('Y-m-d H:i:s'),
                'classification' => 'Minor',
            ])
            ->assertForbidden();
    }

    public function test_owner_and_worker_cannot_transition_but_manager_can(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $owner = User::factory()->create();
        $worker = User::factory()->create();
        $manager = User::factory()->create();
        $workerRole = Role::query()->where('name', 'Worker')->firstOrFail();
        $managerRole = Role::query()->where('name', 'Manager')->firstOrFail();

        $owner->roles()->attach($workerRole->id);
        $worker->roles()->attach($workerRole->id);
        $manager->roles()->attach($managerRole->id);

        $incident = Incident::query()->create($this->requiredIncidentAttributes($owner, [
            'title' => 'Chemical spill',
            'description' => 'Contained and neutralized.',
            'incident_description' => 'Contained and neutralized.',
            'location' => 'Lab 2',
            'classification' => 'Major',
            'status' => 'draft',
        ]));

        $this->actingAs($owner)
            ->post(route('incidents.transition', $incident), ['to_status' => 'draft_submitted'])
            ->assertForbidden();

        $this->actingAs($worker)
            ->post(route('incidents.transition', $incident), ['to_status' => 'draft_submitted'])
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('incidents.transition', $incident), [
                'to_status' => 'draft_submitted',
                'remarks' => 'Manager forwarded draft for review.',
            ])
            ->assertRedirect(route('incidents.show', $incident));

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'status' => 'draft_submitted',
        ]);
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
