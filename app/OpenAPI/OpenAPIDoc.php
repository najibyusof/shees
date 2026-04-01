<?php

namespace App\OpenAPI;

use OpenAPI\Annotations as OA;

/**
 * @OA\OpenApi(
 *     info=@OA\Info(
 *         version="1.0",
 *         title="SHEES API",
 *         description="Safety, Health, Environment & Emergency System - RESTful API for incident management, training, inspections, and worker tracking.",
 *         x={
 *             "logo": {
 *                 "url": "https://via.placeholder.com/190x90?text=SHEES+API"
 *             }
 *         },
 *         contact=@OA\Contact(
 *             email="api@shees.local"
 *         )
 *     ),
 *     servers={
 *         @OA\Server(
 *             url=L5_SWAGGER_CONST_HOST,
 *             description="API Server",
 *         ),
 *     },
 *     paths={},
 *     components=@OA\Components(
 *         responses={
 *             "Unauthorized"=@OA\Response(
 *                 response="Unauthorized",
 *                 description="Unauthorized",
 *                 @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *             ),
 *             "ValidationError"=@OA\Response(
 *                 response="ValidationError",
 *                 description="Validation error",
 *                 @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *             ),
 *             "NotFound"=@OA\Response(
 *                 response="NotFound",
 *                 description="Resource not found",
 *                 @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *             )
 *         },
 *         securitySchemes={
 *             "bearer_token"=@OA\SecurityScheme(
 *                 securityScheme="bearer_token",
 *                 type="http",
 *                 description="Bearer token auth. Use data.dashboard_token (Sanctum) for /api/dashboard endpoints, and data.token for mobile token middleware endpoints.",
 *                 name="bearer_token",
 *                 in="header",
 *                 scheme="bearer",
 *                 bearerFormat="Sanctum"
 *             ),
 *         },
 *         schemas={
 *             "ApiResponse"=@OA\Schema(
 *                 schema="ApiResponse",
 *                 title="API Response",
 *                 description="Standard API Response",
 *                 required={"success", "message", "data"},
 *                 @OA\Property(
 *                     property="success",
 *                     type="boolean",
 *                     description="Operation success status",
 *                     example=true
 *                 ),
 *                 @OA\Property(
 *                     property="message",
 *                     type="string",
 *                     description="Response message",
 *                     example="Operation successful"
 *                 ),
 *                 @OA\Property(
 *                     property="data",
 *                     type="object",
 *                     description="Response data"
 *                 ),
 *                 @OA\Property(
 *                     property="meta",
 *                     type="object",
 *                     description="Pagination and metadata"
 *                 )
 *             ),
 *             "ErrorResponse"=@OA\Schema(
 *                 schema="ErrorResponse",
 *                 title="Error Response",
 *                 description="Standard Error Response",
 *                 required={"success", "message"},
 *                 @OA\Property(
 *                     property="success",
 *                     type="boolean",
 *                     description="Operation success status",
 *                     example=false
 *                 ),
 *                 @OA\Property(
 *                     property="message",
 *                     type="string",
 *                     description="Error message",
 *                     example="An error occurred"
 *                 ),
 *                 @OA\Property(
 *                     property="errors",
 *                     type="object",
 *                     description="Validation errors",
 *                     example={}
 *                 )
 *             ),
 *             "PaginationMeta"=@OA\Schema(
 *                 schema="PaginationMeta",
 *                 title="Pagination Metadata",
 *                 type="object",
 *                 @OA\Property(
 *                     property="total",
 *                     type="integer",
 *                     example=100
 *                 ),
 *                 @OA\Property(
 *                     property="per_page",
 *                     type="integer",
 *                     example=15
 *                 ),
 *                 @OA\Property(
 *                     property="current_page",
 *                     type="integer",
 *                     example=1
 *                 ),
 *                 @OA\Property(
 *                     property="last_page",
 *                     type="integer",
 *                     example=7
 *                 )
 *             )
 *         }
 *     ),
 *     security={
 *         {"bearer_token": {}}
 *     }
 * )
 */
class OpenAPIDoc
{
    // This class is used for OpenAPI documentation generation only
}
