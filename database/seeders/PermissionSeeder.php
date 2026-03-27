<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define module permissions
        $modules = [
            'incident' => 'Incident Management',
            'training' => 'Training Management',
            'inspection' => 'Inspection Management',
            'audit' => 'Audit Management',
            'worker' => 'Worker Management',
            'user_management' => 'User Management',
        ];

        $actions = [
            'view' => 'View',
            'create' => 'Create',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'approve' => 'Approve',
        ];

        // Create permissions
        foreach ($modules as $moduleKey => $moduleName) {
            foreach ($actions as $actionKey => $actionName) {
                Permission::firstOrCreate(
                    ['name' => "{$actionKey}_{$moduleKey}"],
                    ['description' => "{$actionName} {$moduleName}"]
                );
            }
        }
    }
}
