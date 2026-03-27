<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SyncRequest;
use App\Services\Api\SyncService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class SyncController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly SyncService $syncService)
    {
    }

    /**
     * POST /api/v1/sync
     *
     * Accepts offline-created or offline-modified records from the mobile device,
     * persists them using last-write-wins conflict resolution, then returns all
     * server-side records that have changed since the device's last sync point.
     *
     * Request example:
     * {
     *   "device_id": "abc123",
     *   "last_synced_at": "2026-03-27T08:00:00Z",
     *   "data": {
     *     "incidents": [
     *       { "temporary_id": "uuid", "title": "...", "classification": "Minor", ... }
     *     ],
     *     "attendance_logs": [...],
     *     "inspections": [...]
     *   }
     * }
     */
    public function sync(SyncRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user      = $request->user();

        $result = $this->syncService->sync(
            userId:       $user->id,
            deviceId:     $validated['device_id'],
            lastSyncedAt: $validated['last_synced_at'] ?? null,
            conflictStrategy: $validated['conflict_strategy'] ?? 'last_updated_wins',
            data:         $validated['data'] ?? [],
        );

        return $this->success(
            data:    $result,
            message: 'Sync completed successfully.',
            meta:    [
                'server_time' => $result['server_time'],
                'conflict_count' => count($result['conflicts'] ?? []),
                'conflict_strategy' => $result['conflict_strategy'] ?? 'last_updated_wins',
            ],
        );
    }
}
