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
        $permissions = [
            'view_dashboard' => 'View dashboard',

            // Incident module
            'view_incident' => 'View incidents',
            'create_incident' => 'Create incidents',
            'edit_incident' => 'Edit incidents',
            'submit_incident' => 'Submit incidents',
            'review_incident' => 'Review incident drafts',
            'approve_incident' => 'Approve incidents',
            'approve_final' => 'Approve final incident submissions',
            'request_closure' => 'Request incident closure',
            'approve_closure' => 'Approve incident closure',

            // Training module
            'view_training' => 'View trainings',
            'create_training' => 'Create trainings',
            'edit_training' => 'Edit trainings',
            'approve_training' => 'Approve trainings',

            // Audit module
            'view_audit' => 'View audit',
            'create_audit' => 'Create audit',
            'edit_audit' => 'Edit audits',
            'approve_audit' => 'Approve audit',

            // Worker module
            'view_worker' => 'View workers',
            'create_worker' => 'Create workers',
            'edit_worker' => 'Edit workers',
            'approve_worker' => 'Approve workers',

            // User management
            'view_user_management' => 'View users and roles',
            'create_user_management' => 'Create users',
            'edit_user_management' => 'Edit users',
            'delete_user_management' => 'Delete users',

            // Reports and admin
            'view_report' => 'View reports',
            'roles.manage' => 'Manage roles and permissions',

            // Legacy compatibility permissions used by existing routes and policies
            'dashboard.view' => 'Legacy dashboard access',
            'users.view' => 'Legacy users view',
            'users.create' => 'Legacy users create',
            'users.update' => 'Legacy users update',
            'users.delete' => 'Legacy users delete',
            'users.restore' => 'Legacy users restore',
            'users.force-delete' => 'Legacy users force delete',
            'reports.view' => 'Legacy reports view',
            'audits.view' => 'Legacy audits view',
            'audits.conduct' => 'Legacy audits conduct',
            'audits.approve' => 'Legacy audits approve',
            'audits.ncr.manage' => 'Legacy audits NCR manage',
            'audit.view' => 'Legacy audit view',
            'audit.conduct' => 'Legacy audit conduct',
            'audit.approve' => 'Legacy audit approve',
            'audit.ncr.manage' => 'Legacy audit NCR manage',
            'incidents.submit' => 'Legacy incident submit',
            'incidents.approve' => 'Legacy incident approve',
            'incidents.comment' => 'Legacy incident comment',
            'workers.view' => 'Legacy workers view',
            'workers.manage' => 'Legacy workers manage',
            'workers.track' => 'Legacy workers tracking',
            'safety.manage' => 'Legacy safety manage',
            'work-permits.approve' => 'Legacy work permits approve',
        ];

        foreach ($permissions as $name => $description) {
            Permission::query()->firstOrCreate(
                ['name' => $name],
                ['description' => $description]
            );
        }
    }
}
