<?php

namespace App\OpenAPI;

use OpenAPI\Annotations as OA;

/**
 * @OA\Tag(name="Dashboard", description="Role-based dashboard endpoints")
 *
 * @OA\PathItem(
 *     path="/api/dashboard",
 *     @OA\Get(
 *         tags={"Dashboard"},
 *         operationId="dashboardOverview",
 *         summary="Get role-based dashboard widgets",
 *         description="Returns dashboard widgets based on authenticated user's role and permissions. Requires Sanctum bearer token from login response dashboard_token.",
 *         security={{"bearer_token": {}}},
 *         @OA\Response(
 *             response=200,
 *             description="Dashboard loaded",
 *             @OA\JsonContent(
 *                 @OA\Property(property="success", type="boolean", example=true),
 *                 @OA\Property(property="message", type="string", example="Dashboard loaded successfully."),
 *                 @OA\Property(property="data", ref="#/components/schemas/DashboardResponse")
 *             )
 *         ),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/dashboard/analytics",
 *     @OA\Get(
 *         tags={"Dashboard"},
 *         operationId="dashboardAnalytics",
 *         summary="Get role-based dashboard analytics",
 *         description="Returns analytics data and chart series for incidents, trainings, inspections, audits/NCR, and workers with query-level RBAC filtering.",
 *         security={{"bearer_token": {}}},
 *         @OA\Parameter(name="from", in="query", required=false, @OA\Schema(type="string", format="date")),
 *         @OA\Parameter(name="to", in="query", required=false, @OA\Schema(type="string", format="date")),
 *         @OA\Parameter(name="module", in="query", required=false, @OA\Schema(type="string", enum={"all","incident","training","inspection","audit","worker"})),
 *         @OA\Response(
 *             response=200,
 *             description="Dashboard analytics loaded",
 *             @OA\JsonContent(
 *                 @OA\Property(property="success", type="boolean", example=true),
 *                 @OA\Property(property="message", type="string", example="Dashboard analytics loaded successfully."),
 *                 @OA\Property(property="data", ref="#/components/schemas/AnalyticsResponse")
 *             )
 *         ),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/dashboard/logout",
 *     @OA\Post(
 *         tags={"Dashboard"},
 *         operationId="dashboardLogout",
 *         summary="Revoke dashboard session token",
 *         description="Revokes the currently authenticated Sanctum dashboard token.",
 *         security={{"bearer_token": {}}},
 *         @OA\Response(
 *             response=200,
 *             description="Dashboard session revoked",
 *             @OA\JsonContent(
 *                 @OA\Property(property="success", type="boolean", example=true),
 *                 @OA\Property(property="message", type="string", example="Dashboard session revoked."),
 *                 @OA\Property(property="data", type="object", nullable=true)
 *             )
 *         ),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 */
class DashboardEndpoints
{
    // OpenAPI annotation carrier for dashboard endpoint.
}
