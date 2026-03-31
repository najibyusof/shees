<?php

namespace App\OpenAPI;

use OpenAPI\Annotations as OA;

/**
 * @OA\Tag(name="Audits", description="Site audit management")
 *
 * @OA\PathItem(
 *     path="/api/v1/audits",
 *     @OA\Get(tags={"Audits"}, operationId="v1AuditsIndex", summary="List audits", security={{"bearer_token": {}}}, @OA\Parameter(name="status", in="query", @OA\Schema(type="string")), @OA\Parameter(name="audit_type", in="query", @OA\Schema(type="string")), @OA\Parameter(name="site_name", in="query", @OA\Schema(type="string")), @OA\Response(response=200, description="Audits list", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Success"), @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Audit")))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))),
 *     @OA\Post(tags={"Audits"}, operationId="v1AuditsStore", summary="Create audit", security={{"bearer_token": {}}}, @OA\RequestBody(required=true, @OA\JsonContent(required={"site_name"}, @OA\Property(property="site_name", type="string", example="Main Construction Yard"), @OA\Property(property="area", type="string", nullable=true), @OA\Property(property="audit_type", type="string", nullable=true, example="internal"), @OA\Property(property="status", type="string", nullable=true, example="scheduled"), @OA\Property(property="scheduled_for", type="string", format="date-time", nullable=true), @OA\Property(property="conducted_at", type="string", format="date-time", nullable=true))), @OA\Response(response=201, description="Audit created", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Created"), @OA\Property(property="data", ref="#/components/schemas/Audit"))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")))
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/audits/{audit}",
 *     @OA\Parameter(name="audit", in="path", required=true, @OA\Schema(type="integer", example=21)),
 *     @OA\Get(tags={"Audits"}, operationId="v1AuditsShow", summary="Show audit", security={{"bearer_token": {}}}, @OA\Response(response=200, description="Audit details", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Success"), @OA\Property(property="data", ref="#/components/schemas/Audit"))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))),
 *     @OA\Put(tags={"Audits"}, operationId="v1AuditsUpdate", summary="Update audit", security={{"bearer_token": {}}}, @OA\RequestBody(required=true, @OA\JsonContent(@OA\Property(property="site_name", type="string"), @OA\Property(property="area", type="string", nullable=true), @OA\Property(property="audit_type", type="string", nullable=true), @OA\Property(property="status", type="string", nullable=true), @OA\Property(property="scheduled_for", type="string", format="date-time", nullable=true), @OA\Property(property="conducted_at", type="string", format="date-time", nullable=true))), @OA\Response(response=200, description="Audit updated", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Updated"), @OA\Property(property="data", ref="#/components/schemas/Audit"))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))),
 *     @OA\Delete(tags={"Audits"}, operationId="v1AuditsDelete", summary="Delete audit", security={{"bearer_token": {}}}, @OA\Response(response=200, description="Audit deleted", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Deleted"), @OA\Property(property="data", type="object", nullable=true))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")))
 * )
 */
class AuditEndpoints
{
    // Audit endpoint annotation carrier.
}
