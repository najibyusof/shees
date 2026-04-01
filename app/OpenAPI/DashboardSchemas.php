<?php

namespace App\OpenAPI;

use OpenAPI\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="DashboardResponse",
 *     required={"role", "widgets", "analytics"},
 *     @OA\Property(property="role", type="string", example="HOD HSSE"),
 *     @OA\Property(
 *         property="roles",
 *         type="array",
 *         @OA\Items(type="string", example="HOD HSSE")
 *     ),
 *     @OA\Property(
 *         property="widgets",
 *         type="object",
 *         additionalProperties=@OA\Schema(type="integer"),
 *         example={"pending_draft_review": 3, "closure_requests": 2}
 *     ),
 *     @OA\Property(
 *         property="analytics",
 *         ref="#/components/schemas/AnalyticsResponse"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ChartSeries",
 *     type="object",
 *     @OA\Property(property="labels", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="data", type="array", @OA\Items(type="number", format="float"))
 * )
 *
 * @OA\Schema(
 *     schema="AnalyticsResponse",
 *     type="object",
 *     @OA\Property(
 *         property="incident",
 *         type="object",
 *         @OA\Property(property="total_incidents", type="integer", example=42),
 *         @OA\Property(property="by_status", ref="#/components/schemas/ChartSeries"),
 *         @OA\Property(property="over_time", ref="#/components/schemas/ChartSeries"),
 *         @OA\Property(property="by_classification", ref="#/components/schemas/ChartSeries")
 *     ),
 *     @OA\Property(
 *         property="training",
 *         type="object",
 *         @OA\Property(property="total_trainings", type="integer", example=15),
 *         @OA\Property(property="completion_rate", type="number", format="float", example=87.5),
 *         @OA\Property(property="expiring_certificates", type="integer", example=3),
 *         @OA\Property(property="by_status", ref="#/components/schemas/ChartSeries")
 *     ),
 *     @OA\Property(
 *         property="inspection",
 *         type="object",
 *         @OA\Property(property="total_inspections", type="integer", example=21),
 *         @OA\Property(property="passed_vs_failed", ref="#/components/schemas/ChartSeries"),
 *         @OA\Property(property="trends", ref="#/components/schemas/ChartSeries")
 *     ),
 *     @OA\Property(
 *         property="audit",
 *         type="object",
 *         @OA\Property(property="total_audits", type="integer", example=10),
 *         @OA\Property(property="ncr_by_severity", ref="#/components/schemas/ChartSeries"),
 *         @OA\Property(property="open_vs_closed_ncr", ref="#/components/schemas/ChartSeries")
 *     ),
 *     @OA\Property(
 *         property="worker",
 *         type="object",
 *         @OA\Property(property="total_workers", type="integer", example=84),
 *         @OA\Property(property="attendance_trends", ref="#/components/schemas/ChartSeries"),
 *         @OA\Property(property="active_vs_inactive", ref="#/components/schemas/ChartSeries")
 *     )
 * )
 */
class DashboardSchemas
{
    // OpenAPI annotation carrier for dashboard schema.
}
