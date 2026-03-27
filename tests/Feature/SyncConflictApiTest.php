<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Inspection;
use App\Models\InspectionChecklist;
use App\Models\NcrReport;
use App\Models\SiteAudit;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SyncConflictApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function mobileAuthHeaders(User $user): array
    {
        $plainPassword = 'password';
        $user->update(['password' => bcrypt($plainPassword)]);

        $login = $this->postJson(route('api.v1.auth.login'), [
            'email' => $user->email,
            'password' => $plainPassword,
            'device_name' => 'sync-conflict-device',
        ]);

        $login->assertOk();

        return [
            'Authorization' => 'Bearer '.$login->json('data.token'),
            'Accept' => 'application/json',
        ];
    }

    public function test_sync_logs_conflict_and_applies_local_change_when_local_timestamp_is_newer(): void
    {
        $user = User::factory()->create();
        $headers = $this->mobileAuthHeaders($user);
        $temporaryId = (string) Str::uuid();

        $lastSyncedAt = Carbon::now()->subHour()->startOfSecond();
        $serverUpdatedAt = Carbon::now()->subMinutes(8)->startOfSecond();
        $localUpdatedAt = Carbon::now()->subMinutes(2)->startOfSecond();

        $incident = Incident::query()->create([
            'reported_by' => $user->id,
            'title' => 'Server Title',
            'description' => 'Server description',
            'location' => 'Plant A',
            'datetime' => $lastSyncedAt->copy()->subDay(),
            'classification' => 'Minor',
            'status' => 'draft',
            'temporary_id' => $temporaryId,
            'local_created_at' => $lastSyncedAt->copy()->subDay(),
        ]);

        DB::table('incidents')->where('id', $incident->id)->update([
            'updated_at' => $serverUpdatedAt,
        ]);

        $response = $this->withHeaders($headers)->postJson(route('api.v1.sync'), [
            'device_id' => 'device-conflict-local-win',
            'last_synced_at' => $lastSyncedAt->toIso8601String(),
            'conflict_strategy' => 'last_updated_wins',
            'data' => [
                'incidents' => [[
                    'id' => $incident->id,
                    'temporary_id' => $temporaryId,
                    'title' => 'Local Title Wins',
                    'description' => 'Client changed this offline',
                    'location' => 'Plant B',
                    'datetime' => $localUpdatedAt->copy()->subHours(2)->toIso8601String(),
                    'classification' => 'Major',
                    'updated_at' => $localUpdatedAt->toIso8601String(),
                ]],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('meta.conflict_count', 1);
        $response->assertJsonPath('data.conflict_strategy', 'last_updated_wins');
        $response->assertJsonPath('data.conflicts.0.module', 'incidents');
        $response->assertJsonPath('data.conflicts.0.winner', 'local');
        $response->assertJsonPath('data.conflicts.0.requires_manual_review', false);

        $this->assertDatabaseHas('conflict_logs', [
            'module' => 'incidents',
            'record_id' => $temporaryId,
            'resolution_strategy' => 'last_updated_wins',
            'winner' => 'local',
        ]);

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'title' => 'Local Title Wins',
            'location' => 'Plant B',
            'classification' => 'Major',
        ]);
    }

    public function test_sync_logs_conflict_and_keeps_server_change_when_server_timestamp_is_newer(): void
    {
        $user = User::factory()->create();
        $headers = $this->mobileAuthHeaders($user);
        $temporaryId = (string) Str::uuid();

        $lastSyncedAt = Carbon::now()->subHour()->startOfSecond();
        $serverUpdatedAt = Carbon::now()->subMinute()->startOfSecond();
        $localUpdatedAt = Carbon::now()->subMinutes(5)->startOfSecond();

        $incident = Incident::query()->create([
            'reported_by' => $user->id,
            'title' => 'Server Version Survives',
            'description' => 'Server update',
            'location' => 'Warehouse 1',
            'datetime' => $lastSyncedAt->copy()->subDay(),
            'classification' => 'Moderate',
            'status' => 'draft',
            'temporary_id' => $temporaryId,
            'local_created_at' => $lastSyncedAt->copy()->subDay(),
        ]);

        DB::table('incidents')->where('id', $incident->id)->update([
            'updated_at' => $serverUpdatedAt,
        ]);

        $response = $this->withHeaders($headers)->postJson(route('api.v1.sync'), [
            'device_id' => 'device-conflict-server-win',
            'last_synced_at' => $lastSyncedAt->toIso8601String(),
            'conflict_strategy' => 'last_updated_wins',
            'data' => [
                'incidents' => [[
                    'id' => $incident->id,
                    'temporary_id' => $temporaryId,
                    'title' => 'Local Attempted Update',
                    'description' => 'Should not win',
                    'location' => 'Warehouse 2',
                    'datetime' => $localUpdatedAt->copy()->subHours(3)->toIso8601String(),
                    'classification' => 'Critical',
                    'updated_at' => $localUpdatedAt->toIso8601String(),
                ]],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('meta.conflict_count', 1);
        $response->assertJsonPath('data.conflicts.0.winner', 'server');
        $response->assertJsonPath('data.conflicts.0.resolution_strategy', 'last_updated_wins');

        $this->assertDatabaseHas('conflict_logs', [
            'module' => 'incidents',
            'record_id' => $temporaryId,
            'winner' => 'server',
        ]);

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'title' => 'Server Version Survives',
            'location' => 'Warehouse 1',
            'classification' => 'Moderate',
        ]);
    }

    public function test_sync_can_flag_conflict_for_manual_review_without_applying_mobile_change(): void
    {
        $user = User::factory()->create();
        $headers = $this->mobileAuthHeaders($user);
        $temporaryId = (string) Str::uuid();

        $lastSyncedAt = Carbon::now()->subHour()->startOfSecond();
        $serverUpdatedAt = Carbon::now()->subMinutes(9)->startOfSecond();
        $localUpdatedAt = Carbon::now()->subMinutes(1)->startOfSecond();

        $incident = Incident::query()->create([
            'reported_by' => $user->id,
            'title' => 'Needs Review',
            'description' => 'Server change exists',
            'location' => 'Zone C',
            'datetime' => $lastSyncedAt->copy()->subDay(),
            'classification' => 'Minor',
            'status' => 'draft',
            'temporary_id' => $temporaryId,
            'local_created_at' => $lastSyncedAt->copy()->subDay(),
        ]);

        DB::table('incidents')->where('id', $incident->id)->update([
            'updated_at' => $serverUpdatedAt,
        ]);

        $response = $this->withHeaders($headers)->postJson(route('api.v1.sync'), [
            'device_id' => 'device-conflict-manual-review',
            'last_synced_at' => $lastSyncedAt->toIso8601String(),
            'conflict_strategy' => 'manual_review',
            'data' => [
                'incidents' => [[
                    'id' => $incident->id,
                    'temporary_id' => $temporaryId,
                    'title' => 'Local Update Waiting Review',
                    'description' => 'Offline version',
                    'location' => 'Zone D',
                    'datetime' => $localUpdatedAt->copy()->subHours(4)->toIso8601String(),
                    'classification' => 'Critical',
                    'updated_at' => $localUpdatedAt->toIso8601String(),
                ]],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('meta.conflict_count', 1);
        $response->assertJsonPath('meta.conflict_strategy', 'manual_review');
        $response->assertJsonPath('data.conflicts.0.winner', 'manual_review');
        $response->assertJsonPath('data.conflicts.0.requires_manual_review', true);

        $this->assertDatabaseHas('conflict_logs', [
            'module' => 'incidents',
            'record_id' => $temporaryId,
            'resolution_strategy' => 'manual_review',
            'winner' => 'manual_review',
        ]);

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'title' => 'Needs Review',
            'location' => 'Zone C',
            'classification' => 'Minor',
        ]);
    }

    public function test_sync_logs_worker_conflict_and_applies_local_change_when_local_timestamp_is_newer(): void
    {
        $user = User::factory()->create();
        $headers = $this->mobileAuthHeaders($user);

        $lastSyncedAt = Carbon::now()->subHour()->startOfSecond();
        $serverUpdatedAt = Carbon::now()->subMinutes(10)->startOfSecond();
        $localUpdatedAt = Carbon::now()->subMinutes(3)->startOfSecond();

        $worker = Worker::query()->create([
            'user_id' => $user->id,
            'employee_code' => 'WK-1001',
            'full_name' => 'Server Worker',
            'phone' => '111111111',
            'department' => 'Ops',
            'position' => 'Rigger',
            'status' => 'active',
            'geofence_radius_meters' => 100,
        ]);

        DB::table('workers')->where('id', $worker->id)->update([
            'updated_at' => $serverUpdatedAt,
        ]);

        $response = $this->withHeaders($headers)->postJson(route('api.v1.sync'), [
            'device_id' => 'device-worker-local-win',
            'last_synced_at' => $lastSyncedAt->toIso8601String(),
            'conflict_strategy' => 'last_updated_wins',
            'data' => [
                'workers' => [[
                    'id' => $worker->id,
                    'full_name' => 'Local Worker Wins',
                    'phone' => '222222222',
                    'department' => 'Field Ops',
                    'position' => 'Supervisor',
                    'status' => 'inactive',
                    'last_latitude' => 3.14159,
                    'last_longitude' => 101.6869,
                    'last_seen_at' => $localUpdatedAt->copy()->subMinute()->toIso8601String(),
                    'updated_at' => $localUpdatedAt->toIso8601String(),
                ]],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('meta.conflict_count', 1);
        $response->assertJsonPath('data.conflicts.0.module', 'workers');
        $response->assertJsonPath('data.conflicts.0.record_id', (string) $worker->id);
        $response->assertJsonPath('data.conflicts.0.winner', 'local');

        $this->assertDatabaseHas('conflict_logs', [
            'module' => 'workers',
            'record_id' => (string) $worker->id,
            'winner' => 'local',
        ]);

        $this->assertDatabaseHas('workers', [
            'id' => $worker->id,
            'full_name' => 'Local Worker Wins',
            'department' => 'Field Ops',
            'position' => 'Supervisor',
            'status' => 'inactive',
        ]);
    }

    public function test_sync_logs_site_audit_conflict_and_keeps_server_change_when_server_timestamp_is_newer(): void
    {
        $user = User::factory()->create();
        $headers = $this->mobileAuthHeaders($user);

        $lastSyncedAt = Carbon::now()->subHour()->startOfSecond();
        $serverUpdatedAt = Carbon::now()->subMinute()->startOfSecond();
        $localUpdatedAt = Carbon::now()->subMinutes(6)->startOfSecond();

        $audit = SiteAudit::query()->create([
            'created_by' => $user->id,
            'reference_no' => 'AUD-'.Str::upper(Str::random(8)),
            'site_name' => 'Server Audit Site',
            'area' => 'Area 1',
            'audit_type' => 'internal',
            'status' => 'draft',
            'scope' => 'Server scope',
            'summary' => 'Server summary',
        ]);

        DB::table('site_audits')->where('id', $audit->id)->update([
            'updated_at' => $serverUpdatedAt,
        ]);

        $response = $this->withHeaders($headers)->postJson(route('api.v1.sync'), [
            'device_id' => 'device-audit-server-win',
            'last_synced_at' => $lastSyncedAt->toIso8601String(),
            'conflict_strategy' => 'last_updated_wins',
            'data' => [
                'site_audits' => [[
                    'id' => $audit->id,
                    'site_name' => 'Local Audit Attempt',
                    'area' => 'Area 2',
                    'audit_type' => 'external',
                    'status' => 'in_progress',
                    'scope' => 'Local scope',
                    'summary' => 'Local summary',
                    'updated_at' => $localUpdatedAt->toIso8601String(),
                ]],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('meta.conflict_count', 1);
        $response->assertJsonPath('data.conflicts.0.module', 'site_audits');
        $response->assertJsonPath('data.conflicts.0.winner', 'server');

        $this->assertDatabaseHas('conflict_logs', [
            'module' => 'site_audits',
            'record_id' => (string) $audit->id,
            'winner' => 'server',
        ]);

        $this->assertDatabaseHas('site_audits', [
            'id' => $audit->id,
            'site_name' => 'Server Audit Site',
            'area' => 'Area 1',
            'audit_type' => 'internal',
            'status' => 'draft',
        ]);
    }

    public function test_sync_can_flag_ncr_report_conflict_for_manual_review_without_applying_mobile_change(): void
    {
        $user = User::factory()->create();
        $headers = $this->mobileAuthHeaders($user);

        $lastSyncedAt = Carbon::now()->subHour()->startOfSecond();
        $serverUpdatedAt = Carbon::now()->subMinutes(12)->startOfSecond();
        $localUpdatedAt = Carbon::now()->subMinutes(1)->startOfSecond();

        $audit = SiteAudit::query()->create([
            'created_by' => $user->id,
            'reference_no' => 'AUD-'.Str::upper(Str::random(8)),
            'site_name' => 'NCR Parent Audit',
            'audit_type' => 'internal',
            'status' => 'draft',
        ]);

        $report = NcrReport::query()->create([
            'site_audit_id' => $audit->id,
            'reported_by' => $user->id,
            'reference_no' => 'NCR-'.Str::upper(Str::random(8)),
            'title' => 'Server NCR',
            'description' => 'Server-side version',
            'severity' => 'medium',
            'status' => 'open',
        ]);

        DB::table('ncr_reports')->where('id', $report->id)->update([
            'updated_at' => $serverUpdatedAt,
        ]);

        $response = $this->withHeaders($headers)->postJson(route('api.v1.sync'), [
            'device_id' => 'device-ncr-manual-review',
            'last_synced_at' => $lastSyncedAt->toIso8601String(),
            'conflict_strategy' => 'manual_review',
            'data' => [
                'ncr_reports' => [[
                    'id' => $report->id,
                    'site_audit_id' => $audit->id,
                    'title' => 'Local NCR Waiting Review',
                    'description' => 'Offline version',
                    'severity' => 'critical',
                    'status' => 'verified',
                    'updated_at' => $localUpdatedAt->toIso8601String(),
                ]],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('meta.conflict_count', 1);
        $response->assertJsonPath('data.conflicts.0.module', 'ncr_reports');
        $response->assertJsonPath('data.conflicts.0.winner', 'manual_review');
        $response->assertJsonPath('data.conflicts.0.requires_manual_review', true);

        $this->assertDatabaseHas('conflict_logs', [
            'module' => 'ncr_reports',
            'record_id' => (string) $report->id,
            'resolution_strategy' => 'manual_review',
            'winner' => 'manual_review',
        ]);

        $this->assertDatabaseHas('ncr_reports', [
            'id' => $report->id,
            'title' => 'Server NCR',
            'severity' => 'medium',
            'status' => 'open',
        ]);
    }

    public function test_sync_logs_inspection_conflict_and_applies_local_change_when_local_timestamp_is_newer(): void
    {
        $user = User::factory()->create();
        $headers = $this->mobileAuthHeaders($user);
        $offlineUuid = (string) Str::uuid();

        $lastSyncedAt = Carbon::now()->subHour()->startOfSecond();
        $serverUpdatedAt = Carbon::now()->subMinutes(7)->startOfSecond();
        $localUpdatedAt = Carbon::now()->subMinutes(2)->startOfSecond();

        $checklist = InspectionChecklist::query()->create([
            'offline_uuid' => (string) Str::uuid(),
            'title' => 'Sync Conflict Checklist',
            'version' => 1,
            'is_active' => true,
            'sync_status' => 'synced',
            'last_synced_at' => now(),
        ]);

        $inspection = Inspection::query()->create([
            'offline_uuid' => $offlineUuid,
            'inspection_checklist_id' => $checklist->id,
            'inspector_id' => $user->id,
            'status' => 'draft',
            'location' => 'Server Location',
            'notes' => 'Server notes',
            'sync_status' => 'pending_sync',
        ]);

        DB::table('inspections')->where('id', $inspection->id)->update([
            'updated_at' => $serverUpdatedAt,
        ]);

        $response = $this->withHeaders($headers)->postJson(route('api.v1.sync'), [
            'device_id' => 'device-inspection-local-win',
            'last_synced_at' => $lastSyncedAt->toIso8601String(),
            'conflict_strategy' => 'last_updated_wins',
            'data' => [
                'inspections' => [[
                    'id' => $inspection->id,
                    'offline_uuid' => $offlineUuid,
                    'location' => 'Local Inspection Location',
                    'notes' => 'Local notes win',
                    'status' => 'submitted',
                    'sync_status' => 'synced',
                    'performed_at' => $localUpdatedAt->copy()->subHours(5)->toIso8601String(),
                    'submitted_at' => $localUpdatedAt->copy()->subMinute()->toIso8601String(),
                    'updated_at' => $localUpdatedAt->toIso8601String(),
                ]],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('meta.conflict_count', 1);
        $response->assertJsonPath('data.conflicts.0.module', 'inspections');
        $response->assertJsonPath('data.conflicts.0.record_id', $offlineUuid);
        $response->assertJsonPath('data.conflicts.0.winner', 'local');

        $this->assertDatabaseHas('conflict_logs', [
            'module' => 'inspections',
            'record_id' => $offlineUuid,
            'winner' => 'local',
        ]);

        $this->assertDatabaseHas('inspections', [
            'id' => $inspection->id,
            'location' => 'Local Inspection Location',
            'notes' => 'Local notes win',
            'status' => 'submitted',
            'sync_status' => 'synced',
        ]);
    }
}
