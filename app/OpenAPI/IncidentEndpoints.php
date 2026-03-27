<?php

namespace App\OpenAPI;

use OpenAPI\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Incident",
 *     title="Incident",
 *     description="Incident Model",
 *     required={"id", "title", "status", "reported_by"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         example=1,
 *         description="Incident ID"
 *     ),
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         example="Safety incident occurred",
 *         description="Incident title"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         example="Detailed description of the incident",
 *         description="Full incident description"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"reported", "under_investigation", "resolved", "closed"},
 *         example="under_investigation",
 *         description="Current status of the incident"
 *     ),
 *     @OA\Property(
 *         property="classification",
 *         type="string",
 *         enum={"high_risk", "medium_risk", "low_risk"},
 *         example="high_risk",
 *         description="Risk classification"
 *     ),
 *     @OA\Property(
 *         property="location",
 *         type="string",
 *         example="Office Building A",
 *         description="Incident location"
 *     ),
 *     @OA\Property(
 *         property="reported_by",
 *         type="integer",
 *         example=1,
 *         description="User ID who reported the incident"
 *     ),
 *     @OA\Property(
 *         property="datetime",
 *         type="string",
 *         format="date-time",
 *         example="2026-03-28T14:30:00Z",
 *         description="Date and time of incident"
 *     ),
 *     @OA\Property(
 *         property="temporary_id",
 *         type="string",
 *         example="temp_12345",
 *         description="Temporary ID for offline sync"
 *     ),
 *     @OA\Property(
 *         property="local_created_at",
 *         type="string",
 *         format="date-time",
 *         example="2026-03-28T14:20:00Z",
 *         description="Local creation timestamp (offline)"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         example="2026-03-28T14:30:00Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         example="2026-03-28T15:00:00Z"
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="IncidentCreate",
 *     title="Create Incident",
 *     description="Request body for creating an incident",
 *     required={"title", "status", "datetime"},
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         example="Safety incident occurred",
 *         description="Incident title"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         example="Detailed description of the incident",
 *         description="Full incident description"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"reported", "under_investigation", "resolved", "closed"},
 *         example="reported"
 *     ),
 *     @OA\Property(
 *         property="classification",
 *         type="string",
 *         enum={"high_risk", "medium_risk", "low_risk"},
 *         example="high_risk"
 *     ),
 *     @OA\Property(
 *         property="location",
 *         type="string",
 *         example="Office Building A"
 *     ),
 *     @OA\Property(
 *         property="datetime",
 *         type="string",
 *         format="date-time",
 *         example="2026-03-28T14:30:00Z"
 *     ),
 *     @OA\Property(
 *         property="temporary_id",
 *         type="string",
 *         example="temp_12345",
 *         description="Temporary ID for offline sync"
 *     ),
 *     @OA\Property(
 *         property="local_created_at",
 *         type="string",
 *         format="date-time",
 *         example="2026-03-28T14:20:00Z",
 *         description="Local creation timestamp (offline)"
 *     )
 * )
 */

/**
 * @OA\Tag(
 *     name="Incident Management",
 *     description="API endpoints for managing incidents"
 * )
 */

/**
 * @OA\Get(
 *     path="/api/incidents",
 *     operationId="listIncidents",
 *     tags={"Incident Management"},
 *     summary="List all incidents",
 *     description="Retrieve a paginated list of incidents. Non-admin users see only their own incidents.",
 *     security={{"bearer_token": {}}},
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Page number for pagination",
 *         required=false,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         description="Number of records per page",
 *         required=false,
 *         @OA\Schema(type="integer", example=15)
 *     ),
 *     @OA\Parameter(
 *         name="status",
 *         in="query",
 *         description="Filter by incident status",
 *         required=false,
 *         @OA\Schema(type="string", enum={"reported", "under_investigation", "resolved", "closed"})
 *     ),
 *     @OA\Parameter(
 *         name="classification",
 *         in="query",
 *         description="Filter by risk classification",
 *         required=false,
 *         @OA\Schema(type="string", enum={"high_risk", "medium_risk", "low_risk"})
 *     ),
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Search in title and description",
 *         required=false,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="from",
 *         in="query",
 *         description="Filter incidents from date (ISO 8601)",
 *         required=false,
 *         @OA\Schema(type="string", format="date-time")
 *     ),
 *     @OA\Parameter(
 *         name="to",
 *         in="query",
 *         description="Filter incidents until date (ISO 8601)",
 *         required=false,
 *         @OA\Schema(type="string", format="date-time")
 *     ),
 *     @OA\Parameter(
 *         name="sort",
 *         in="query",
 *         description="Sort by field",
 *         required=false,
 *         @OA\Schema(type="string", enum={"created_at", "updated_at", "datetime", "title", "classification", "status"})
 *     ),
 *     @OA\Parameter(
 *         name="direction",
 *         in="query",
 *         description="Sort direction (asc/desc)",
 *         required=false,
 *         @OA\Schema(type="string", enum={"asc", "desc"})
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Incidents list retrieved",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Incidents retrieved"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/Incident")
 *             ),
 *             @OA\Property(
 *                 property="meta",
 *                 ref="#/components/schemas/PaginationMeta"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/api/incidents",
 *     operationId="createIncident",
 *     tags={"Incident Management"},
 *     summary="Create a new incident",
 *     description="Create a new safety incident. Supports offline sync with temporary_id.",
 *     security={{"bearer_token": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/IncidentCreate")
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Incident created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Incident created"),
 *             @OA\Property(property="data", ref="#/components/schemas/Incident")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/api/incidents/{id}",
 *     operationId="getIncident",
 *     tags={"Incident Management"},
 *     summary="Get incident details",
 *     description="Retrieve detailed information about a specific incident including attachments and comments",
 *     security={{"bearer_token": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="Incident ID",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Incident details retrieved",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", ref="#/components/schemas/Incident")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Incident not found",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */

/**
 * @OA\Put(
 *     path="/api/incidents/{id}",
 *     operationId="updateIncident",
 *     tags={"Incident Management"},
 *     summary="Update incident",
 *     description="Update an existing incident. Only the reporter or admins can update.",
 *     security={{"bearer_token": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="Incident ID",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/IncidentCreate")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Incident updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Incident updated"),
 *             @OA\Property(property="data", ref="#/components/schemas/Incident")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Forbidden - Cannot update",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Incident not found",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */

/**
 * @OA\Delete(
 *     path="/api/incidents/{id}",
 *     operationId="deleteIncident",
 *     tags={"Incident Management"},
 *     summary="Delete incident",
 *     description="Soft delete an incident. Only admins can delete incidents.",
 *     security={{"bearer_token": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="Incident ID",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Incident deleted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Incident deleted")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Forbidden - Admin only",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Incident not found",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */
class IncidentEndpoints
{
    // This class is used for incident endpoint documentation only
}
