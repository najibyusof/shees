<?php

namespace App\OpenAPI;

use OpenAPI\Annotations as OA;

/**
 * @OA\Tag(name="Workers", description="Worker and attendance management")
 *
 * @OA\PathItem(
 *     path="/api/v1/workers",
 *     @OA\Get(tags={"Workers"}, operationId="v1WorkersIndex", summary="List workers", security={{"bearer_token": {}}}, @OA\Parameter(name="status", in="query", @OA\Schema(type="string")), @OA\Parameter(name="department", in="query", @OA\Schema(type="string")), @OA\Parameter(name="search", in="query", @OA\Schema(type="string")), @OA\Response(response=200, description="Workers list", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Success"), @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Worker")))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))),
 *     @OA\Post(tags={"Workers"}, operationId="v1WorkersStore", summary="Create worker", security={{"bearer_token": {}}}, @OA\RequestBody(required=true, @OA\JsonContent(required={"employee_code", "full_name"}, @OA\Property(property="employee_code", type="string", example="WRK-0090"), @OA\Property(property="full_name", type="string", example="Jane Smith"), @OA\Property(property="department", type="string", nullable=true), @OA\Property(property="position", type="string", nullable=true), @OA\Property(property="phone", type="string", nullable=true), @OA\Property(property="status", type="string", nullable=true, example="active"), @OA\Property(property="geofence_center_latitude", type="number", format="float", nullable=true), @OA\Property(property="geofence_center_longitude", type="number", format="float", nullable=true), @OA\Property(property="geofence_radius_meters", type="number", format="float", nullable=true))), @OA\Response(response=201, description="Worker created", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Created"), @OA\Property(property="data", ref="#/components/schemas/Worker"))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")))
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/workers/{worker}",
 *     @OA\Parameter(name="worker", in="path", required=true, @OA\Schema(type="integer", example=90)),
 *     @OA\Get(tags={"Workers"}, operationId="v1WorkersShow", summary="Show worker", security={{"bearer_token": {}}}, @OA\Response(response=200, description="Worker details", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Success"), @OA\Property(property="data", ref="#/components/schemas/Worker"))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))),
 *     @OA\Put(tags={"Workers"}, operationId="v1WorkersUpdate", summary="Update worker", security={{"bearer_token": {}}}, @OA\RequestBody(required=true, @OA\JsonContent(@OA\Property(property="employee_code", type="string"), @OA\Property(property="full_name", type="string"), @OA\Property(property="department", type="string", nullable=true), @OA\Property(property="position", type="string", nullable=true), @OA\Property(property="phone", type="string", nullable=true), @OA\Property(property="status", type="string", nullable=true))), @OA\Response(response=200, description="Worker updated", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Updated"), @OA\Property(property="data", ref="#/components/schemas/Worker"))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))),
 *     @OA\Delete(tags={"Workers"}, operationId="v1WorkersDelete", summary="Delete worker", security={{"bearer_token": {}}}, @OA\Response(response=200, description="Worker deleted", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Deleted"), @OA\Property(property="data", type="object", nullable=true))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")))
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/workers/{worker}/attendance",
 *     @OA\Post(
 *         tags={"Workers"},
 *         operationId="v1WorkersAttendanceLog",
 *         summary="Log worker attendance event",
 *         security={{"bearer_token": {}}},
 *         @OA\Parameter(name="worker", in="path", required=true, @OA\Schema(type="integer", example=90)),
 *         @OA\RequestBody(required=true, @OA\JsonContent(
 *             required={"event_type", "latitude", "longitude"},
 *             @OA\Property(property="event_type", type="string", example="check_in"),
 *             @OA\Property(property="latitude", type="number", format="float", example=-6.201234),
 *             @OA\Property(property="longitude", type="number", format="float", example=106.816123),
 *             @OA\Property(property="accuracy_meters", type="number", format="float", nullable=true),
 *             @OA\Property(property="speed_mps", type="number", format="float", nullable=true),
 *             @OA\Property(property="heading_degrees", type="number", format="float", nullable=true),
 *             @OA\Property(property="temporary_id", type="string", nullable=true),
 *             @OA\Property(property="local_created_at", type="string", format="date-time", nullable=true)
 *         )),
 *         @OA\Response(response=201, description="Attendance logged", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Attendance event logged."), @OA\Property(property="data", type="object"))),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 */
class WorkerEndpoints
{
    // Worker endpoint annotation carrier.
}
