<?php

namespace App\Services;

use App\Models\Inspection;
use App\Models\InspectionSyncConflict;
use App\Models\InspectionSyncJob;
use App\Models\User;
use Illuminate\Support\Carbon;

class InspectionSyncService
{
    public const CONTRACT_NAME = 'inspection-sync';
    public const CONTRACT_VERSION = 1;

    public function enqueueUploadJob(array $data): array
    {
        $contractVersion = (int) ($data['contract_version'] ?? self::CONTRACT_VERSION);
        $this->assertSupportedContract($contractVersion, $data['contract_name'] ?? self::CONTRACT_NAME);

        $idempotencyKey = $data['idempotency_key'] ?? null;
        if ($idempotencyKey) {
            $existing = InspectionSyncJob::query()
                ->where('direction', 'upload')
                ->where('user_id', $data['user_id'] ?? null)
                ->where('device_identifier', $data['device_identifier'] ?? null)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return [
                    'job' => $existing,
                    'created' => false,
                ];
            }
        }

        $job = InspectionSyncJob::query()->create([
            'inspection_id' => $data['inspection_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'device_identifier' => $data['device_identifier'] ?? null,
            'direction' => 'upload',
            'entity_type' => $data['entity_type'],
            'entity_offline_uuid' => $data['entity_offline_uuid'] ?? null,
            'operation' => $data['operation'] ?? 'upsert',
            'contract_name' => $data['contract_name'] ?? self::CONTRACT_NAME,
            'contract_version' => $contractVersion,
            'payload' => $data['payload'] ?? [],
            'status' => 'pending',
            'sync_batch_uuid' => $data['sync_batch_uuid'] ?? null,
            'idempotency_key' => $idempotencyKey,
            'received_at' => now(),
        ]);

        return [
            'job' => $job,
            'created' => true,
        ];
    }

    public function enqueueDownloadForInspection(Inspection $inspection, string $operation = 'upsert'): InspectionSyncJob
    {
        return InspectionSyncJob::query()->create([
            'inspection_id' => $inspection->id,
            'user_id' => $inspection->inspector_id,
            'direction' => 'download',
            'entity_type' => 'inspection',
            'entity_offline_uuid' => $inspection->offline_uuid,
            'operation' => $operation,
            'contract_name' => self::CONTRACT_NAME,
            'contract_version' => self::CONTRACT_VERSION,
            'payload' => $inspection->load(['responses.images', 'responses.checklistItem'])->toArray(),
            'status' => 'pending',
            'received_at' => now(),
        ]);
    }

