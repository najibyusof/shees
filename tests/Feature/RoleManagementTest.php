<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_role_management_index(): void
    {
        $admin = $this->createAdminUser();
        Role::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.roles'));

        $response
            ->assertOk()
            ->assertSee('Role & Permission Management');
    }

    public function test_non_admin_role_is_blocked_even_with_manage_permission(): void
    {
        $permission = Permission::factory()->create(['name' => 'roles.manage']);
        $managerRole = Role::factory()->create([
            'name' => 'Manager',
            'slug' => 'manager',
        ]);
        $managerRole->permissions()->sync([$permission->id]);

        $manager = User::factory()->create();
        $manager->roles()->sync([$managerRole->id]);

        $this->actingAs($manager)
            ->get(route('admin.roles'))
            ->assertForbidden();
    }

    public function test_admin_can_create_role_and_sync_permissions(): void
    {
        $admin = $this->createAdminUser();
        $permissions = Permission::factory()->count(3)->sequence(
            ['name' => 'incidents.view'],
            ['name' => 'incidents.create'],
            ['name' => 'reports.view'],
        )->create();

        $response = $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'Incident Commander',
            'slug' => '',
            'permission_ids' => $permissions->pluck('id')->all(),
        ]);

        $role = Role::query()->where('name', 'Incident Commander')->firstOrFail();

        $response
            ->assertRedirect(route('admin.roles.show', $role))
            ->assertSessionHasNoErrors();

        $this->assertSame('incident-commander', $role->slug);
        $this->assertEqualsCanonicalizing(
            $permissions->pluck('id')->all(),
            $role->permissions()->pluck('permissions.id')->all(),
        );
    }

    public function test_admin_can_update_role_and_resync_permissions(): void
    {
        $admin = $this->createAdminUser();
        $oldPermission = Permission::factory()->create(['name' => 'audits.view']);
        $newPermissions = Permission::factory()->count(2)->sequence(
            ['name' => 'audits.approve'],
            ['name' => 'audits.ncr.manage'],
        )->create();

        $role = Role::factory()->create([
            'name' => 'Auditor Lead',
            'slug' => 'auditor-lead',
        ]);
        $role->permissions()->sync([$oldPermission->id]);

        $response = $this->actingAs($admin)->patch(route('admin.roles.update', $role), [
            'name' => 'Lead Auditor',
            'slug' => 'lead-auditor',
            'permission_ids' => $newPermissions->pluck('id')->all(),
        ]);

        $response
            ->assertRedirect(route('admin.roles.show', $role))
            ->assertSessionHasNoErrors();

        $role->refresh();

        $this->assertSame('Lead Auditor', $role->name);
        $this->assertSame('lead-auditor', $role->slug);
        $this->assertEqualsCanonicalizing(
            $newPermissions->pluck('id')->all(),
            $role->permissions()->pluck('permissions.id')->all(),
        );
    }

    public function test_role_with_assigned_users_cannot_be_deleted(): void
    {
        $admin = $this->createAdminUser();
        $role = Role::factory()->create([
            'name' => 'Inspection Reviewer',
            'slug' => 'inspection-reviewer',
        ]);

        $assignedUser = User::factory()->create();
        $assignedUser->roles()->sync([$role->id]);

        $this->actingAs($admin)
            ->delete(route('admin.roles.destroy', $role))
            ->assertRedirect(route('admin.roles.show', $role));

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_admin_can_delete_empty_role(): void
    {
        $admin = $this->createAdminUser();
        $role = Role::factory()->create([
            'name' => 'Temporary Reviewer',
            'slug' => 'temporary-reviewer',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.roles.destroy', $role))
            ->assertRedirect(route('admin.roles'));

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_admin_can_clone_role_with_all_permissions(): void
    {
        $admin = $this->createAdminUser();
        $permissions = Permission::factory()->count(3)->sequence(
            ['name' => 'audits.view'],
            ['name' => 'audits.approve'],
            ['name' => 'audits.ncr.manage'],
        )->create();

        $originalRole = Role::factory()->create([
            'name' => 'Audit Manager',
            'slug' => 'audit-manager',
            'description' => 'Manages audit workflow',
        ]);
        $originalRole->permissions()->sync($permissions->pluck('id')->all());

        $response = $this->actingAs($admin)->post(route('admin.roles.clone', $originalRole));

        $clonedRole = Role::query()
            ->where('name', 'Audit Manager (Clone)')
            ->firstOrFail();

        $response
            ->assertRedirect(route('admin.roles.show', $clonedRole))
            ->assertSessionHasNoErrors();

        $this->assertSame('audit-manager-clone', $clonedRole->slug);
        $this->assertStringContainsString('cloned', $clonedRole->description ?? '');
        $this->assertEqualsCanonicalizing(
            $permissions->pluck('id')->all(),
            $clonedRole->permissions()->pluck('permissions.id')->all(),
        );
    }

    public function test_admin_can_export_role_permissions_as_csv(): void
    {
        $admin = $this->createAdminUser();
        $permissions = Permission::factory()->count(3)->sequence(
            ['name' => 'incidents.view', 'description' => 'View incidents'],
            ['name' => 'incidents.create', 'description' => 'Create incidents'],
            ['name' => 'incidents.approve', 'description' => 'Approve incidents'],
        )->create();

        $role = Role::factory()->create([
            'name' => 'Incident Manager',
            'slug' => 'incident-manager',
        ]);
        $role->permissions()->sync($permissions->pluck('id')->all());

        $response = $this->actingAs($admin)->get(route('admin.roles.export', [$role, 'csv']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition');
    }

    public function test_admin_can_export_role_permissions_as_pdf(): void
    {
        $admin = $this->createAdminUser();
        $permissions = Permission::factory()->count(2)->sequence(
            ['name' => 'workers.view', 'description' => 'View workers'],
            ['name' => 'workers.manage', 'description' => 'Manage workers'],
        )->create();

        $role = Role::factory()->create([
            'name' => 'Safety Officer',
            'slug' => 'safety-officer',
        ]);
        $role->permissions()->sync($permissions->pluck('id')->all());

        $response = $this->actingAs($admin)->get(route('admin.roles.export', [$role, 'pdf']));

        $response->assertOk();
        $response->assertHeader('Content-Type');
        $response->assertHeader('Content-Disposition');
    }

    public function test_export_with_invalid_format_returns_404(): void
    {
        $admin = $this->createAdminUser();
        $role = Role::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.roles.export', [$role, 'json']))
            ->assertNotFound();
    }

    public function test_non_admin_cannot_clone_role(): void
    {
        $role = Role::factory()->create([
            'name' => 'Test Role',
            'slug' => 'test-role',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.roles.clone', $role))
            ->assertForbidden();

        $this->assertDatabaseMissing('roles', ['name' => 'Test Role (Clone)']);
    }

    private function createAdminUser(): User
    {
        $permission = Permission::factory()->create(['name' => 'roles.manage']);
        $adminRole = Role::factory()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $adminRole->permissions()->sync([$permission->id]);

        $user = User::factory()->create();
        $user->roles()->sync([$adminRole->id]);

        return $user;
    }
}
