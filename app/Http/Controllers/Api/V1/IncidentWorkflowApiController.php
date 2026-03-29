<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\IncidentComment;
use App\Services\IncidentService;
use App\Services\IncidentWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * API surface for the collaborative incident workflow.
 *
 * POST /api/v1/incidents/{incident}/transition
 * POST /api/v1/incidents/{incident}/comments
 * POST /api/v1/comments/{comment}/reply
 */
class IncidentWorkflowApiController extends Controller
{
    public function __construct(
        private readonly IncidentWorkflowService $workflowService,
        private readonly IncidentService $incidentService,
    ) {}

    // ── Transition ────────────────────────────────────────────────────────

    /**
     * POST /api/v1/incidents/{incident}/transition
     *
     * Body: { "to_status": "draft_submitted", "remarks": "optional notes" }
     */
    public function transition(Request $request, Incident $incident): JsonResponse
    {
        $this->authorize('transition', $incident);

        $validated = $request->validate([
            'to_status' => ['required', 'string', 'in:'.implode(',', Incident::STATUSES)],
            'remarks'   => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $updated = $this->workflowService->transition(
                $incident,
                $request->user(),
                $validated['to_status'],
                $validated['remarks'] ?? null,
            );
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        }

        return response()->json([
            'message'        => 'Incident transitioned successfully.',
            'incident_id'    => $updated->id,
            'status'         => $updated->status,
            'status_label'   => Incident::WORKFLOW_STEPS[$updated->status]['label'] ?? $updated->status,
        ]);
    }

    // ── Comments ──────────────────────────────────────────────────────────

    /**
     * POST /api/v1/incidents/{incident}/comments
     *
     * Body: { "comment": "...", "comment_type": "general|clarification|action_required" }
     */
    public function storeComment(Request $request, Incident $incident): JsonResponse
    {
        $this->authorize('comment', $incident);

        $validated = $request->validate([
            'comment'      => ['required', 'string', 'max:2000'],
            'comment_type' => ['nullable', 'string', 'in:general,clarification,action_required,action,review,investigation'],
            'is_critical'  => ['nullable', 'boolean'],
        ]);

        $comment = $this->incidentService->addComment(
            $incident,
            $request->user(),
            $validated['comment'],
            $validated['comment_type'] ?? 'general',
            array_key_exists('is_critical', $validated) ? (bool) $validated['is_critical'] : null,
        );

        return response()->json([
            'message'    => 'Comment added.',
            'comment_id' => $comment->id,
        ], 201);
    }

    // ── Replies ───────────────────────────────────────────────────────────

    /**
     * POST /api/v1/comments/{comment}/reply
     *
     * Body: { "reply": "..." }
     */
    public function storeReply(Request $request, IncidentComment $comment): JsonResponse
    {
        $incident = $comment->incident;
        $this->authorize('comment', $incident);

        $validated = $request->validate([
            'reply' => ['required', 'string', 'max:2000'],
        ]);

        $reply = $this->incidentService->addCommentReply(
            $incident,
            $comment,
            $request->user(),
            $validated['reply'],
        );

        return response()->json([
            'message'  => 'Reply added.',
            'reply_id' => $reply->id,
        ], 201);
    }

    public function resolveComment(Request $request, IncidentComment $comment): JsonResponse
    {
        $incident = $comment->incident;
        $this->authorize('comment', $incident);

        $validated = $request->validate([
            'resolved' => ['required', 'boolean'],
            'resolution_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $resolvedComment = $this->incidentService->setCommentResolution(
            $incident,
            $comment,
            $request->user(),
            (bool) $validated['resolved'],
            $validated['resolution_note'] ?? null,
        );

        return response()->json([
            'message' => (bool) $validated['resolved'] ? 'Comment resolved.' : 'Comment reopened.',
            'comment_id' => $resolvedComment->id,
            'is_resolved' => (bool) $resolvedComment->is_resolved,
            'resolved_at' => optional($resolvedComment->resolved_at)?->toIso8601String(),
            'resolved_by' => $resolvedComment->resolved_by,
        ]);
    }

    // ── Allowed transitions ───────────────────────────────────────────────

    /**
     * GET /api/v1/incidents/{incident}/allowed-transitions
     *
     * Returns the list of valid next statuses for the authenticated user.
     */
    public function allowedTransitions(Request $request, Incident $incident): JsonResponse
    {
        $this->authorize('view', $incident);

        $transitions = $this->workflowService->allowedTransitionsFor($request->user(), $incident);

        $result = collect($transitions)->map(fn ($status) => [
            'status' => $status,
            'label'  => IncidentWorkflowService::ACTION_LABELS[$status] ?? ucwords(str_replace('_', ' ', $status)),
            'blocked_by_unresolved_critical_comments' => $this->workflowService
                ->isTransitionBlockedByUnresolvedCriticalComments($request->user(), $incident, $status),
        ])->values();

        return response()->json(['transitions' => $result]);
    }
}
