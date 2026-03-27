<?php

namespace Tests\Feature;

use App\Models\Inspection;
use App\Models\InspectionChecklist;
use App\Models\InspectionChecklistItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InspectionSyncApiTest extends TestCase
{
    use RefreshDatabase;

    private function mobileAuthHeaders(User $user): array
    {
        $plainPassword = 'password';
        $user->update(['password' => bcrypt($plainPassword)]);

        $login = $this->postJson(route('api.inspection.auth.login'), [
            'email' => $user->email,
            'password' => $plainPassword,
            'device_name' => 'sync-device',
        ]);

        $login->assertCreated();
        $token = $login->json('data.token');

        return [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ];
    }

    public function test_sync_api_can_enqueue_and_ack_job(): void
    {
        $user = User::factory()->create();
        $spoofedUser = User::factory()->create();
        $headers = $this->mobileAuthHeaders($user);

        $checklist = InspectionChecklist::query()->create([
            'offline_uuid' => (string) Str::uuid(),
            'title' => 'Sync Checklist',
            'version' => 1,
            'is_active' => true,
            'sync_status' => 'synced',
            'last_synced_at' => now(),
        ]);

        InspectionChecklistItem::query()->create([
            'inspection_checklist_id' => $checklist->id,
            'offline_uuid' => (string) Str::uuid(),
            'label' => 'Item A',
            'item_type' => 'boolean',
            'is_required' => true,
            'sort_order' => 0,
            'sync_status' => 'synced',
            'last_synced_at' => now(),
        ]);

        $inspection = Inspection::query()->create([
            'offline_uuid' => (string) Str::uuid(),
            'inspection_checklist_id' => $checklist->id,
            'inspector_id' => $user->id,
            'status' => 'draft',
            'sync_status' => 'pending_sync',
        ]);

        $enqueue = $this->withHeaders($headers)->postJson(route('api.inspection.sync.upload'), [
            'inspection_id' => $inspection->id,
            'user_id' => $spoofedUser->id,
            'device_identifier' => 'device-sync-1',
            'entity_type' => 'inspection',
            'entity_offline_uuid' => $inspection->offline_uuid,
            'operation' => 'upsert',
            'contract_name' => 'inspection-sync',
            'contract_version' => 1,
            'payload' => [
                'inspection' => ['id' => $inspection->id, 'status' => 'draft'],
            ],
        ]);

        $enqueue->assertCreated();
        $enqueue->assertJsonPath('data.user_id', $user->id);
        $jobId = $enqueue->json('data.id');

        $pending = $this->withHeaders($headers)->getJson(route('api.inspection.sync.pending', ['device_identifier' => 'device-sync-1']));
        $pending->assertOk();
        $pending->assertJsonPath('data.0.id', $jobId);
        $pending->assertJsonPath('contract.version', 1);

        $ack = $this->withHeaders($headers)->postJson(route('api.inspection.sync.jobs.ack', $jobId));
        $ack->assertOk();
        $ack->assertJsonPath('data.status', 'acked');

        $this->assertDatabaseHas('inspection_sync_jobs', [
            'id' => $jobId,
            'status' => 'acked',
        ]);
    }

    public function test_sync_api_can_record_and_resolve_conflict(): void
    {
        $user = User::factory()->create();
        $headers = $this->mobileAuthHeaders($user);

        $checklist = InspectionChecklist::query()->create([
            'offline_uuid' => (string) Str::uuid(),
            'title' => 'Conflict Checklist',
            'version' => 1,
            'is_active' => true,
            'sync_status' => 'synced',
            'last_synced_at' => now(),
        ]);

        $inspection = Inspection::query()->create([
            'offline_uuid' => (string) Str::uuid(),
            'inspection_checklist_id' => $checklist->id,
            'inspector_id' => $user->id,
            'status' => 'draft',
            'sync_status' => 'pending_sync',
        ]);

        $enqueue = $this->withHeaders($headers)->postJson(route('api.inspection.sync.upload'), [
            'inspection_id' => $inspection->id,
            'user_id' => $user->id,
            'entity_type' => 'inspection_response',
            'entity_offline_uuid' => $inspection->offline_uuid,
            'contract_name' => 'inspection-sync',
            'contract_version' => 1,
            'payload' => [
                'response_value' => 'Client Value',
                'comment' => 'Client comment',
                'is_non_compliant' => false,
            ],
        ]);

        $jobId = $enqueue->json('data.id');

        $conflictResponse = $this->withHeaders($headers)->postJson(route('api.inspection.sync.jobs.conflict', $jobId), [
            'server_payload' => [
                'response_value' => 'Server Value',
                'comment' => 'Server comment',
                'is_non_compliant' => true,
            ],
            'notes' => 'Version mismatch from mobile sync',
        ]);

        $conflictResponse->assertCreated();
        $conflictId = $conflictResponse->json('data.id');
        $conflictResponse->assertJsonPath('data.resolution_status', 'resolved');

        $resolve = $this->withHeaders($headers)->postJson(route('api.inspection.sync.conflicts.resolve', $conflictId), [
            'strategy' => 'merge',
            'notes' => 'Merged server/client values',
            'resolved_by' => $user->id,
        ]);

        $resolve->assertOk();
        $resolve->assertJsonPath('data.resolution_status', 'resolved');

        $this->assertDatabaseHas('inspection_sync_conflicts', [
            'id' => $conflictId,
            'resolution_status' => 'resolved',
            'resolved_by' => $user->id,
        ]);

        $this->assertDatabaseHas('inspection_sync_jobs', [
            'id' => $jobId,
            'status' => 'applied',
        ]);
    }

    public function test_sync_upload_supports_idempotency_replay(): void
    {
        $user = User::factory()->create();
        $headers = $this->mobileAuthHeaders($user);

        $checklist = InspectionChecklist::query()->create([
            'offline_uuid' => (string) Str::uuid(),
            'title' => 'Idempotency Checklist',
            'version' => 1,
            'is_active' => true,
            'sync_status' => 'synced',
            'last_synced_at' => now(),
        ]);

        $inspection = Inspection::query()->create([
            'offline_uuid' => (string) Str::uuid(),
            'inspection_checklist_id' => $checklist->id,
            'inspector_id' => $user->id,
            'status' => 'draft',
            'sync_status' => 'pending_sync',
        ]);

        $payload = [
            'inspection_id' => $inspection->id,
            'device_identifier' => 'device-sync-idempotent',
            'idempotency_key' => 'dup-key-001',
            'entity_type' => 'inspection',
            'entity_offline_uuid' => $inspection->offline_uuid,
            'operation' => 'upsert',
            'contract_name' => 'inspection-sync',
            'contract_version' => 1,
            'payload' => ['inspection' => ['id' => $inspection->id, 'status' => 'draft']],
        ];

        $first = $this->withHeaders($headers)->postJson(route('api.inspection.sync.upload'), $payload);
        $first->assertCreated();
        $first->assertJsonPath('meta.idempotency_replay', false);

        $second = $this->withHeaders($headers)->postJson(route('api.inspection.sync.upload'), $payload);
        $second->assertOk();
        $second->assertJsonPath('meta.idempotency_replay', true);
        $second->assertJsonPath('data.id', $first->json('data.id'));

        $this->assertSame(1, \App\Models\InspectionSyncJob::query()
            ->where('user_id', $user->id)
            ->where('device_identifier', 'device-sync-idempotent')
            ->where('idempotency_key', 'dup-key-001')
            ->count());
    }

    public function test_sync_metrics_endpoint_returns_aggregate_telemetry(): void
    {
        $user = User::factory()->create();
        $headers = $this->mobileAuthHeaders($user);

        $checklist = InspectionChecklist::query()->create([
            'offline_uuid' => (string) Str::uuid(),
            'title' => 'Metrics Checklist',
            'version' => 1,
            'is_active' => true,
            'sync_status' => 'synced',
            'last_synced_at' => now(),
        ]);

        $inspection = Inspection::query()->create([
            'offline_uuid' => (string) Str::uuid(),
            'inspection_checklist_id' => $checklist->id,
            'inspector_id' => $user->id,
            'status' => 'draft',
            'sync_status' => 'pending_sync',
        ]);

        $enqueue = $this->withHeaders($headers)->postJson(route('api.inspection.sync.upload'), [
            'inspection_id' => $inspection->id,
            'device_identifier' => 'device-sync-metrics',
            'idempotency_key' => 'metrics-key-1',
            'entity_type' => 'inspection',
            'entity_offline_uuid' => $inspection->offline_uuid,
            'operation' => 'upsert',
            'contract_name' => 'inspection-sync',
            'contract_version' => 1,
            'payload' => ['inspection' => ['id' => $inspection->id, 'status' => 'draft']],
        ]);

        $enqueue->assertCreated();
        $jobId = $enqueue->json('data.id');

        $this->withHeaders($headers)
            ->postJson(route('api.inspection.sync.jobs.ack', $jobId))
            ->assertOk();

        $metrics = $this->withHeaders($headers)
            ->getJson(route('api.inspection.sync.metrics'));

        $metrics->assertOk();
        $metrics->assertJsonPath('data.totals.jobs', 1);
        $metrics->assertJsonPath('data.totals.idempotent_jobs', 1);
        $metrics->assertJsonPath('data.status_counts.acked', 1);
        $metrics->assertJsonPath('data.totals.open_conflicts', 0);
    }
}
