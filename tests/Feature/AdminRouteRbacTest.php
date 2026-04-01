<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRouteRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_roles_manage_permission_can_access_roles_index(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'roles.manage']);
        $role = Role::factory()->create([
            'name' => 'RBAC Maintainer',
            'slug' => 'rbac-maintainer',
        ]);

        $role->permissions()->sync([$permission->id]);
        $user->roles()->sync([$role->id]);

        $this->actingAs($user)
            ->get(route('admin.roles'))
            ->assertOk();
    }

    public function test_user_without_roles_manage_permission_cannot_access_roles_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.roles'))
            ->assertForbidden();
    }

    public function test_user_with_view_user_management_can_access_users_index(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'view_user_management']);
        $role = Role::factory()->create([
            'name' => 'User Viewer',
            'slug' => 'user-viewer',
        ]);

        $role->permissions()->sync([$permission->id]);
        $user->roles()->sync([$role->id]);

        $this->actingAs($user)
            ->get(route('admin.users'))
            ->assertOk();
    }

    public function test_user_with_roles_manage_permission_can_access_incident_workflow_settings(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'roles.manage']);
        $role = Role::factory()->create([
            'name' => 'Workflow Configurator',
            'slug' => 'workflow-configurator',
        ]);

        $role->permissions()->sync([$permission->id]);
        $user->roles()->sync([$role->id]);

        $this->actingAs($user)
            ->get(route('admin.settings.incident-workflow'))
            ->assertOk();
    }
}
