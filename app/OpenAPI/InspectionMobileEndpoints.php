<?php

namespace App\OpenAPI;

use OpenAPI\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Inspection Mobile",
 *     description="Inspection-focused mobile authentication, run execution, and sync endpoints"
 * )
 *
 * @OA\Schema(
 *     schema="InspectionMobileSession",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=33),
 *     @OA\Property(property="device_name", type="string", example="iPad Pro - Safety Team"),
 *     @OA\Property(property="last_used_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="expires_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="is_current", type="boolean", example=true)
 * )
 *
 * @OA\Schema(
 *     schema="InspectionSyncJob",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=901),
 *     @OA\Property(property="inspection_id", type="integer", nullable=true, example=52),
 *     @OA\Property(property="entity_type", type="string", example="inspection_response"),
 *     @OA\Property(property="entity_offline_uuid", type="string", nullable=true, example="a4f2f2f0-ff6c-4f8c-9a2f-8b6f3b1d7a1e"),
 *     @OA\Property(property="operation", type="string", nullable=true, example="upsert"),
 *     @OA\Property(property="contract_name", type="string", example="inspection-mobile-sync"),
 *     @OA\Property(property="contract_version", type="integer", example=1),
 *     @OA\Property(property="status", type="string", example="pending"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="InspectionSyncConflict",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=44),
 *     @OA\Property(property="inspection_sync_job_id", type="integer", example=901),
 *     @OA\Property(property="strategy", type="string", nullable=true, example="manual_review"),
 *     @OA\Property(property="status", type="string", example="open"),
 *     @OA\Property(property="notes", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/inspection/auth/login",
 *
 *     @OA\Post(
 *         tags={"Inspection Mobile"},
 *         operationId="inspectionMobileAuthLogin",
 *         summary="Login for inspection mobile app",
 *         security={},
 *
 *         @OA\RequestBody(
 *             required=true,
 *
 *             @OA\JsonContent(
 *                 required={"email", "password", "device_name"},
 *
 *                 @OA\Property(property="email", type="string", format="email", example="inspector@example.com"),
 *                 @OA\Property(property="password", type="string", format="password", example="Str0ngPass!23"),
 *                 @OA\Property(property="device_name", type="string", example="iPad Pro - Safety Team"),
 *                 @OA\Property(property="ttl_minutes", type="integer", nullable=true, example=10080)
 *             )
 *         ),
 *
 *         @OA\Response(
 *             response=201,
 *             description="Token issued",
 *
 *             @OA\JsonContent(
 *                 @OA\Property(property="success", type="boolean", example=true),
 *                 @OA\Property(property="message", type="string", example="Token issued."),
 *                 @OA\Property(property="data", type="object",
 *                     @OA\Property(property="token", type="string", example="1|abcxyz"),
 *                     @OA\Property(property="token_type", type="string", example="Bearer"),
 *                     @OA\Property(property="session_id", type="integer", example=33),
 *                     @OA\Property(property="device_name", type="string", example="iPad Pro - Safety Team"),
 *                     @OA\Property(property="expires_at", type="string", format="date-time", nullable=true),
 *                     @OA\Property(property="user", type="object",
 *                         @OA\Property(property="id", type="integer", example=7),
 *                         @OA\Property(property="name", type="string", example="Inspector Jane"),
 *                         @OA\Property(property="email", type="string", format="email", example="inspector@example.com")
 *                     )
 *                 )
 *             )
 *         ),
 *         @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/inspection/auth/logout",
 *
 *     @OA\Post(
 *         tags={"Inspection Mobile"},
 *         operationId="inspectionMobileAuthLogout",
 *         summary="Logout current inspection mobile session",
 *         security={{"bearer_token": {}}},
 *
 *         @OA\Response(response=200, description="Logged out", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Logged out successfully."), @OA\Property(property="data", type="object", nullable=true))),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/inspection/auth/sessions",
 *
 *     @OA\Get(
 *         tags={"Inspection Mobile"},
 *         operationId="inspectionMobileAuthSessions",
 *         summary="List active mobile sessions",
 *         security={{"bearer_token": {}}},
 *
 *         @OA\Response(
 *             response=200,
 *             description="Sessions list",
 *
 *             @OA\JsonContent(
 *                 @OA\Property(property="success", type="boolean", example=true),
 *                 @OA\Property(property="message", type="string", example="Success"),
 *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/InspectionMobileSession"))
 *             )
 *         ),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/inspection/auth/sessions/{mobileAccessToken}",
 *
 *     @OA\Patch(
 *         tags={"Inspection Mobile"},
 *         operationId="inspectionMobileRenameSession",
 *         summary="Rename mobile session",
 *         security={{"bearer_token": {}}},
 *
 *         @OA\Parameter(name="mobileAccessToken", in="path", required=true, @OA\Schema(type="integer", example=33)),
 *
 *         @OA\RequestBody(required=true, @OA\JsonContent(required={"device_name"}, @OA\Property(property="device_name", type="string", example="iPad Pro - Shift B"))),
 *
 *         @OA\Response(response=200, description="Session renamed", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Session renamed."), @OA\Property(property="data", ref="#/components/schemas/InspectionMobileSession"))),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/inspection/auth/sessions/{mobileAccessToken}/revoke",
 *
 *     @OA\Post(
 *         tags={"Inspection Mobile"},
 *         operationId="inspectionMobileRevokeSession",
 *         summary="Revoke specific mobile session",
 *         security={{"bearer_token": {}}},
 *
 *         @OA\Parameter(name="mobileAccessToken", in="path", required=true, @OA\Schema(type="integer", example=33)),
 *
 *         @OA\Response(response=200, description="Session revoked", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Session revoked."), @OA\Property(property="data", type="object", nullable=true))),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/inspection/auth/rotate",
 *
 *     @OA\Post(
 *         tags={"Inspection Mobile"},
 *         operationId="inspectionMobileRotateToken",
 *         summary="Rotate inspection mobile token",
 *         security={{"bearer_token": {}}},
 *
 *         @OA\RequestBody(required=false, @OA\JsonContent(@OA\Property(property="device_name", type="string", nullable=true, example="iPad Pro - Safety Team"))),
 *
 *         @OA\Response(response=201, description="Token rotated", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Token rotated."), @OA\Property(property="data", type="object"))),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/inspection/checklists",
 *
 *     @OA\Get(
 *         tags={"Inspection Mobile"},
 *         operationId="inspectionMobileChecklists",
 *         summary="Get active inspection checklists",
 *         security={{"bearer_token": {}}},
 *
 *         @OA\Parameter(name="since", in="query", required=false, description="Return checklists updated since timestamp", @OA\Schema(type="string", format="date-time")),
 *
 *         @OA\Response(response=200, description="Checklist list", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Success"), @OA\Property(property="data", type="array", @OA\Items(type="object")))),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/inspection/runs",
 *
 *     @OA\Post(
 *         tags={"Inspection Mobile"},
 *         operationId="inspectionMobileStartRun",
 *         summary="Start inspection run",
 *         security={{"bearer_token": {}}},
 *
 *         @OA\RequestBody(
 *             required=true,
 *
 *             @OA\JsonContent(
 *                 required={"inspection_checklist_id"},
 *
 *                 @OA\Property(property="inspection_checklist_id", type="integer", example=4),
 *                 @OA\Property(property="location", type="string", nullable=true, example="Plant B"),
 *                 @OA\Property(property="notes", type="string", nullable=true),
 *                 @OA\Property(property="device_identifier", type="string", nullable=true),
 *                 @OA\Property(property="offline_reference", type="string", nullable=true),
 *                 @OA\Property(property="status", type="string", nullable=true, enum={"draft", "completed", "submitted"})
 *             )
 *         ),
 *
 *         @OA\Response(response=201, description="Inspection run created", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Inspection run created."), @OA\Property(property="data", type="object"))),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/inspection/runs/{inspection}",
 *
 *     @OA\Get(
 *         tags={"Inspection Mobile"},
 *         operationId="inspectionMobileShowRun",
 *         summary="Get inspection run details",
 *         security={{"bearer_token": {}}},
 *
 *         @OA\Parameter(name="inspection", in="path", required=true, @OA\Schema(type="integer", example=52)),
 *
 *         @OA\Response(response=200, description="Inspection run", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Success"), @OA\Property(property="data", type="object"))),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/inspection/runs/{inspection}/responses",
 *
 *     @OA\Put(
 *         tags={"Inspection Mobile"},
 *         operationId="inspectionMobileUpdateResponses",
 *         summary="Update inspection responses",
 *         security={{"bearer_token": {}}},
 *
 *         @OA\Parameter(name="inspection", in="path", required=true, @OA\Schema(type="integer", example=52)),
 *
 *         @OA\RequestBody(
 *             required=true,
 *
 *             @OA\JsonContent(
 *                 required={"responses"},
 *
 *                 @OA\Property(
 *                     property="responses",
 *                     type="array",
 *
 *                     @OA\Items(
 *                         type="object",
 *                         required={"response_id"},
 *
 *                         @OA\Property(property="response_id", type="integer", example=771),
 *                         @OA\Property(property="response_value", type="string", nullable=true, example="Compliant"),
 *                         @OA\Property(property="comment", type="string", nullable=true, example="PPE worn correctly"),
 *                         @OA\Property(property="is_non_compliant", type="boolean", nullable=true, example=false)
 *                     )
 *                 ),
 *                 @OA\Property(property="mark_as_completed", type="boolean", nullable=true, example=true)
 *             )
 *         ),
 *
 *         @OA\Response(response=200, description="Responses updated", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Responses updated."), @OA\Property(property="data", type="object"))),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/inspection/runs/{inspection}/submit",
 *
 *     @OA\Post(
 *         tags={"Inspection Mobile"},
 *         operationId="inspectionMobileSubmitRun",
 *         summary="Submit inspection run",
 *         security={{"bearer_token": {}}},
 *
 *         @OA\Parameter(name="inspection", in="path", required=true, @OA\Schema(type="integer", example=52)),
 *
 *         @OA\Response(response=200, description="Run submitted", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Run submitted."), @OA\Property(property="data", type="object"))),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/inspection/sync/contract",
 *
 *     @OA\Get(
 *         tags={"Inspection Mobile"},
 *         operationId="inspectionSyncContract",
 *         summary="Get sync contract",
 *         security={{"bearer_token": {}}},
 *
 *         @OA\Response(
 *             response=200,
 *             description="Sync contract",
 *
 *             @OA\JsonContent(
 *                 @OA\Property(property="success", type="boolean", example=true),
 *                 @OA\Property(property="message", type="string", example="Success"),
 *                 @OA\Property(property="data", type="object",
 *                     @OA\Property(property="name", type="string", example="inspection-mobile-sync"),
 *                     @OA\Property(property="version", type="integer", example=1),
 *                     @OA\Property(property="capabilities", type="object"),
 *                     @OA\Property(property="server_time", type="string", format="date-time")
 *                 )
 *             )
 *         ),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/inspection/sync/upload",
 *
 *     @OA\Post(
 *         tags={"Inspection Mobile"},
 *         operationId="inspectionSyncUpload",
 *         summary="Upload inspection sync payload",
 *         security={{"bearer_token": {}}},
 *
 *         @OA\RequestBody(
 *             required=true,
 *
 *             @OA\JsonContent(
 *                 required={"entity_type", "contract_name", "contract_version", "payload"},
 *
 *                 @OA\Property(property="inspection_id", type="integer", nullable=true, example=52),
 *                 @OA\Property(property="device_identifier", type="string", nullable=true, example="android-inspection-01"),
 *                 @OA\Property(property="idempotency_key", type="string", nullable=true, example="f4b5e8d3-95c2-4f92-8f3a-95a8c0f1a2c1"),
 *                 @OA\Property(property="entity_type", type="string", example="inspection_response"),
 *                 @OA\Property(property="entity_offline_uuid", type="string", nullable=true, example="a4f2f2f0-ff6c-4f8c-9a2f-8b6f3b1d7a1e"),
 *                 @OA\Property(property="operation", type="string", nullable=true, example="upsert"),
 *                 @OA\Property(property="contract_name", type="string", example="inspection-mobile-sync"),
 *                 @OA\Property(property="contract_version", type="integer", example=1),
 *                 @OA\Property(property="payload", type="object"),
 *                 @OA\Property(property="sync_batch_uuid", type="string", nullable=true, example="batch-20260331-1")
 *             )
 *         ),
 *
 *         @OA\Response(
 *             response=201,
 *             description="Sync job enqueued",
 *
 *             @OA\JsonContent(
 *                 @OA\Property(property="success", type="boolean", example=true),
 *                 @OA\Property(property="message", type="string", example="Sync job enqueued."),
 *                 @OA\Property(property="contract", type="object"),
 *                 @OA\Property(property="meta", type="object", @OA\Property(property="idempotency_replay", type="boolean", example=false)),
 *                 @OA\Property(property="data", ref="#/components/schemas/InspectionSyncJob")
 *             )
 *         ),
 *
 *         @OA\Response(
 *             response=200,
 *             description="Idempotency replay",
 *
 *             @OA\JsonContent(
 *                 @OA\Property(property="success", type="boolean", example=true),
 *                 @OA\Property(property="message", type="string", example="Idempotency replay."),
 *                 @OA\Property(property="contract", type="object"),
 *                 @OA\Property(property="meta", type="object", @OA\Property(property="idempotency_replay", type="boolean", example=true)),
 *                 @OA\Property(property="data", ref="#/components/schemas/InspectionSyncJob")
 *             )
 *         ),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/inspection/sync/metrics",
 *
 *     @OA\Get(
 *         tags={"Inspection Mobile"},
 *         operationId="inspectionSyncMetrics",
 *         summary="Get sync metrics",
 *         security={{"bearer_token": {}}},
 *
 *         @OA\Parameter(name="from", in="query", required=false, @OA\Schema(type="string", format="date-time")),
 *         @OA\Parameter(name="to", in="query", required=false, @OA\Schema(type="string", format="date-time")),
 *
 *         @OA\Response(response=200, description="Metrics", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Success"), @OA\Property(property="data", type="object"))),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/inspection/sync/pending",
 *
 *     @OA\Get(
 *         tags={"Inspection Mobile"},
 *         operationId="inspectionSyncPending",
 *         summary="Get pending sync jobs",
 *         security={{"bearer_token": {}}},
 *
 *         @OA\Parameter(name="device_identifier", in="query", required=false, @OA\Schema(type="string")),
 *         @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer", minimum=1, maximum=200, example=50)),
 *
 *         @OA\Response(
 *             response=200,
 *             description="Pending jobs",
 *
 *             @OA\JsonContent(
 *                 @OA\Property(property="success", type="boolean", example=true),
 *                 @OA\Property(property="message", type="string", example="Success"),
 *                 @OA\Property(property="contract", type="object"),
 *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/InspectionSyncJob"))
 *             )
 *         ),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/inspection/sync/jobs/{inspectionSyncJob}/ack",
 *
 *     @OA\Post(
 *         tags={"Inspection Mobile"},
 *         operationId="inspectionSyncAcknowledge",
 *         summary="Acknowledge sync job",
 *         security={{"bearer_token": {}}},
 *
 *         @OA\Parameter(name="inspectionSyncJob", in="path", required=true, @OA\Schema(type="integer", example=901)),
 *
 *         @OA\Response(response=200, description="Acknowledged", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Acknowledged."), @OA\Property(property="data", ref="#/components/schemas/InspectionSyncJob"))),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/inspection/sync/jobs/{inspectionSyncJob}/conflict",
 *
 *     @OA\Post(
 *         tags={"Inspection Mobile"},
 *         operationId="inspectionSyncMarkConflict",
 *         summary="Mark sync job conflict",
 *         security={{"bearer_token": {}}},
 *
 *         @OA\Parameter(name="inspectionSyncJob", in="path", required=true, @OA\Schema(type="integer", example=901)),
 *
 *         @OA\RequestBody(
 *             required=true,
 *
 *             @OA\JsonContent(
 *                 required={"server_payload"},
 *
 *                 @OA\Property(property="server_payload", type="object"),
 *                 @OA\Property(property="notes", type="string", nullable=true, example="Server and client differ on response value")
 *             )
 *         ),
 *
 *         @OA\Response(response=201, description="Conflict created", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Conflict created."), @OA\Property(property="data", ref="#/components/schemas/InspectionSyncConflict"))),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/inspection/sync/conflicts/{inspectionSyncConflict}/resolve",
 *
 *     @OA\Post(
 *         tags={"Inspection Mobile"},
 *         operationId="inspectionSyncResolveConflict",
 *         summary="Resolve sync conflict",
 *         security={{"bearer_token": {}}},
 *
 *         @OA\Parameter(name="inspectionSyncConflict", in="path", required=true, @OA\Schema(type="integer", example=44)),
 *
 *         @OA\RequestBody(
 *             required=true,
 *
 *             @OA\JsonContent(
 *                 required={"strategy"},
 *
 *                 @OA\Property(property="strategy", type="string", enum={"server_wins", "client_wins", "merge", "ignore"}, example="merge"),
 *                 @OA\Property(property="notes", type="string", nullable=true, example="Merged manually after review")
 *             )
 *         ),
 *
 *         @OA\Response(response=200, description="Conflict resolved", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Conflict resolved."), @OA\Property(property="data", ref="#/components/schemas/InspectionSyncConflict"))),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 */
class InspectionMobileEndpoints
{
    // Annotation carrier for inspection mobile API endpoints.
}