    public function getPendingJobs(?string $deviceIdentifier = null, int $limit = 50)
    {
        return InspectionSyncJob::query()
            ->whereIn('status', ['pending', 'conflict'])
            ->when($deviceIdentifier, function ($query, $deviceIdentifier) {
                $query->where(function ($inner) use ($deviceIdentifier) {
                    $inner->whereNull('device_identifier')
                        ->orWhere('device_identifier', $deviceIdentifier);
                });
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function acknowledge(InspectionSyncJob $job): InspectionSyncJob
    {
        $startedAt = $job->processing_started_at ?: now();
        $finishedAt = now();

        $job->update([
            'status' => 'acked',
            'processing_started_at' => $startedAt,
            'processing_finished_at' => $finishedAt,
            'processing_latency_ms' => $this->latencyMs($job->received_at, $finishedAt),
            'acknowledged_at' => now(),
            'processed_at' => $job->processed_at ?? now(),
        ]);

        return $job->refresh();
    }

    public function markApplied(InspectionSyncJob $job): InspectionSyncJob
    {
        $startedAt = $job->processing_started_at ?: now();
        $finishedAt = now();

        $job->update([
            'status' => 'applied',
            'processing_started_at' => $startedAt,
            'processing_finished_at' => $finishedAt,
            'processing_latency_ms' => $this->latencyMs($job->received_at, $finishedAt),
            'processed_at' => now(),
        ]);

        return $job->refresh();
    }

    public function recordConflict(InspectionSyncJob $job, array $serverPayload, ?string $notes = null): InspectionSyncConflict
    {
        $startedAt = $job->processing_started_at ?: now();
        $finishedAt = now();
        $autoResolved = $this->autoMergePayload($job->entity_type, $job->payload ?? [], $serverPayload);

        if ($autoResolved !== null) {
            $job->update([
                'payload' => $autoResolved,
                'status' => 'applied',
                'processing_started_at' => $startedAt,
                'processing_finished_at' => $finishedAt,
                'processing_latency_ms' => $this->latencyMs($job->received_at, $finishedAt),
                'processed_at' => now(),
            ]);

            return InspectionSyncConflict::query()->create([
                'inspection_sync_job_id' => $job->id,
                'inspection_id' => $job->inspection_id,
                'entity_type' => $job->entity_type,
                'entity_offline_uuid' => $job->entity_offline_uuid,
                'conflict_type' => 'version_mismatch',
                'client_payload' => $job->payload,
                'server_payload' => $serverPayload,
                'resolution_status' => 'resolved',
                'resolution_notes' => $notes ?: 'Auto-merged by entity policy.',
                'resolved_at' => now(),
            ]);
        }

        $job->update([
            'status' => 'conflict',
            'processing_started_at' => $startedAt,
            'processing_finished_at' => $finishedAt,
            'processing_latency_ms' => $this->latencyMs($job->received_at, $finishedAt),
            'processed_at' => now(),
            'error_message' => $notes,
        ]);

        return InspectionSyncConflict::query()->create([
            'inspection_sync_job_id' => $job->id,
            'inspection_id' => $job->inspection_id,
            'entity_type' => $job->entity_type,
            'entity_offline_uuid' => $job->entity_offline_uuid,
            'conflict_type' => 'version_mismatch',
            'client_payload' => $job->payload,
            'server_payload' => $serverPayload,
            'resolution_status' => 'open',
            'resolution_notes' => $notes,
        ]);
    }

    public function resolveConflict(
        InspectionSyncConflict $conflict,
        string $strategy,
        ?string $notes = null,
        ?User $resolver = null
    ): InspectionSyncConflict {
        $resolutionStatus = $strategy === 'ignore' ? 'ignored' : 'resolved';

        $conflict->update([
            'resolution_status' => $resolutionStatus,
            'resolution_notes' => $notes,
            'resolved_by' => $resolver?->id,
            'resolved_at' => now(),
        ]);

        if ($conflict->syncJob) {
            $this->markApplied($conflict->syncJob);
        }

        return $conflict->refresh();
    }

    public function metrics(?string $from = null, ?string $to = null): array
    {
        $query = InspectionSyncJob::query();

        if ($from) {
            $query->where('created_at', '>=', Carbon::parse($from));
        }

        if ($to) {
            $query->where('created_at', '<=', Carbon::parse($to));
        }

        $total = (clone $query)->count();
        $statusRows = (clone $query)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->get();

        $statusCounts = [];
        foreach ($statusRows as $row) {
            $statusCounts[$row->status] = (int) $row->aggregate;
        }

        $latency = (clone $query)
            ->whereNotNull('processing_latency_ms')
            ->selectRaw('AVG(processing_latency_ms) as avg_latency_ms, MIN(processing_latency_ms) as min_latency_ms, MAX(processing_latency_ms) as max_latency_ms')
            ->first();

        return [
            'window' => [
                'from' => $from,
                'to' => $to,
            ],
            'totals' => [
                'jobs' => $total,
                'open_conflicts' => InspectionSyncConflict::query()->where('resolution_status', 'open')->count(),
                'idempotent_jobs' => (clone $query)->whereNotNull('idempotency_key')->count(),
            ],
            'status_counts' => $statusCounts,
            'latency_ms' => [
                'average' => isset($latency->avg_latency_ms) ? (float) $latency->avg_latency_ms : null,
                'min' => isset($latency->min_latency_ms) ? (int) $latency->min_latency_ms : null,
                'max' => isset($latency->max_latency_ms) ? (int) $latency->max_latency_ms : null,
            ],
        ];
    }

    private function assertSupportedContract(int $version, string $name): void
    {
        if ($name !== self::CONTRACT_NAME || $version !== self::CONTRACT_VERSION) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'contract_version' => 'Unsupported sync contract. Expected '.self::CONTRACT_NAME.' v'.self::CONTRACT_VERSION.'.',
            ]);
        }
    }

    private function autoMergePayload(string $entityType, array $clientPayload, array $serverPayload): ?array
    {
        return match ($entityType) {
            'inspection_response' => [
                'response_value' => $clientPayload['response_value'] ?? $serverPayload['response_value'] ?? null,
                'comment' => $clientPayload['comment'] ?? $serverPayload['comment'] ?? null,
                'is_non_compliant' => (bool) ($clientPayload['is_non_compliant'] ?? $serverPayload['is_non_compliant'] ?? false),
            ],
            'inspection_image' => [
                'images' => array_values(array_unique(array_merge(
                    $serverPayload['images'] ?? [],
                    $clientPayload['images'] ?? []
                ), SORT_REGULAR)),
            ],
            default => null,
        };
    }

    private function latencyMs(?Carbon $receivedAt, Carbon $finishedAt): int
    {
        $baseline = $receivedAt ?: $finishedAt;

        return (int) max(0, $baseline->diffInMilliseconds($finishedAt, false));
    }
}
