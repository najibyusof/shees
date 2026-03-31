<?php

namespace App\OpenAPI;

use OpenAPI\Annotations as OA;

/**
 * @OA\Tag(name="Trainings", description="Training management")
 *
 * @OA\PathItem(
 *     path="/api/v1/trainings",
 *     @OA\Get(tags={"Trainings"}, operationId="v1TrainingsIndex", summary="List trainings", security={{"bearer_token": {}}}, @OA\Parameter(name="status", in="query", @OA\Schema(type="string")), @OA\Parameter(name="search", in="query", @OA\Schema(type="string")), @OA\Response(response=200, description="Trainings list", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Success"), @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Training")))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))),
 *     @OA\Post(tags={"Trainings"}, operationId="v1TrainingsStore", summary="Create training", security={{"bearer_token": {}}}, @OA\RequestBody(required=true, @OA\JsonContent(required={"title"}, @OA\Property(property="title", type="string", example="Fire Safety 101"), @OA\Property(property="description", type="string", nullable=true), @OA\Property(property="starts_at", type="string", format="date-time", nullable=true), @OA\Property(property="ends_at", type="string", format="date-time", nullable=true), @OA\Property(property="certificate_validity_days", type="integer", nullable=true), @OA\Property(property="assigned_user_ids", type="array", @OA\Items(type="integer")))), @OA\Response(response=201, description="Training created", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Created"), @OA\Property(property="data", ref="#/components/schemas/Training"))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")))
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/trainings/{training}",
 *     @OA\Parameter(name="training", in="path", required=true, @OA\Schema(type="integer", example=11)),
 *     @OA\Get(tags={"Trainings"}, operationId="v1TrainingsShow", summary="Show training", security={{"bearer_token": {}}}, @OA\Response(response=200, description="Training details", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Success"), @OA\Property(property="data", ref="#/components/schemas/Training"))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))),
 *     @OA\Put(tags={"Trainings"}, operationId="v1TrainingsUpdate", summary="Update training", security={{"bearer_token": {}}}, @OA\RequestBody(required=true, @OA\JsonContent(@OA\Property(property="title", type="string"), @OA\Property(property="description", type="string", nullable=true), @OA\Property(property="starts_at", type="string", format="date-time", nullable=true), @OA\Property(property="ends_at", type="string", format="date-time", nullable=true), @OA\Property(property="assigned_user_ids", type="array", @OA\Items(type="integer")))), @OA\Response(response=200, description="Training updated", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Updated"), @OA\Property(property="data", ref="#/components/schemas/Training"))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))),
 *     @OA\Delete(tags={"Trainings"}, operationId="v1TrainingsDelete", summary="Delete training", security={{"bearer_token": {}}}, @OA\Response(response=200, description="Training deleted", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Deleted"), @OA\Property(property="data", type="object", nullable=true))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")))
 * )
 */
class TrainingEndpoints
{
    // Training endpoint annotation carrier.
}
