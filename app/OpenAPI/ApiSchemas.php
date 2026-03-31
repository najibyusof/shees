<?php

namespace App\OpenAPI;

use OpenAPI\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="User",
 *     required={"id", "name", "email"},
 *
 *     @OA\Property(property="id", type="integer", example=7),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="email_verified", type="boolean", example=true),
 *     @OA\Property(property="roles", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Incident",
 *     required={"id", "title", "status", "reported_by"},
 *
 *     @OA\Property(property="id", type="integer", example=128),
 *     @OA\Property(property="title", type="string", example="Forklift collision near loading dock"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="status", type="string", example="draft"),
 *     @OA\Property(property="classification", type="string", nullable=true, example="Major"),
 *     @OA\Property(property="location", type="string", nullable=true),
 *     @OA\Property(property="datetime", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="reported_by", type="integer", example=7),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Training",
 *     required={"id", "title"},
 *
 *     @OA\Property(property="id", type="integer", example=11),
 *     @OA\Property(property="title", type="string", example="Fire Safety 101"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="starts_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="ends_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Inspection",
 *     required={"id", "inspection_checklist_id", "status"},
 *
 *     @OA\Property(property="id", type="integer", example=52),
 *     @OA\Property(property="inspection_checklist_id", type="integer", example=4),
 *     @OA\Property(property="status", type="string", example="in_progress"),
 *     @OA\Property(property="location", type="string", nullable=true),
 *     @OA\Property(property="performed_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="submitted_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Audit",
 *     required={"id", "site_name", "status"},
 *
 *     @OA\Property(property="id", type="integer", example=21),
 *     @OA\Property(property="reference_no", type="string", nullable=true, example="AUD-2026-0021"),
 *     @OA\Property(property="site_name", type="string", example="Main Construction Yard"),
 *     @OA\Property(property="area", type="string", nullable=true),
 *     @OA\Property(property="audit_type", type="string", nullable=true, example="internal"),
 *     @OA\Property(property="status", type="string", example="scheduled"),
 *     @OA\Property(property="scheduled_for", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="conducted_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Worker",
 *     required={"id", "employee_code", "full_name"},
 *
 *     @OA\Property(property="id", type="integer", example=90),
 *     @OA\Property(property="employee_code", type="string", example="WRK-0090"),
 *     @OA\Property(property="full_name", type="string", example="Jane Smith"),
 *     @OA\Property(property="department", type="string", nullable=true),
 *     @OA\Property(property="position", type="string", nullable=true),
 *     @OA\Property(property="status", type="string", nullable=true, example="active"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class ApiSchemas
{
    // Shared schema carrier.
}
