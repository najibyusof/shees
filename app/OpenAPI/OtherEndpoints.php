<?php

namespace App\OpenAPI;

use OpenAPI\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="User",
 *     title="User",
 *     description="User Model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="roles", type="array", @OA\Items(type="string", example="Safety Officer")),
 *     @OA\Property(property="permissions", type="array", @OA\Items(type="string", example="manage_incidents"))
 * )
 */

/**
 * @OA\Schema(
 *     schema="Training",
 *     title="Training",
 *     description="Training Record",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Fire Safety 101"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="duration_hours", type="integer", example=2),
 *     @OA\Property(property="scheduled_date", type="string", format="date", example="2026-04-15"),
 *     @OA\Property(property="status", type="string", enum={"pending", "in_progress", "completed", "cancelled"}),
 *     @OA\Property(property="trainees", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Inspection",
 *     title="Inspection",
 *     description="Safety Inspection Record",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Monthly Site Inspection"),
 *     @OA\Property(property="location", type="string", example="Site A"),
 *     @OA\Property(property="inspection_type", type="string", example="monthly"),
 *     @OA\Property(property="status", type="string", enum={"draft", "in_progress", "completed", "reviewed"}),
 *     @OA\Property(property="checklist_items", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="inspector", ref="#/components/schemas/User"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */

/**
 * @OA\Schema(
 *     schema="AuditLog",
 *     title="Audit Log",
 *     description="System Audit Log Entry",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="action", type="string", example="INCIDENT_CREATED"),
 *     @OA\Property(property="entity_type", type="string", example="Incident"),
 *     @OA\Property(property="entity_id", type="integer", example=5),
 *     @OA\Property(property="user", ref="#/components/schemas/User"),
 *     @OA\Property(property="changes", type="object", description="What changed"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Worker",
 *     title="Worker",
 *     description="Worker/Employee Record",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="worker_id", type="string", example="WRK001"),
 *     @OA\Property(property="name", type="string", example="Jane Smith"),
 *     @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="phone", type="string", example="+1234567890"),
 *     @OA\Property(property="department", type="string", example="Operations"),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive", "on_leave"}),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */

/**
 * @OA\Schema(
 *     schema="AttendanceLog",
 *     title="Attendance Log",
 *     description="Worker Attendance Record",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="worker_id", type="integer", example=1),
 *     @OA\Property(property="check_in_time", type="string", format="date-time"),
 *     @OA\Property(property="check_out_time", type="string", format="date-time"),
 *     @OA\Property(property="location", type="string", example="Main Gate"),
 *     @OA\Property(property="notes", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */

/**
 * @OA\Schema(
 *     schema="NcrReport",
 *     title="NCR Report",
 *     description="Non-Conformance Report",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="ncr_number", type="string", example="NCR-2026-001"),
 *     @OA\Property(property="title", type="string", example="Quality deviation detected"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="severity", type="string", enum={"critical", "major", "minor"}),
 *     @OA\Property(property="status", type="string", enum={"open", "in_progress", "resolved", "closed"}),
 *     @OA\Property(property="assigned_to", ref="#/components/schemas/User"),
 *     @OA\Property(property="created_by", ref="#/components/schemas/User"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */

/**
 * @OA\Tag(
 *     name="Users",
 *     description="User management endpoints"
 * )
 */

/**
 * @OA\Get(
 *     path="/api/users",
 *     operationId="listUsers",
 *     tags={"Users"},
 *     summary="List users",
 *     security={{"bearer_token": {}}},
 *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
 *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Users list", @OA\JsonContent(
 *         @OA\Property(property="success", type="boolean"),
 *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     )),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 */

/**
 * @OA\Tag(
 *     name="Training",
 *     description="Training management endpoints"
 * )
 */

/**
 * @OA\Get(
 *     path="/api/training",
 *     operationId="listTraining",
 *     tags={"Training"},
 *     summary="List training records",
 *     security={{"bearer_token": {}}},
 *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
 *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
 *     @OA\Response(response=200, description="Training list", @OA\JsonContent(
 *         @OA\Property(property="success", type="boolean"),
 *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Training")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     )),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 */

/**
 * @OA\Post(
 *     path="/api/training",
 *     operationId="createTraining",
 *     tags={"Training"},
 *     summary="Create training record",
 *     security={{"bearer_token": {}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"title", "scheduled_date"},
 *         @OA\Property(property="title", type="string"),
 *         @OA\Property(property="description", type="string"),
 *         @OA\Property(property="duration_hours", type="integer"),
 *         @OA\Property(property="scheduled_date", type="string", format="date"),
 *         @OA\Property(property="trainees", type="array", @OA\Items(type="integer"))
 *     )),
 *     @OA\Response(response=201, description="Training created", @OA\JsonContent(
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="data", ref="#/components/schemas/Training")
 *     )),
 *     @OA\Response(response=422, description="Validation error")
 * )
 */

/**
 * @OA\Tag(
 *     name="Inspection",
 *     description="Safety inspection endpoints"
 * )
 */

