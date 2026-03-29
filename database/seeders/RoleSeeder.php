<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $rolePermissions = [
            'Admin' => [
                'dashboard.view',
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
                'users.restore',
                'users.force-delete',
                'roles.manage',
                'reports.view',
                'safety.manage',
                'audits.view',
                'audits.conduct',
                'audits.approve',
                'audits.ncr.manage',
                'work-permits.approve',
                'incidents.submit',
                'incidents.approve',
                'incidents.comment',
                'workers.view',
                'workers.manage',
                'workers.track',
            ],
            'Manager' => [
                'dashboard.view',
                'users.view',
                'users.create',
                'users.update',
                'reports.view',
                'audits.view',
                'audits.conduct',
                'audits.approve',
                'audits.ncr.manage',
                'work-permits.approve',
                'incidents.submit',
                'incidents.approve',
                'incidents.comment',
                'workers.view',
                'workers.manage',
                'workers.track',
            ],
            'Safety Officer' => [
                'dashboard.view',
                'safety.manage',
                'reports.view',
                'audits.view',
                'audits.conduct',
                'audits.approve',
                'audits.ncr.manage',
                'incidents.submit',
                'work-permits.approve',
                'incidents.approve',
                'incidents.comment',
                'workers.view',
                'workers.track',
            ],
            'Auditor' => [
                'dashboard.view',
                'reports.view',
                'audits.view',
                'audits.conduct',
                'audits.ncr.manage',
                'incidents.comment',
                'workers.view',
            ],
            'Supervisor' => [
                'dashboard.view',
                'users.view',
                'reports.view',
                'audits.view',
                'audits.conduct',
                'audits.ncr.manage',
                'work-permits.approve',
                'incidents.submit',
                'incidents.comment',
                'workers.view',
                'workers.track',
            ],
            'Worker' => [
                'dashboard.view',
                'audits.view',
                'audits.conduct',
                'incidents.submit',
                'incidents.comment',
                'workers.view',
                'workers.track',
            ],

            // -- Incident workflow roles --
            // HOD HSSE: reviews submitted drafts, submits final, initiates closure
            'HOD HSSE' => [
                'dashboard.view',
                'reports.view',
                'audits.view',
                'incidents.submit',
                'incidents.approve',
                'incidents.comment',
                'workers.view',
            ],

            // APSB PD: co-approves final submission alongside HOD HSSE
            'APSB PD' => [
                'dashboard.view',
                'reports.view',
                'audits.view',
                'incidents.submit',
                'incidents.approve',
                'incidents.comment',
                'workers.view',
            ],

            // MRTS: reviews final submission and closes the incident
            'MRTS' => [
                'dashboard.view',
                'reports.view',
                'audits.view',
                'incidents.submit',
                'incidents.approve',
                'incidents.comment',
                'workers.view',
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
    }
}
