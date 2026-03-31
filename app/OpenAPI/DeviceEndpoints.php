<?php

namespace App\OpenAPI;

use OpenAPI\Annotations as OA;

/**
 * @OA\Tag(name="Devices", description="Mobile device registration")
 *
 * @OA\PathItem(
 *     path="/api/v1/device/register",
 *     @OA\Post(
 *         tags={"Devices"},
 *         operationId="v1DeviceRegister",
 *         summary="Register or update device",
 *         security={{"bearer_token": {}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(
 *             required={"device_id", "device_name"},
 *             @OA\Property(property="device_id", type="string", example="android-8f0a12"),
 *             @OA\Property(property="device_name", type="string", example="Samsung S24"),
 *             @OA\Property(property="platform", type="string", nullable=true, example="android"),
 *             @OA\Property(property="app_version", type="string", nullable=true, example="1.4.0"),
 *             @OA\Property(property="push_token", type="string", nullable=true)
 *         )),
 *         @OA\Response(response=200, description="Device registered", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Device registered."), @OA\Property(property="data", type="object"))),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/device/registrations",
 *     @OA\Get(tags={"Devices"}, operationId="v1DeviceRegistrations", summary="List registered devices", security={{"bearer_token": {}}}, @OA\Response(response=200, description="Device list", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Success"), @OA\Property(property="data", type="array", @OA\Items(type="object")))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")))
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/device/{deviceId}",
 *     @OA\Delete(tags={"Devices"}, operationId="v1DeviceDeregister", summary="Deregister device", security={{"bearer_token": {}}}, @OA\Parameter(name="deviceId", in="path", required=true, @OA\Schema(type="string", example="android-8f0a12")), @OA\Response(response=200, description="Device deregistered", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Device deregistered."), @OA\Property(property="data", type="object", nullable=true))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")))
 * )
 */
class DeviceEndpoints
{
    // Device endpoint annotation carrier.
}
