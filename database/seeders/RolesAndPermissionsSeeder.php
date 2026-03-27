<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rolePermissions = [
            'Admin' => [
                'dashboard.view',
                'view_user_management',
                'create_user_management',
                'edit_user_management',
                'delete_user_management',
                'reports.view',
                'safety.manage',
                'view_audit',
                'create_audit',
                'approve_audit',
                'edit_audit',
                'work-permits.approve',
                'create_incident',
                'approve_incident',
                'edit_incident',
                'view_worker',
                'edit_worker',
            ],
            'Manager' => [
                'dashboard.view',
                'view_user_management',
                'create_user_management',
                'edit_user_management',
                'reports.view',
                'view_audit',
                'create_audit',
                'approve_audit',
                'edit_audit',
                'work-permits.approve',
                'create_incident',
                'approve_incident',
                'edit_incident',
                'view_worker',
                'edit_worker',
            ],
            'Safety Officer' => [
                'dashboard.view',
                'safety.manage',
                'reports.view',
                'view_audit',
                'create_audit',
                'approve_audit',
                'edit_audit',
                'create_incident',
                'work-permits.approve',
                'approve_incident',
                'edit_incident',
                'view_worker',
            ],
            'Auditor' => [
                'dashboard.view',
                'reports.view',
                'view_audit',
                'create_audit',
                'edit_audit',
                'edit_incident',
                'view_worker',
            ],
            'Supervisor' => [
                'dashboard.view',
                'view_user_management',
                'reports.view',
                'view_audit',
                'create_audit',
                'edit_audit',
                'work-permits.approve',
                'create_incident',
                'edit_incident',
                'view_worker',
            ],
            'Worker' => [
                'dashboard.view',
                'view_audit',
                'create_audit',
                'create_incident',
                'edit_incident',
                'view_worker',
            ],
        ];

        $allPermissions = collect($rolePermissions)
            ->flatten()
            ->unique()
            ->values();

        foreach ($allPermissions as $permissionName) {
            Permission::query()->firstOrCreate(
                ['name' => $permissionName],
                ['description' => str_replace('.', ' ', $permissionName)]
            );
        }

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::query()->firstOrCreate(
                ['name' => $roleName],
                [
                    'slug' => Str::slug($roleName),
                    'description' => $roleName.' role',
                ]
            );

            $permissionIds = Permission::query()
                ->whereIn('name', $permissions)
                ->pluck('id');

            $role->permissions()->sync($permissionIds);
        }

        $admin = Role::query()->where('name', 'Admin')->first();
        $firstUser = User::query()->first();

        if ($admin && $firstUser) {
            $firstUser->roles()->syncWithoutDetaching([$admin->id]);
        }
    }
}
