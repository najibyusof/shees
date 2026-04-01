<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roleMap = [
            'Admin' => ['*'],
            'Manager' => [
                'view_dashboard',
                'view_incident',
                'submit_incident',
                'view_report',
                'reports.view',
                'incidents.submit',
                'view_audit',
                'audits.view',
                'view_worker',
            ],
            'Safety Officer' => [
                'view_dashboard',
                'view_incident',
                'create_incident',
                'edit_incident',
                'submit_incident',
                'review_incident',
                'incidents.submit',
                'view_audit',
                'audits.view',
                'audits.conduct',
                'view_worker',
            ],
            'HOD HSSE' => [
                'view_dashboard',
                'view_incident',
                'review_incident',
                'submit_incident',
                'request_closure',
                'view_report',
                'reports.view',
                'incidents.submit',
            ],
            'APSB PD' => [
                'view_dashboard',
                'view_incident',
                'submit_incident',
                'view_report',
                'reports.view',
                'incidents.submit',
                'view_audit',
                'audits.view',
            ],
            'MRTS' => [
                'view_dashboard',
                'approve_final',
                'approve_closure',
                'view_report',
                'reports.view',
            ],
            'Auditor' => [
                'view_dashboard',
                'view_audit',
                'create_audit',
                'edit_audit',
                'approve_audit',
                'view_report',
                'reports.view',
                'audits.view',
                'audits.conduct',
                'audits.approve',
                'audits.ncr.manage',
            ],
            'Supervisor' => [
                'view_dashboard',
                'view_incident',
                'submit_incident',
                'incidents.submit',
                'view_worker',
                'view_audit',
                'audits.view',
            ],
            'Worker' => [
                'view_dashboard',
                'view_incident',
                'create_incident',
                'incidents.submit',
                'view_training',
            ],
        ];

        $allPermissionIds = Permission::query()->pluck('id')->all();

        foreach ($roleMap as $roleName => $permissionNames) {
            $role = Role::query()->where('name', $roleName)->first();

            if (! $role) {
                continue;
            }

            $permissionIds = $permissionNames === ['*']
                ? $allPermissionIds
                : Permission::query()->whereIn('name', $permissionNames)->pluck('id')->all();

            $role->permissions()->sync($permissionIds);
        }
    }
}