/**
 * @OA\Get(
 *     path="/api/inspections",
 *     operationId="listInspections",
 *     tags={"Inspection"},
 *     summary="List inspections",
 *     security={{"bearer_token": {}}},
 *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
 *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="type", in="query", @OA\Schema(type="string")),
 *     @OA\Response(response=200, description="Inspections list", @OA\JsonContent(
 *         @OA\Property(property="success", type="boolean"),
 *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Inspection")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     )),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 */

/**
 * @OA\Post(
 *     path="/api/inspections",
 *     operationId="createInspection",
 *     tags={"Inspection"},
 *     summary="Create inspection",
 *     security={{"bearer_token": {}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"title", "location"},
 *         @OA\Property(property="title", type="string"),
 *         @OA\Property(property="location", type="string"),
 *         @OA\Property(property="inspection_type", type="string"),
 *         @OA\Property(property="checklist_items", type="array", @OA\Items(type="object"))
 *     )),
 *     @OA\Response(response=201, description="Inspection created", @OA\JsonContent(
 *         @OA\Property(property="success", type="boolean"),
 *         @OA\Property(property="data", ref="#/components/schemas/Inspection")
 *     )),
 *     @OA\Response(response=422, description="Validation error")
 * )
 */

/**
 * @OA\Tag(
 *     name="Audit & NCR",
 *     description="Audit logs and NCR endpoints"
 * )
 */

/**
 * @OA\Get(
 *     path="/api/audit-logs",
 *     operationId="listAuditLogs",
 *     tags={"Audit & NCR"},
 *     summary="List audit logs",
 *     security={{"bearer_token": {}}},
 *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
 *     @OA\Parameter(name="action", in="query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="entity_type", in="query", @OA\Schema(type="string")),
 *     @OA\Response(response=200, description="Audit logs retrieved", @OA\JsonContent(
 *         @OA\Property(property="success", type="boolean"),
 *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/AuditLog")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     )),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 */

/**
 * @OA\Get(
 *     path="/api/ncr-reports",
 *     operationId="listNcrReports",
 *     tags={"Audit & NCR"},
 *     summary="List NCR reports",
 *     security={{"bearer_token": {}}},
 *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
 *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="severity", in="query", @OA\Schema(type="string")),
 *     @OA\Response(response=200, description="NCR reports retrieved", @OA\JsonContent(
 *         @OA\Property(property="success", type="boolean"),
 *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/NcrReport")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     )),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 */

/**
 * @OA\Post(
 *     path="/api/ncr-reports",
 *     operationId="createNcrReport",
 *     tags={"Audit & NCR"},
 *     summary="Create NCR report",
 *     security={{"bearer_token": {}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"title", "severity"},
 *         @OA\Property(property="title", type="string"),
 *         @OA\Property(property="description", type="string"),
 *         @OA\Property(property="severity", type="string", enum={"critical", "major", "minor"}),
 *         @OA\Property(property="assigned_to", type="integer")
 *     )),
 *     @OA\Response(response=201, description="NCR report created", @OA\JsonContent(
 *         @OA\Property(property="success", type="boolean"),
 *         @OA\Property(property="data", ref="#/components/schemas/NcrReport")
 *     )),
 *     @OA\Response(response=422, description="Validation error")
 * )
 */

/**
 * @OA\Tag(
 *     name="Worker Tracking",
 *     description="Worker and attendance management endpoints"
 * )
 */

/**
 * @OA\Get(
 *     path="/api/workers",
 *     operationId="listWorkers",
 *     tags={"Worker Tracking"},
 *     summary="List workers",
 *     security={{"bearer_token": {}}},
 *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
 *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="department", in="query", @OA\Schema(type="string")),
 *     @OA\Response(response=200, description="Workers list", @OA\JsonContent(
 *         @OA\Property(property="success", type="boolean"),
 *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Worker")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     )),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 */

/**
 * @OA\Post(
 *     path="/api/attendance",
 *     operationId="createAttendance",
 *     tags={"Worker Tracking"},
 *     summary="Record attendance",
 *     security={{"bearer_token": {}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"worker_id", "check_in_time"},
 *         @OA\Property(property="worker_id", type="integer"),
 *         @OA\Property(property="check_in_time", type="string", format="date-time"),
 *         @OA\Property(property="check_out_time", type="string", format="date-time"),
 *         @OA\Property(property="location", type="string"),
 *         @OA\Property(property="notes", type="string")
 *     )),
 *     @OA\Response(response=201, description="Attendance recorded", @OA\JsonContent(
 *         @OA\Property(property="success", type="boolean"),
 *         @OA\Property(property="data", ref="#/components/schemas/AttendanceLog")
 *     )),
 *     @OA\Response(response=422, description="Validation error")
 * )
 */

/**
 * @OA\Get(
 *     path="/api/attendance",
 *     operationId="listAttendance",
 *     tags={"Worker Tracking"},
 *     summary="List attendance logs",
 *     security={{"bearer_token": {}}},
 *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
 *     @OA\Parameter(name="worker_id", in="query", @OA\Schema(type="integer")),
 *     @OA\Parameter(name="from", in="query", @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="to", in="query", @OA\Schema(type="string", format="date")),
 *     @OA\Response(response=200, description="Attendance list", @OA\JsonContent(
 *         @OA\Property(property="success", type="boolean"),
 *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/AttendanceLog")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     )),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 */

class OtherEndpoints
{
    // This class is used for additional endpoint documentation
}
