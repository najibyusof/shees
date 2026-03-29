<?php

namespace Tests\Feature;

use App\Console\Commands\CleanupAuditLogsCommand;
use App\Models\AuditLog;
use App\Models\Incident;
use App\Models\IncidentClassification;
use App\Models\IncidentLocation;
use App\Models\IncidentType;
use App\Models\LocationType;
use App\Models\ReportPreset;
use App\Models\Role;
use App\Models\Subcontractor;
use App\Models\SiteAudit;
use App\Models\User;
use App\Models\WorkActivity;
use App\Models\WorkPackage;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $roleName): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('name', $roleName)->firstOrFail();
        $user->roles()->attach($role->id);

        return $user;
    }

    public function test_create_update_and_delete_actions_are_logged_across_modules(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $manager = $this->userWithRole('Manager');

        $createWorker = $this->actingAs($manager)->postJson(route('worker-tracking.workers.store'), [
            'employee_code' => 'WK-AUDIT-001',
            'full_name' => 'Audit Worker',
            'status' => 'active',
        ]);

        $createWorker->assertCreated();

        $workerId = (int) $createWorker->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $manager->id,
            'action' => 'create',
            'module' => 'workers',
        ]);

        $updateWorker = $this->actingAs($manager)->putJson(route('worker-tracking.workers.update', $workerId), [
            'department' => 'Quality',
        ]);

        $updateWorker->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $manager->id,
            'action' => 'update',
            'module' => 'workers',
        ]);

        $preset = ReportPreset::query()->create([
            'user_id' => $manager->id,
            'name' => 'Delete Me',
            'module' => 'incidents',
            'export_format' => 'csv',
            'filters' => ['module' => 'incidents'],
            'schedule_enabled' => false,
        ]);

        $this->actingAs($manager)
            ->delete(route('reports.presets.destroy', $preset))
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $manager->id,
            'action' => 'delete',
            'module' => 'reports',
        ]);
    }

    public function test_incident_transition_action_is_logged(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $owner = $this->userWithRole('Worker');
        $manager = $this->userWithRole('Manager');

        $incident = Incident::query()->create($this->requiredIncidentAttributes($owner, [
            'reported_by' => $owner->id,
            'title' => 'Approval Audit Log Test',
            'description' => 'Incident for approve audit verification.',
            'incident_description' => 'Incident for approve audit verification.',
            'location' => 'Warehouse',
            'datetime' => now(),
            'classification' => 'Minor',
            'status' => 'draft',
        ]));

        $this->actingAs($manager)
            ->post(route('incidents.transition', $incident), [
                'to_status' => 'draft_submitted',
                'remarks' => 'Transitioned for audit logging test',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $manager->id,
            'action' => 'transition',
            'module' => 'incidents',
        ]);
    }

    public function test_site_audit_approve_action_is_logged(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $owner = $this->userWithRole('Worker');
        $manager = $this->userWithRole('Manager');

        $audit = SiteAudit::query()->create([
            'created_by' => $owner->id,
            'reference_no' => 'AUD-LOG-0001',
            'site_name' => 'Audit Logging Plant',
            'audit_type' => 'internal',
            'status' => 'draft',
            'scheduled_for' => now()->toDateString(),
        ]);

        $this->actingAs($owner)
            ->post(route('site-audits.submit', $audit))
            ->assertRedirect();

        $this->actingAs($manager)
            ->post(route('site-audits.approve', $audit), ['remarks' => 'Manager approved'])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $manager->id,
            'action' => 'approve',
            'module' => 'audits',
        ]);
    }

    public function test_audit_logs_endpoint_validates_and_returns_data(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $manager = $this->userWithRole('Manager');

        $this->actingAs($manager)
            ->getJson(route('audit.logs', ['action' => 'create', 'per_page' => 10]))
            ->assertOk()
            ->assertJsonPath('message', 'Audit logs retrieved successfully.');

        $this->actingAs($manager)
            ->getJson(route('audit.logs', ['action' => 'invalid-action']))
            ->assertStatus(422);
    }

    public function test_audit_logs_include_request_metadata_when_available(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $manager = $this->userWithRole('Manager');

        $requestId = 'audit-test-request-123';

        $this->actingAs($manager)
            ->withHeader('X-Request-Id', $requestId)
            ->postJson(route('worker-tracking.workers.store'), [
                'employee_code' => 'WK-AUDIT-REQ-001',
                'full_name' => 'Request Metadata Worker',
                'status' => 'active',
            ])
            ->assertCreated();

        $log = AuditLog::query()->latest('id')->firstOrFail();
        $metadata = is_array($log->metadata) ? $log->metadata : [];

        $this->assertSame($requestId, $metadata['request_id'] ?? null);
        $this->assertSame('POST', $metadata['http_method'] ?? null);
        $this->assertSame('worker-tracking/workers', $metadata['path'] ?? null);
    }

    public function test_user_can_export_audit_logs_as_csv(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $manager = $this->userWithRole('Manager');

        $this->actingAs($manager)->postJson(route('worker-tracking.workers.store'), [
            'employee_code' => 'WK-AUDIT-EXP-001',
            'full_name' => 'Export Worker',
            'status' => 'active',
        ])->assertCreated();

        $response = $this->actingAs($manager)
            ->get(route('audit.logs.export', ['format' => 'csv']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('id,timestamp,user,action,module', $response->streamedContent());
    }

    public function test_cleanup_audit_logs_command_removes_old_records(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $manager = $this->userWithRole('Manager');

        $oldLog = AuditLog::query()->create([
            'user_id' => $manager->id,
            'action' => 'update',
            'module' => 'reports',
        ]);

        $oldLog->forceFill([
            'created_at' => now()->subDays(250),
            'updated_at' => now()->subDays(250),
        ])->save();

        $exitCode = Artisan::call(CleanupAuditLogsCommand::class, ['--days' => 180]);

        $this->assertSame(0, $exitCode);
        $this->assertDatabaseMissing('audit_logs', ['id' => $oldLog->id]);
    }

    private function requiredIncidentAttributes(User $reporter, array $overrides = []): array
    {
        $locationType = LocationType::query()->firstOrCreate(
            ['code' => 'LT-TST'],
            ['name' => 'Test Location Type', 'is_active' => true]
        );

        $location = IncidentLocation::query()->firstOrCreate(
            ['code' => 'LOC-TST'],
            ['name' => 'Test Location', 'location_type_id' => $locationType->id, 'is_active' => true]
        );

        if ((int) $location->location_type_id !== (int) $locationType->id) {
            $location->update(['location_type_id' => $locationType->id]);
        }

        $incidentType = IncidentType::query()->firstOrCreate(
            ['code' => 'IT-TST'],
            ['name' => 'Test Incident Type', 'is_active' => true]
        );

        $classification = IncidentClassification::query()->firstOrCreate(
            ['code' => 'IC-TST'],
            ['name' => 'Test Classification', 'is_active' => true]
        );

        $workPackage = WorkPackage::query()->firstOrCreate(
            ['code' => 'WP-TST'],
            ['name' => 'Test Work Package', 'is_active' => true]
        );

        $workActivity = WorkActivity::query()->firstOrCreate(
            ['code' => 'WA-TST'],
            ['name' => 'Test Work Activity', 'is_active' => true]
        );

        $subcontractor = Subcontractor::query()->first();

        return array_merge([
            'reported_by' => $reporter->id,
            'incident_reference_number' => 'INC-TST-'.Str::upper(Str::random(8)),
            'title' => 'Test Incident',
            'description' => 'Test incident description.',
            'incident_description' => 'Test incident description.',
            'incident_type_id' => $incidentType->id,
            'location_type_id' => $locationType->id,
            'location_id' => $location->id,
            'location' => $location->name,
            'other_location' => $location->name,
            'datetime' => now(),
            'incident_date' => now()->toDateString(),
            'incident_time' => now()->format('H:i:s'),
            'classification' => 'Major',
            'classification_id' => $classification->id,
            'status' => 'draft',
            'work_package_id' => $workPackage->id,
            'work_activity_id' => $workActivity->id,
            'immediate_response' => 'Immediate response executed.',
            'subcontractor_id' => $subcontractor?->id,
            'person_in_charge' => 'Test PIC',
            'subcontractor_contact_number' => '+60123456789',
        ], $overrides);
    }
}
