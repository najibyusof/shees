<?php

namespace App\OpenAPI;

use OpenAPI\Annotations as OA;

/**
 * @OA\Tag(name="Incident Workflow", description="Incident transitions and comments")
 *
 * @OA\PathItem(
 *     path="/api/v1/incidents/{incident}/transition",
 *     @OA\Post(
 *         tags={"Incident Workflow"},
 *         operationId="v1IncidentTransition",
 *         summary="Transition incident status",
 *         security={{"bearer_token": {}}},
 *         @OA\Parameter(name="incident", in="path", required=true, @OA\Schema(type="integer", example=128)),
 *         @OA\RequestBody(required=true, @OA\JsonContent(required={"to_status"}, @OA\Property(property="to_status", type="string", example="in_review"), @OA\Property(property="remarks", type="string", nullable=true))),
 *         @OA\Response(response=200, description="Transition applied", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Incident transitioned."), @OA\Property(property="data", type="object"))),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/incidents/{incident}/comments",
 *     @OA\Post(
 *         tags={"Incident Workflow"},
 *         operationId="v1IncidentStoreComment",
 *         summary="Create incident comment",
 *         security={{"bearer_token": {}}},
 *         @OA\Parameter(name="incident", in="path", required=true, @OA\Schema(type="integer", example=128)),
 *         @OA\RequestBody(required=true, @OA\JsonContent(required={"comment"}, @OA\Property(property="comment", type="string", example="Please add witness statement."), @OA\Property(property="comment_type", type="string", nullable=true, example="clarification"), @OA\Property(property="is_critical", type="boolean", nullable=true))),
 *         @OA\Response(response=201, description="Comment created", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Comment added."), @OA\Property(property="data", type="object"))),
 *         @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *         @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 *     )
 * )
 *
 * @OA\PathItem(path="/api/v1/incidents/{incident}/allowed-transitions", @OA\Get(tags={"Incident Workflow"}, operationId="v1IncidentAllowedTransitions", summary="Get allowed transitions", security={{"bearer_token": {}}}, @OA\Parameter(name="incident", in="path", required=true, @OA\Schema(type="integer", example=128)), @OA\Response(response=200, description="Allowed transitions", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Success"), @OA\Property(property="data", type="array", @OA\Items(type="object")))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))))
 * @OA\PathItem(path="/api/v1/comments/{comment}/reply", @OA\Post(tags={"Incident Workflow"}, operationId="v1IncidentCommentReply", summary="Reply to comment", security={{"bearer_token": {}}}, @OA\Parameter(name="comment", in="path", required=true, @OA\Schema(type="integer", example=77)), @OA\RequestBody(required=true, @OA\JsonContent(required={"reply"}, @OA\Property(property="reply", type="string", example="Attached witness statement."))), @OA\Response(response=201, description="Reply created", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Reply added."), @OA\Property(property="data", type="object"))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))))
 * @OA\PathItem(path="/api/v1/comments/{comment}/resolve", @OA\Patch(tags={"Incident Workflow"}, operationId="v1IncidentResolveComment", summary="Resolve or reopen comment", security={{"bearer_token": {}}}, @OA\Parameter(name="comment", in="path", required=true, @OA\Schema(type="integer", example=77)), @OA\RequestBody(required=true, @OA\JsonContent(required={"resolved"}, @OA\Property(property="resolved", type="boolean", example=true), @OA\Property(property="resolution_note", type="string", nullable=true))), @OA\Response(response=200, description="Comment status updated", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Comment updated."), @OA\Property(property="data", type="object"))), @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=404, description="Resource not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))))
 */
class IncidentWorkflowEndpoints
{
    // Incident workflow annotation carrier.
}
