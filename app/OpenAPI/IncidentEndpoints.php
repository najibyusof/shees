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
 *         @OA\Response(response=200, description="Incidents list", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Success"), @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Incident")))),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     ),
 *     @OA\Post(
 *         tags={"Incidents"},
 *         operationId="v1IncidentsStore",
 *         summary="Create incident",
 *         security={{"bearer_token": {}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(
 *             required={"title", "incident_type_id", "incident_date", "incident_time", "work_package_id", "location_type_id", "classification_id", "incident_description"},
 *             @OA\Property(property="title", type="string", example="Forklift collision near loading dock"),
 *             @OA\Property(property="incident_type_id", type="integer", example=2),
 *             @OA\Property(property="incident_date", type="string", format="date", example="2026-01-15"),
 *             @OA\Property(property="incident_time", type="string", example="13:45"),
 *             @OA\Property(property="work_package_id", type="integer", example=3),
 *             @OA\Property(property="location_type_id", type="integer", example=1),
 *             @OA\Property(property="classification_id", type="integer", example=2),
 *             @OA\Property(property="incident_description", type="string"),
 *             @OA\Property(property="temporary_id", type="string", format="uuid", nullable=true),
 *             @OA\Property(property="local_created_at", type="string", format="date-time", nullable=true)
 *         )),
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
 *     @OA\Put(tags={"Incidents"}, operationId="v1IncidentsUpdate", summary="Update incident", security={{"bearer_token": {}}}, @OA\RequestBody(required=true, @OA\JsonContent(@OA\Property(property="title", type="string"), @OA\Property(property="incident_description", type="string", nullable=true), @OA\Property(property="classification_id", type="integer", nullable=true), @OA\Property(property="status", type="string", nullable=true))), @OA\Response(response=200, description="Incident updated", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Updated"), @OA\Property(property="data", ref="#/components/schemas/Incident"))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))),
 *     @OA\Delete(tags={"Incidents"}, operationId="v1IncidentsDelete", summary="Delete incident", security={{"bearer_token": {}}}, @OA\Response(response=200, description="Incident deleted", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Deleted"), @OA\Property(property="data", type="object", nullable=true))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")))
 * )
 */
class IncidentEndpoints
{
    // Incident endpoint annotation carrier.
}
