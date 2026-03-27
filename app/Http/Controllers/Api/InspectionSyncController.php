<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InspectionSyncConflict;
use App\Models\InspectionSyncJob;
use App\Services\InspectionSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InspectionSyncController extends Controller
{
    public function __construct(private readonly InspectionSyncService $syncService) {}

    public function contract(): JsonResponse
    {
        return response()->json([
            'data' => [
                'name' => InspectionSyncService::CONTRACT_NAME,
                'version' => InspectionSyncService::CONTRACT_VERSION,
                'capabilities' => [
                    'upload' => true,
                    'download' => true,
                    'auto_merge' => ['inspection_response', 'inspection_image'],
                ],
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }

    public function enqueueUpload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inspection_id' => ['nullable', 'integer', 'exists:inspections,id'],
            'device_identifier' => ['nullable', 'string', 'max:255'],
            'idempotency_key' => ['nullable', 'string', 'max:128'],
            'entity_type' => ['required', 'string', 'max:100'],
            'entity_offline_uuid' => ['nullable', 'string', 'max:255'],
            'operation' => ['nullable', 'string', 'max:100'],
            'contract_name' => ['required', 'string', 'max:100'],
            'contract_version' => ['required', 'integer', 'min:1'],
            'payload' => ['required', 'array'],
            'sync_batch_uuid' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['user_id'] = $request->user()?->id;

        $result = $this->syncService->enqueueUploadJob($validated);
        $job = $result['job'];
        $created = $result['created'];

        return response()->json([
            'contract' => [
                'name' => $job->contract_name,
                'version' => $job->contract_version,
            ],
            'meta' => [
                'idempotency_replay' => ! $created,
            ],
            'data' => $job,
        ], $created ? 201 : 200);
    }

    public function metrics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return response()->json([
            'data' => $this->syncService->metrics(
                $validated['from'] ?? null,
                $validated['to'] ?? null
            ),
        ]);
    }

    public function pending(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_identifier' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $jobs = $this->syncService->getPendingJobs(
            $validated['device_identifier'] ?? null,
            (int) ($validated['limit'] ?? 50)
        );

        return response()->json([
            'contract' => [
                'name' => \App\Services\InspectionSyncService::CONTRACT_NAME,
                'version' => \App\Services\InspectionSyncService::CONTRACT_VERSION,
            ],
            'data' => $jobs,
        ]);
    }

    public function acknowledge(InspectionSyncJob $inspectionSyncJob): JsonResponse
    {
        return response()->json([
            'data' => $this->syncService->acknowledge($inspectionSyncJob),
        ]);
    }

    public function markConflict(Request $request, InspectionSyncJob $inspectionSyncJob): JsonResponse
    {
        $validated = $request->validate([
            'server_payload' => ['required', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        $conflict = $this->syncService->recordConflict(
            $inspectionSyncJob,
            $validated['server_payload'],
            $validated['notes'] ?? null
        );

        return response()->json(['data' => $conflict], 201);
    }

    public function resolveConflict(Request $request, InspectionSyncConflict $inspectionSyncConflict): JsonResponse
    {
        $validated = $request->validate([
            'strategy' => ['required', 'string', 'in:server_wins,client_wins,merge,ignore'],
            'notes' => ['nullable', 'string'],
        ]);

        $resolved = $this->syncService->resolveConflict(
            $inspectionSyncConflict,
            $validated['strategy'],
            $validated['notes'] ?? null,
            $request->user()
        );

        return response()->json(['data' => $resolved]);
    }
}
