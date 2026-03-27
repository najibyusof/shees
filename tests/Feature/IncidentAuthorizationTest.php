<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $incident = Incident::query()->create([
            'reported_by' => $owner->id,
            'title' => 'Near-miss on line 3',
            'description' => 'Worker slipped near wet area.',
            'location' => 'Line 3',
            'datetime' => now(),
            'classification' => 'Moderate',
            'status' => 'draft',
        ]);

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

        $incident = Incident::query()->create([
            'reported_by' => $owner->id,
            'title' => 'Forklift minor collision',
            'description' => 'No injuries reported.',
            'location' => 'Warehouse A',
            'datetime' => now(),
            'classification' => 'Minor',
            'status' => 'draft',
        ]);

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

    public function test_owner_can_submit_but_worker_cannot_approve_and_manager_can(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $owner = User::factory()->create();
        $worker = User::factory()->create();
        $manager = User::factory()->create();
        $safetyOfficer = User::factory()->create();
        $workerRole = Role::query()->where('name', 'Worker')->firstOrFail();
        $managerRole = Role::query()->where('name', 'Manager')->firstOrFail();
        $safetyRole = Role::query()->where('name', 'Safety Officer')->firstOrFail();

        $owner->roles()->attach($workerRole->id);
        $worker->roles()->attach($workerRole->id);
        $manager->roles()->attach($managerRole->id);
        $safetyOfficer->roles()->attach($safetyRole->id);

        $incident = Incident::query()->create([
            'reported_by' => $owner->id,
            'title' => 'Chemical spill',
            'description' => 'Contained and neutralized.',
            'location' => 'Lab 2',
            'datetime' => now(),
            'classification' => 'Major',
            'status' => 'draft',
        ]);

        $this->actingAs($owner)
            ->post(route('incidents.submit', $incident))
            ->assertRedirect(route('incidents.show', $incident));

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'status' => 'submitted',
        ]);

        $this->actingAs($worker)
            ->post(route('incidents.approve', $incident))
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('incidents.approve', $incident), [
                'remarks' => 'Reviewed and approved.',
            ])
            ->assertRedirect(route('incidents.show', $incident));

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'status' => 'under_review',
        ]);

        $this->actingAs($safetyOfficer)
            ->post(route('incidents.approve', $incident), [
                'remarks' => 'Safety approval completed.',
            ])
            ->assertRedirect(route('incidents.show', $incident));

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'status' => 'approved',
        ]);
    }
}
