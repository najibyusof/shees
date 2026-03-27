<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $workerRole = Role::query()->firstOrCreate(['name' => 'Worker'], ['description' => 'Worker role']);
        $user->roles()->attach($workerRole->id);

        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\IncidentWorkflowNotification',
            'data' => [
                'incident_id' => 100,
                'incident_title' => 'Test Incident',
                'event' => 'submitted',
                'status' => 'submitted',
                'url' => route('dashboard'),
            ],
        ]);

        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\IncidentWorkflowNotification',
            'data' => [
                'incident_id' => 101,
                'incident_title' => 'Test Incident 2',
                'event' => 'approved',
                'status' => 'approved',
                'url' => route('dashboard'),
            ],
        ]);

        $this->assertSame(2, $user->unreadNotifications()->count());

        $this->actingAs($user)
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }
}
