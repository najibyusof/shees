<?php

namespace App\OpenAPI;

use OpenAPI\Annotations as OA;

/**
 * @OA\Tag(name="Sync", description="Offline synchronization")
 *
 * @OA\PathItem(
 *     path="/api/v1/sync",
 *     @OA\Post(
 *         tags={"Sync"},
 *         operationId="v1Sync",
 *         summary="Sync offline payload",
 *         security={{"bearer_token": {}}},
 *         @OA\RequestBody(
 *             required=true,
 *             @OA\JsonContent(
 *                 required={"device_id", "data"},
 *                 @OA\Property(property="device_id", type="string", example="android-8f0a12"),
 *                 @OA\Property(property="last_synced_at", type="string", format="date-time", nullable=true),
 *                 @OA\Property(property="conflict_strategy", type="string", nullable=true, example="manual_review"),
 *                 @OA\Property(property="data", type="object",
 *                     @OA\Property(property="incidents", type="array", @OA\Items(type="object")),
 *                     @OA\Property(property="attendance_logs", type="array", @OA\Items(type="object")),
 *                     @OA\Property(property="workers", type="array", @OA\Items(type="object")),
 *                     @OA\Property(property="site_audits", type="array", @OA\Items(type="object")),
 *                     @OA\Property(property="ncr_reports", type="array", @OA\Items(type="object")),
 *                     @OA\Property(property="inspections", type="array", @OA\Items(type="object"))
 *                 )
 *             )
 *         ),
 *         @OA\Response(
 *             response=200,
 *             description="Sync processed",
 *             @OA\JsonContent(
 *                 @OA\Property(property="success", type="boolean", example=true),
 *                 @OA\Property(property="message", type="string", example="Sync completed."),
 *                 @OA\Property(property="data", type="object",
 *                     @OA\Property(property="server_time", type="string", format="date-time"),
 *                     @OA\Property(property="conflict_count", type="integer", example=0),
 *                     @OA\Property(property="conflict_strategy", type="string", example="manual_review"),
 *                     @OA\Property(property="merged", type="object"),
 *                     @OA\Property(property="conflicts", type="array", @OA\Items(type="object"))
 *                 )
 *             )
 *         ),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 */
class SyncEndpoints
{
    // Sync endpoint annotation carrier.
}
