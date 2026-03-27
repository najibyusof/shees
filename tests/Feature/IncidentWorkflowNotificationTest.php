<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Role;
use App\Models\User;
use App\Notifications\IncidentWorkflowNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class IncidentWorkflowNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_notifies_all_approvers(): void
    {
        Notification::fake();
        $this->seed(RolesAndPermissionsSeeder::class);

        $reporter = User::factory()->create();
        $manager = User::factory()->create();
        $safetyOfficer = User::factory()->create();
        $worker = User::factory()->create();

        $workerRole = Role::query()->where('name', 'Worker')->firstOrFail();
        $managerRole = Role::query()->where('name', 'Manager')->firstOrFail();
        $safetyRole = Role::query()->where('name', 'Safety Officer')->firstOrFail();

        $reporter->roles()->attach($workerRole->id);
        $manager->roles()->attach($managerRole->id);
        $safetyOfficer->roles()->attach($safetyRole->id);
        $worker->roles()->attach($workerRole->id);

        $incident = Incident::query()->create([
            'reported_by' => $reporter->id,
            'title' => 'Valve pressure incident',
            'description' => 'Pressure exceeded safe threshold.',
            'location' => 'Plant B',
            'datetime' => now(),
            'classification' => 'Major',
            'status' => 'draft',
        ]);

        $this->actingAs($reporter)
            ->post(route('incidents.submit', $incident))
            ->assertRedirect(route('incidents.show', $incident));

        Notification::assertSentTo([$manager, $safetyOfficer], IncidentWorkflowNotification::class);
        Notification::assertNotSentTo($worker, IncidentWorkflowNotification::class);
    }

    public function test_first_approval_keeps_under_review_and_second_role_approves(): void
    {
        Notification::fake();
        $this->seed(RolesAndPermissionsSeeder::class);

        $reporter = User::factory()->create();
        $manager = User::factory()->create();
        $safetyOfficer = User::factory()->create();

        $workerRole = Role::query()->where('name', 'Worker')->firstOrFail();
        $managerRole = Role::query()->where('name', 'Manager')->firstOrFail();
        $safetyRole = Role::query()->where('name', 'Safety Officer')->firstOrFail();

        $reporter->roles()->attach($workerRole->id);
        $manager->roles()->attach($managerRole->id);
        $safetyOfficer->roles()->attach($safetyRole->id);

        $incident = Incident::query()->create([
            'reported_by' => $reporter->id,
            'title' => 'Compressor malfunction',
            'description' => 'Unexpected shutdown with alarm.',
            'location' => 'Unit 7',
            'datetime' => now(),
            'classification' => 'Critical',
            'status' => 'draft',
        ]);

        $this->actingAs($reporter)
            ->post(route('incidents.submit', $incident))
            ->assertRedirect(route('incidents.show', $incident));

        $this->actingAs($manager)
            ->post(route('incidents.approve', $incident), [
                'remarks' => 'Manager approval complete.',
            ])
            ->assertRedirect(route('incidents.show', $incident));

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'status' => 'under_review',
        ]);

        $this->assertDatabaseHas('incident_approvals', [
            'incident_id' => $incident->id,
            'decision' => 'approved',
            'approver_role' => 'Manager',
        ]);

        $this->actingAs($safetyOfficer)
            ->post(route('incidents.approve', $incident), [
                'remarks' => 'Safety officer approval complete.',
            ])
            ->assertRedirect(route('incidents.show', $incident));

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('incident_approvals', [
            'incident_id' => $incident->id,
            'decision' => 'approved',
            'approver_role' => 'Safety Officer',
        ]);

        Notification::assertSentTo($reporter, IncidentWorkflowNotification::class, function ($notification) use ($incident) {
            $data = $notification->toArray((object) []);

            return $data['incident_id'] === $incident->id && in_array($data['event'], ['approval_pending', 'approved'], true);
        });
    }

    public function test_admin_only_role_cannot_approve_when_manager_and_safety_are_required(): void
    {
        Notification::fake();
        $this->seed(RolesAndPermissionsSeeder::class);

        $reporter = User::factory()->create();
        $admin = User::factory()->create();

        $workerRole = Role::query()->where('name', 'Worker')->firstOrFail();
        $adminRole = Role::query()->where('name', 'Admin')->firstOrFail();

        $reporter->roles()->attach($workerRole->id);
        $admin->roles()->attach($adminRole->id);

        $incident = Incident::query()->create([
            'reported_by' => $reporter->id,
            'title' => 'Steam leak',
            'description' => 'Observed leakage at joint.',
            'location' => 'Boiler Room',
            'datetime' => now(),
            'classification' => 'Major',
            'status' => 'draft',
        ]);

        $this->actingAs($reporter)
            ->post(route('incidents.submit', $incident))
            ->assertRedirect(route('incidents.show', $incident));

        $this->actingAs($admin)
            ->post(route('incidents.approve', $incident), ['remarks' => 'Admin review'])
            ->assertForbidden();
    }

    public function test_same_approver_cannot_reject_after_approving_same_incident(): void
    {
        Notification::fake();
        $this->seed(RolesAndPermissionsSeeder::class);

        $reporter = User::factory()->create();
        $manager = User::factory()->create();

        $workerRole = Role::query()->where('name', 'Worker')->firstOrFail();
        $managerRole = Role::query()->where('name', 'Manager')->firstOrFail();

        $reporter->roles()->attach($workerRole->id);
        $manager->roles()->attach($managerRole->id);

        $incident = Incident::query()->create([
            'reported_by' => $reporter->id,
            'title' => 'Loose safety rail',
            'description' => 'Potential fall risk in area C.',
            'location' => 'Area C',
            'datetime' => now(),
            'classification' => 'Major',
            'status' => 'draft',
        ]);

        $this->actingAs($reporter)
            ->post(route('incidents.submit', $incident))
            ->assertRedirect(route('incidents.show', $incident));

        $this->actingAs($manager)
            ->post(route('incidents.approve', $incident), ['remarks' => 'Initial manager approval'])
            ->assertRedirect(route('incidents.show', $incident));

        $this->actingAs($manager)
            ->from(route('incidents.show', $incident))
            ->post(route('incidents.reject', $incident), ['reason' => 'Trying to reverse decision'])
            ->assertRedirect(route('incidents.show', $incident))
            ->assertSessionHasErrors('status');
    }
}
