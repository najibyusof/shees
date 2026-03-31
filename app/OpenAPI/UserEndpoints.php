<?php

namespace App\OpenAPI;

use OpenAPI\Annotations as OA;

/**
 * @OA\Tag(name="Users", description="User management")
 *
 * @OA\PathItem(
 *     path="/api/v1/users",
 *     @OA\Get(
 *         tags={"Users"},
 *         operationId="v1UsersIndex",
 *         summary="List users",
 *         security={{"bearer_token": {}}},
 *         @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
 *         @OA\Parameter(name="role", in="query", @OA\Schema(type="string")),
 *         @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", example=15)),
 *         @OA\Response(response=200, description="Users list", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Success"), @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User")))),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     ),
 *     @OA\Post(
 *         tags={"Users"},
 *         operationId="v1UsersStore",
 *         summary="Create user",
 *         security={{"bearer_token": {}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(
 *             required={"name", "email", "password"},
 *             @OA\Property(property="name", type="string", example="John Doe"),
 *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *             @OA\Property(property="password", type="string", format="password", example="Str0ngPass!23"),
 *             @OA\Property(property="role_ids", type="array", @OA\Items(type="integer", example=1))
 *         )),
 *         @OA\Response(response=201, description="User created", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Created"), @OA\Property(property="data", ref="#/components/schemas/User"))),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/users/{user}",
 *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer", example=7)),
 *     @OA\Get(tags={"Users"}, operationId="v1UsersShow", summary="Show user", security={{"bearer_token": {}}}, @OA\Response(response=200, description="User details", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Success"), @OA\Property(property="data", ref="#/components/schemas/User"))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))),
 *     @OA\Put(tags={"Users"}, operationId="v1UsersUpdate", summary="Update user", security={{"bearer_token": {}}}, @OA\RequestBody(required=true, @OA\JsonContent(@OA\Property(property="name", type="string"), @OA\Property(property="email", type="string", format="email"), @OA\Property(property="password", type="string", format="password", nullable=true), @OA\Property(property="role_ids", type="array", @OA\Items(type="integer")))), @OA\Response(response=200, description="User updated", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Updated"), @OA\Property(property="data", ref="#/components/schemas/User"))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))),
 *     @OA\Delete(tags={"Users"}, operationId="v1UsersDelete", summary="Delete user", security={{"bearer_token": {}}}, @OA\Response(response=200, description="User deleted", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Deleted"), @OA\Property(property="data", type="object", nullable=true))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")))
 * )
 */
class UserEndpoints
{
    // User endpoint annotation carrier.
}
