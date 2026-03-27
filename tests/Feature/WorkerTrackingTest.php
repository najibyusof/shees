<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Worker;
use App\Notifications\WorkerGeofenceAlertNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WorkerTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_worker_profile_and_log_attendance_inside_geofence(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $manager = User::factory()->create();
        $managerRole = Role::query()->where('name', 'Manager')->firstOrFail();
        $manager->roles()->attach($managerRole->id);

        $create = $this->actingAs($manager)->postJson(route('worker-tracking.workers.store'), [
            'employee_code' => 'WK-1001',
            'full_name' => 'John Tracker',
            'department' => 'Operations',
            'position' => 'Technician',
            'status' => 'active',
            'geofence_center_latitude' => 14.5995,
            'geofence_center_longitude' => 120.9842,
            'geofence_radius_meters' => 120,
        ]);

        $create->assertCreated();
        $workerId = $create->json('data.id');

        $attendance = $this->actingAs($manager)->postJson(route('worker-tracking.workers.attendance.store', $workerId), [
            'event_type' => 'check_in',
            'latitude' => 14.5996,
            'longitude' => 120.9841,
            'source' => 'manual',
            'device_identifier' => 'mobile-unit-1',
        ]);

        $attendance->assertCreated();
        $attendance->assertJsonPath('data.inside_geofence', true);

        $this->assertDatabaseHas('attendance_logs', [
            'worker_id' => $workerId,
            'event_type' => 'check_in',
            'inside_geofence' => 1,
        ]);
    }

    public function test_simulated_tracking_outside_geofence_sends_alert_notification(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Notification::fake();

        $manager = User::factory()->create();
        $safetyOfficer = User::factory()->create();
        $managerRole = Role::query()->where('name', 'Manager')->firstOrFail();
        $safetyRole = Role::query()->where('name', 'Safety Officer')->firstOrFail();
        $manager->roles()->attach($managerRole->id);
        $safetyOfficer->roles()->attach($safetyRole->id);

        $worker = Worker::query()->create([
            'employee_code' => 'WK-1002',
            'full_name' => 'Jane Boundary',
            'status' => 'active',
            'geofence_center_latitude' => 14.5995,
            'geofence_center_longitude' => 120.9842,
            'geofence_radius_meters' => 80,
        ]);

        $response = $this->actingAs($manager)
            ->postJson(route('worker-tracking.workers.simulate', $worker), [
                'breach_chance' => 1,
                'breach_offset_meters' => 200,
                'device_identifier' => 'simulator-alpha',
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.inside_geofence', false);

        Notification::assertSentTo($manager, WorkerGeofenceAlertNotification::class);
        Notification::assertSentTo($safetyOfficer, WorkerGeofenceAlertNotification::class);

        $this->assertDatabaseHas('attendance_logs', [
            'worker_id' => $worker->id,
            'inside_geofence' => 0,
            'source' => 'simulated',
        ]);
    }

    public function test_worker_without_manage_permission_cannot_create_worker_profile(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $workerUser = User::factory()->create();
        $workerRole = Role::query()->where('name', 'Worker')->firstOrFail();
        $workerUser->roles()->attach($workerRole->id);

        $this->actingAs($workerUser)
            ->postJson(route('worker-tracking.workers.store'), [
                'employee_code' => 'WK-403',
                'full_name' => 'Unauthorized Create',
            ])
            ->assertForbidden();
    }
}
