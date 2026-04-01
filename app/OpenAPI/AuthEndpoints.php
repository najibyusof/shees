<?php

namespace App\OpenAPI;

use OpenAPI\Annotations as OA;

/**
 * @OA\Tag(name="Auth", description="Authentication endpoints")
 *
 * @OA\PathItem(
 *     path="/api/v1/auth/login",
 *     @OA\Post(
 *         tags={"Auth"},
 *         operationId="v1AuthLogin",
 *         summary="Login",
 *         security={},
 *         @OA\RequestBody(required=true, @OA\JsonContent(
 *             required={"email", "password", "device_name"},
 *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *             @OA\Property(property="password", type="string", format="password", example="Str0ngPass!23"),
 *             @OA\Property(property="device_name", type="string", example="Samsung S24"),
 *             @OA\Property(property="ttl_minutes", type="integer", nullable=true, example=10080)
 *         )),
 *         @OA\Response(response=200, description="Login successful", @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Login successful."),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="token", type="string", example="token_value_here"),
 *                 @OA\Property(property="token_type", type="string", example="Bearer"),
                @OA\Property(property="dashboard_token", type="string", example="1|sanctum_dashboard_token_here"),
 *                 @OA\Property(property="session_id", type="integer", example=12),
 *                 @OA\Property(property="device_name", type="string", example="Samsung S24"),
 *                 @OA\Property(property="expires_at", type="string", format="date-time", nullable=true),
 *                 @OA\Property(property="user", ref="#/components/schemas/User"),
 *                 @OA\Property(property="roles", type="array", @OA\Items(type="string", example="Manager")),
 *                 @OA\Property(property="permissions", type="array", @OA\Items(type="string", example="view_incident"))
 *             )
 *         )),
 *         @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 *
 * @OA\PathItem(path="/api/v1/auth/logout", @OA\Post(tags={"Auth"}, operationId="v1AuthLogout", summary="Logout", security={{"bearer_token": {}}}, @OA\Response(response=200, description="Logged out", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Logged out successfully."), @OA\Property(property="data", type="object", nullable=true))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))))
 * @OA\PathItem(path="/api/v1/user", @OA\Get(tags={"Auth"}, operationId="v1AuthUser", summary="Current user", security={{"bearer_token": {}}}, @OA\Response(response=200, description="Current user", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Success"), @OA\Property(property="data", ref="#/components/schemas/User"))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))))
 */
class AuthEndpoints
{
    // Auth endpoint annotation carrier.
}
