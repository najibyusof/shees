<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarPermissionVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_shows_only_authorized_permission_sections(): void
    {
        $user = User::factory()->create();

        $incidentView = Permission::factory()->create(['name' => 'view_incident']);
        $role = Role::factory()->create([
            'name' => 'Supervisor',
            'slug' => 'supervisor',
        ]);

        $role->permissions()->sync([$incidentView->id]);
        $user->roles()->sync([$role->id]);

        $this->actingAs($user);

        $view = $this->view('layouts.partials.sidebar');

        $view->assertSee('Incident');
        $view->assertSee('Incidents');
        $view->assertDontSee('Training');
        $view->assertDontSee('Admin');
    }

    public function test_sidebar_shows_admin_group_when_user_has_user_management_permission(): void
    {
        $user = User::factory()->create();

        $permissions = Permission::factory()->count(2)->sequence(
            ['name' => 'view_user_management'],
            ['name' => 'roles.manage'],
        )->create();

        $role = Role::factory()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);

        $role->permissions()->sync($permissions->pluck('id')->all());
        $user->roles()->sync([$role->id]);

        $this->actingAs($user);

        $view = $this->view('layouts.partials.sidebar');

        $view->assertSee('Admin');
        $view->assertSee('User Management');
        $view->assertSee('Roles &amp; Permissions', false);
    }
}
