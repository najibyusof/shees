<?php

namespace App\OpenAPI;

use OpenAPI\Annotations as OA;

/**
 * @OA\Tag(name="Inspections", description="Inspection management")
 *
 * @OA\PathItem(
 *     path="/api/v1/inspections",
 *     @OA\Get(tags={"Inspections"}, operationId="v1InspectionsIndex", summary="List inspections", security={{"bearer_token": {}}}, @OA\Parameter(name="status", in="query", @OA\Schema(type="string")), @OA\Parameter(name="inspection_checklist_id", in="query", @OA\Schema(type="integer")), @OA\Response(response=200, description="Inspections list", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Success"), @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Inspection")))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))),
 *     @OA\Post(tags={"Inspections"}, operationId="v1InspectionsStore", summary="Create inspection", security={{"bearer_token": {}}}, @OA\RequestBody(required=true, @OA\JsonContent(required={"inspection_checklist_id"}, @OA\Property(property="inspection_checklist_id", type="integer", example=4), @OA\Property(property="performed_at", type="string", format="date-time", nullable=true), @OA\Property(property="location", type="string", nullable=true), @OA\Property(property="notes", type="string", nullable=true), @OA\Property(property="offline_uuid", type="string", nullable=true))), @OA\Response(response=201, description="Inspection created", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Created"), @OA\Property(property="data", ref="#/components/schemas/Inspection"))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")))
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/inspections/{inspection}",
 *     @OA\Parameter(name="inspection", in="path", required=true, @OA\Schema(type="integer", example=52)),
 *     @OA\Get(tags={"Inspections"}, operationId="v1InspectionsShow", summary="Show inspection", security={{"bearer_token": {}}}, @OA\Response(response=200, description="Inspection details", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Success"), @OA\Property(property="data", ref="#/components/schemas/Inspection"))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))),
 *     @OA\Put(tags={"Inspections"}, operationId="v1InspectionsUpdate", summary="Update inspection", security={{"bearer_token": {}}}, @OA\RequestBody(required=true, @OA\JsonContent(@OA\Property(property="status", type="string", nullable=true), @OA\Property(property="responses", type="array", @OA\Items(type="object", @OA\Property(property="checklist_item_id", type="integer"), @OA\Property(property="response_value", type="string", nullable=true), @OA\Property(property="is_non_compliant", type="boolean", nullable=true), @OA\Property(property="comment", type="string", nullable=true))))), @OA\Response(response=200, description="Inspection updated", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Updated"), @OA\Property(property="data", ref="#/components/schemas/Inspection"))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))),
 *     @OA\Delete(tags={"Inspections"}, operationId="v1InspectionsDelete", summary="Delete inspection", security={{"bearer_token": {}}}, @OA\Response(response=200, description="Inspection deleted", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Deleted"), @OA\Property(property="data", type="object", nullable=true))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")))
 * )
 */
class InspectionEndpoints
{
    // Inspection endpoint annotation carrier.
}
