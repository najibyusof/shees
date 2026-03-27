<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserUiPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_save_ui_preferences(): void
    {
        $response = $this->post(route('admin.users.preferences'), [
            'density' => 'comfortable',
            'defaultSort' => 'created_at',
            'defaultDirection' => 'desc',
            'visibleColumns' => [
                'id' => true,
                'name' => true,
                'email' => true,
                'created_at' => true,
            ],
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_worker_role_cannot_save_ui_preferences(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $worker = User::factory()->create();
        $workerRole = Role::query()->where('name', 'Worker')->firstOrFail();
        $worker->roles()->attach($workerRole->id);

        $response = $this->actingAs($worker)->postJson(route('admin.users.preferences'), [
            'density' => 'compact',
            'defaultSort' => 'name',
            'defaultDirection' => 'asc',
            'visibleColumns' => [
                'id' => true,
                'name' => true,
                'email' => true,
                'created_at' => false,
            ],
        ]);

        $response->assertForbidden();
    }

    public function test_manager_can_save_ui_preferences_and_get_toast_contract(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $manager = User::factory()->create();
        $managerRole = Role::query()->where('name', 'Manager')->firstOrFail();
        $manager->roles()->attach($managerRole->id);

        $payload = [
            'density' => 'compact',
            'defaultSort' => 'name',
            'defaultDirection' => 'asc',
            'visibleColumns' => [
                'id' => true,
                'name' => true,
                'email' => true,
                'created_at' => false,
            ],
        ];

        $response = $this->actingAs($manager)->postJson(route('admin.users.preferences'), $payload);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'toast' => ['type', 'title', 'message'],
            ])
            ->assertJson([
                'toast' => [
                    'type' => 'success',
                ],
            ]);

        $this->assertDatabaseHas('user_ui_preferences', [
            'user_id' => $manager->id,
            'page_key' => 'admin.users',
        ]);
    }
}
