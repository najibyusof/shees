<?php

namespace App\OpenAPI;

use OpenAPI\Annotations as OA;

/**
 * @OA\Tag(name="NCR", description="Non-conformance report management")
 *
 * @OA\PathItem(
 *     path="/api/v1/ncr",
 *     @OA\Get(tags={"NCR"}, operationId="v1NcrIndex", summary="List NCR reports", security={{"bearer_token": {}}}, @OA\Parameter(name="site_audit_id", in="query", @OA\Schema(type="integer")), @OA\Parameter(name="status", in="query", @OA\Schema(type="string")), @OA\Parameter(name="severity", in="query", @OA\Schema(type="string")), @OA\Response(response=200, description="NCR list", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Success"), @OA\Property(property="data", type="array", @OA\Items(type="object")))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))),
 *     @OA\Post(tags={"NCR"}, operationId="v1NcrStore", summary="Create NCR report", security={{"bearer_token": {}}}, @OA\RequestBody(required=true, @OA\JsonContent(required={"site_audit_id", "title", "severity"}, @OA\Property(property="site_audit_id", type="integer", example=21), @OA\Property(property="title", type="string", example="PPE non-compliance in welding area"), @OA\Property(property="severity", type="string", example="high"), @OA\Property(property="root_cause", type="string", nullable=true), @OA\Property(property="containment_action", type="string", nullable=true), @OA\Property(property="corrective_action_plan", type="string", nullable=true), @OA\Property(property="due_date", type="string", format="date", nullable=true))), @OA\Response(response=201, description="NCR created", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Created"), @OA\Property(property="data", type="object"))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")))
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/ncr/{ncrReport}",
 *     @OA\Parameter(name="ncrReport", in="path", required=true, @OA\Schema(type="integer", example=5)),
 *     @OA\Get(tags={"NCR"}, operationId="v1NcrShow", summary="Show NCR report", security={{"bearer_token": {}}}, @OA\Response(response=200, description="NCR details", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Success"), @OA\Property(property="data", type="object"))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))),
 *     @OA\Put(tags={"NCR"}, operationId="v1NcrUpdate", summary="Update NCR report", security={{"bearer_token": {}}}, @OA\RequestBody(required=true, @OA\JsonContent(@OA\Property(property="title", type="string"), @OA\Property(property="severity", type="string"), @OA\Property(property="status", type="string", nullable=true), @OA\Property(property="root_cause", type="string", nullable=true), @OA\Property(property="containment_action", type="string", nullable=true), @OA\Property(property="corrective_action_plan", type="string", nullable=true), @OA\Property(property="due_date", type="string", format="date", nullable=true))), @OA\Response(response=200, description="NCR updated", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Updated"), @OA\Property(property="data", type="object"))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))),
 *     @OA\Delete(tags={"NCR"}, operationId="v1NcrDelete", summary="Delete NCR report", security={{"bearer_token": {}}}, @OA\Response(response=200, description="NCR deleted", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Deleted"), @OA\Property(property="data", type="object", nullable=true))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")))
 * )
 */
class NcrEndpoints
{
    // NCR endpoint annotation carrier.
}
