<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\IncidentClassification;
use App\Models\IncidentComment;
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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;

class IncidentWorkflowNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_move_incident_from_draft_to_draft_submitted(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $owner = User::factory()->create();
        $manager = User::factory()->create();
        $worker = User::factory()->create();

        $workerRole = Role::query()->where('name', 'Worker')->firstOrFail();
        $managerRole = Role::query()->where('name', 'Manager')->firstOrFail();

        $owner->roles()->attach($workerRole->id);
        $manager->roles()->attach($managerRole->id);
        $worker->roles()->attach($workerRole->id);

        $incident = Incident::query()->create($this->requiredIncidentAttributes($owner, [
            'title' => 'Valve pressure incident',
            'description' => 'Pressure exceeded safe threshold.',
            'incident_description' => 'Pressure exceeded safe threshold.',
            'location' => 'Plant B',
            'classification' => 'Major',
            'status' => 'draft',
        ]));

        $this->actingAs($worker)
            ->post(route('incidents.transition', $incident), ['to_status' => 'draft_submitted'])
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('incidents.transition', $incident), ['to_status' => 'draft_submitted'])
            ->assertRedirect(route('incidents.show', $incident));

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'status' => 'draft_submitted',
        ]);
    }

    public function test_full_workflow_progresses_through_all_required_roles(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $reporter = User::factory()->create();
        $manager = User::factory()->create();
        $hod = User::factory()->create();
        $apsbPd = User::factory()->create();
        $mrts = User::factory()->create();

        $workerRole = Role::query()->where('name', 'Worker')->firstOrFail();
        $managerRole = Role::query()->firstOrCreate(['name' => 'Manager'], ['slug' => 'manager', 'description' => 'Manager role']);
        $hodRole = Role::query()->firstOrCreate(['name' => 'HOD HSSE'], ['slug' => 'hod-hsse', 'description' => 'HOD HSSE role']);
        $apsbPdRole = Role::query()->firstOrCreate(['name' => 'APSB PD'], ['slug' => 'apsb-pd', 'description' => 'APSB PD role']);
        $mrtsRole = Role::query()->firstOrCreate(['name' => 'MRTS'], ['slug' => 'mrts', 'description' => 'MRTS role']);

        $reporter->roles()->attach($workerRole->id);
        $manager->roles()->attach($managerRole->id);
        $hod->roles()->attach($hodRole->id);
        $apsbPd->roles()->attach($apsbPdRole->id);
        $mrts->roles()->attach($mrtsRole->id);

        $incident = Incident::query()->create($this->requiredIncidentAttributes($reporter, [
            'title' => 'Compressor malfunction',
            'description' => 'Unexpected shutdown with alarm.',
            'incident_description' => 'Unexpected shutdown with alarm.',
            'location' => 'Unit 7',
            'classification' => 'Critical',
            'status' => 'draft',
        ]));

        $this->actingAs($manager)
            ->post(route('incidents.transition', $incident), ['to_status' => 'draft_submitted'])
            ->assertRedirect(route('incidents.show', $incident));

        $this->actingAs($hod)
            ->post(route('incidents.transition', $incident), ['to_status' => 'draft_reviewed'])
            ->assertRedirect(route('incidents.show', $incident));

        $this->actingAs($apsbPd)
            ->post(route('incidents.transition', $incident), ['to_status' => 'final_submitted'])
            ->assertRedirect(route('incidents.show', $incident));

        $this->actingAs($mrts)
            ->post(route('incidents.transition', $incident), ['to_status' => 'final_reviewed'])
            ->assertRedirect(route('incidents.show', $incident));

        $this->actingAs($hod)
            ->post(route('incidents.transition', $incident), ['to_status' => 'pending_closure'])
            ->assertRedirect(route('incidents.show', $incident));

        $this->actingAs($mrts)
            ->post(route('incidents.transition', $incident), ['to_status' => 'closed'])
            ->assertRedirect(route('incidents.show', $incident));

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'status' => 'closed',
        ]);

        $this->assertDatabaseCount('incident_workflow_logs', 6);
    }

    public function test_admin_cannot_transition_without_required_workflow_role(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $reporter = User::factory()->create();
        $admin = User::factory()->create();

        $workerRole = Role::query()->where('name', 'Worker')->firstOrFail();
        $adminRole = Role::query()->where('name', 'Admin')->firstOrFail();

        $reporter->roles()->attach($workerRole->id);
        $admin->roles()->attach($adminRole->id);

        $incident = Incident::query()->create($this->requiredIncidentAttributes($reporter, [
            'title' => 'Steam leak',
            'description' => 'Observed leakage at joint.',
            'incident_description' => 'Observed leakage at joint.',
            'location' => 'Boiler Room',
            'classification' => 'Major',
            'status' => 'draft',
        ]));

        $this->actingAs($admin)
            ->post(route('incidents.transition', $incident), [
                'to_status' => 'draft_submitted',
                'remarks' => 'Admin review',
            ])
            ->assertForbidden();
    }

    public function test_invalid_transition_target_is_rejected(): void
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
            'title' => 'Loose safety rail',
            'description' => 'Potential fall risk in area C.',
            'incident_description' => 'Potential fall risk in area C.',
            'location' => 'Area C',
            'classification' => 'Major',
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

    public function test_transition_is_blocked_when_role_requires_resolved_critical_comments(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        Config::set('incident_workflow.unresolved_critical_comments.enabled', true);
        Config::set('incident_workflow.unresolved_critical_comments.role_rules.HOD HSSE.enforce', true);

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
            'status' => 'draft',
        ]));

        $this->actingAs($manager)
            ->post(route('incidents.transition', $incident), ['to_status' => 'draft_submitted'])
            ->assertRedirect(route('incidents.show', $incident));

        IncidentComment::query()->create([
            'incident_id' => $incident->id,
            'user_id' => $reporter->id,
            'comment' => 'Critical unresolved issue must be closed before review.',
            'comment_type' => 'action_required',
            'is_critical' => true,
            'is_resolved' => false,
        ]);

        $this->actingAs($hod)
            ->from(route('incidents.show', $incident))
            ->post(route('incidents.transition', $incident), ['to_status' => 'draft_reviewed'])
            ->assertRedirect(route('incidents.show', $incident))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'status' => 'draft_submitted',
        ]);
    }

    public function test_transition_can_continue_when_role_rule_is_disabled(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        Config::set('incident_workflow.unresolved_critical_comments.enabled', true);
        Config::set('incident_workflow.unresolved_critical_comments.role_rules.HOD HSSE.enforce', false);

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
            'status' => 'draft',
        ]));

        $this->actingAs($manager)
            ->post(route('incidents.transition', $incident), ['to_status' => 'draft_submitted'])
            ->assertRedirect(route('incidents.show', $incident));

        IncidentComment::query()->create([
            'incident_id' => $incident->id,
            'user_id' => $reporter->id,
            'comment' => 'Critical unresolved issue currently open.',
            'comment_type' => 'action_required',
            'is_critical' => true,
            'is_resolved' => false,
        ]);

        $this->actingAs($hod)
            ->post(route('incidents.transition', $incident), ['to_status' => 'draft_reviewed'])
            ->assertRedirect(route('incidents.show', $incident));

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'status' => 'draft_reviewed',
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
