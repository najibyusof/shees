<?php

namespace App\OpenAPI;

use OpenAPI\Annotations as OA;

/**
 * @OA\Tag(name="Incidents", description="Incident reporting and management")
 *
 * @OA\PathItem(
 *     path="/api/v1/incidents",
 *     @OA\Get(
 *         tags={"Incidents"},
 *         operationId="v1IncidentsIndex",
 *         summary="List incidents",
 *         security={{"bearer_token": {}}},
 *         @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
 *         @OA\Parameter(name="classification", in="query", @OA\Schema(type="string")),
 *         @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
 *         @OA\Parameter(name="from", in="query", description="Filter incidents with datetime greater than or equal to this value", @OA\Schema(type="string", format="date-time")),
 *         @OA\Parameter(name="to", in="query", description="Filter incidents with datetime less than or equal to this value", @OA\Schema(type="string", format="date-time")),
 *         @OA\Parameter(name="sort", in="query", @OA\Schema(type="string", enum={"created_at", "updated_at", "datetime", "title", "classification", "status"})),
 *         @OA\Parameter(name="direction", in="query", @OA\Schema(type="string", enum={"asc", "desc"})),
 *         @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", example=15)),
 *         @OA\Response(response=200, description="Incidents list", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Success"), @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Incident")), @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"))),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     ),
 *     @OA\Post(
 *         tags={"Incidents"},
 *         operationId="v1IncidentsStore",
 *         summary="Create incident",
 *         security={{"bearer_token": {}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/IncidentStoreRequest")),
 *         @OA\Response(response=201, description="Incident created", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Created"), @OA\Property(property="data", ref="#/components/schemas/Incident"))),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/incidents/{incident}",
 *     @OA\Parameter(name="incident", in="path", required=true, @OA\Schema(type="integer", example=128)),
 *     @OA\Get(tags={"Incidents"}, operationId="v1IncidentsShow", summary="Show incident", security={{"bearer_token": {}}}, @OA\Response(response=200, description="Incident details", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Success"), @OA\Property(property="data", ref="#/components/schemas/Incident"))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))),
 *     @OA\Put(tags={"Incidents"}, operationId="v1IncidentsUpdate", summary="Update incident", security={{"bearer_token": {}}}, @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/IncidentUpdateRequest")), @OA\Response(response=200, description="Incident updated", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Success"), @OA\Property(property="data", ref="#/components/schemas/Incident"))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))),
 *     @OA\Delete(tags={"Incidents"}, operationId="v1IncidentsDelete", summary="Delete incident", security={{"bearer_token": {}}}, @OA\Response(response=200, description="Incident deleted", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Incident deleted."))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")))
 * )
 */
class IncidentEndpoints
{
    // Incident endpoint annotation carrier.
}
