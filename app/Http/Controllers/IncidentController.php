<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncidentRequest;
use App\Http\Requests\StoreIncidentCommentRequest;
use App\Http\Requests\StoreIncidentCommentReplyRequest;
use App\Http\Requests\UpdateIncidentRequest;
use App\Models\Incident;
use App\Models\IncidentComment;
use App\Models\Role;
use App\Models\User;
use App\Services\IncidentFormOptionsService;
use App\Services\IncidentService;
use App\Services\IncidentWorkflowService;
use App\Support\IncidentRules;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class IncidentController extends Controller
{
    public function __construct(
        private readonly IncidentService $incidentService,
        private readonly IncidentWorkflowService $workflowService,
        private readonly IncidentFormOptionsService $incidentFormOptionsService,
    )
    {
        $this->authorizeResource(Incident::class, 'incident');
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'in:'.implode(',', Incident::STATUSES)],
            'classification' => ['nullable', 'string', 'in:'.implode(',', Incident::CLASSIFICATIONS)],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort' => ['nullable', 'string', 'in:title,status,classification,datetime,created_at'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50'],
        ]);

        $sort = (string) ($filters['sort'] ?? 'datetime');
        $direction = (string) ($filters['direction'] ?? 'desc');
        $perPage = (int) ($filters['per_page'] ?? 12);

        $incidents = Incident::query()
            ->select([
                'id',
                'title',
                'status',
                'classification',
                'location',
                'datetime',
                'reported_by',
                'created_at',
            ])
            ->with(['reporter:id,name,email'])
            ->withCount('attachments')
            ->search($filters['search'] ?? null)
            ->status($filters['status'] ?? null)
            ->classification($filters['classification'] ?? null)
            ->reporter(isset($filters['assigned_to']) ? (int) $filters['assigned_to'] : null)
            ->reporterRoles($filters['role_ids'] ?? [])
            ->dateBetween($filters['date_from'] ?? null, $filters['date_to'] ?? null)
            ->sortByField($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        $roles = Role::query()->orderBy('name')->get(['id', 'name']);
        $assignees = User::query()->orderBy('name')->get(['id', 'name']);

        return view('incidents.index', [
            'incidents' => $incidents,
            'roles' => $roles,
            'assignees' => $assignees,
            'filters' => [
                'search' => (string) ($filters['search'] ?? ''),
                'status' => (string) ($filters['status'] ?? ''),
                'classification' => (string) ($filters['classification'] ?? ''),
                'assigned_to' => (string) ($filters['assigned_to'] ?? ''),
                'role_ids' => array_map('intval', $filters['role_ids'] ?? []),
                'date_from' => (string) ($filters['date_from'] ?? ''),
                'date_to' => (string) ($filters['date_to'] ?? ''),
            ],
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Incident::class);

        $validated = $request->validate([
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['integer', 'exists:incidents,id'],
            'action' => ['required', 'string', 'in:delete,update_status'],
            'status' => ['nullable', 'string', 'in:'.implode(',', Incident::STATUSES)],
        ]);

        $selectedIds = array_values(array_unique(array_map('intval', $validated['selected'])));
        $incidents = Incident::query()->whereIn('id', $selectedIds)->get();

        if ($incidents->isEmpty()) {
            return redirect()->route('incidents.index')->with('toast', [
                'type' => 'warning',
                'title' => 'No Incidents Selected',
                'message' => 'No valid incidents were found for this bulk action.',
            ]);
        }

        $targetAbility = $validated['action'] === 'delete' ? 'delete' : 'update';

        $authorizedIncidents = $incidents->filter(fn (Incident $incident) => $request->user()->can($targetAbility, $incident));

        if ($authorizedIncidents->isEmpty()) {
            return redirect()->route('incidents.index')->with('toast', [
                'type' => 'warning',
                'title' => 'Action Not Allowed',
                'message' => 'You are not authorized to perform this bulk action.',
            ]);
        }

        $affected = 0;

        if ($validated['action'] === 'delete') {
            $affected = Incident::query()
                ->whereIn('id', $authorizedIncidents->pluck('id')->all())
                ->delete();

            return redirect()->route('incidents.index')->with('toast', [
                'type' => 'success',
                'title' => 'Bulk Delete Complete',
                'message' => "Deleted {$affected} incident(s).",
            ]);
        }

        $status = (string) ($validated['status'] ?? '');
        if ($status === '') {
            return redirect()->route('incidents.index')->withErrors([
                'status' => 'Please choose a status for bulk update.',
            ]);
        }

        $affected = Incident::query()
            ->whereIn('id', $authorizedIncidents->pluck('id')->all())
            ->update(['status' => $status]);

        return redirect()->route('incidents.index')->with('toast', [
            'type' => 'success',
            'title' => 'Bulk Update Complete',
            'message' => "Updated {$affected} incident(s) to {$status}.",
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('incidents.create', $this->incidentFormOptionsService->forForm());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreIncidentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->incidentService->create(
            $validated,
            $this->normalizeAttachments($request, $validated),
            $request->user()
        );

        return redirect()->route('incidents.index')->with('toast', [
            'type' => 'success',
            'title' => 'Incident Created',
            'message' => 'The incident has been created successfully.',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Incident $incident): View
    {
        $incident->load([
            'incidentType',
            'incidentStatus',
            'incidentClassification',
            'reclassification',
            'incidentLocation.locationType',
            'workPackage',
            'subcontractor',
            'rootCause',
            'attachments.attachmentType',
            'attachments.attachmentCategory',
            'chronologies',
            'victims.victimType',
            'witnesses',
            'investigationTeamMembers',
            'damages.damageType',
            'immediateActions',
            'plannedActions',
            'immediateCauses',
            'contributingFactors',
            'workActivities',
            'externalParties',
        ]);

        return view('incidents.edit', array_merge(
            ['incident' => $incident],
            $this->incidentFormOptionsService->forForm()
        ));
    }

    public function show(Incident $incident): View
    {
        $incident->load([
            'reporter',
            'incidentType',
            'incidentStatus',
            'incidentClassification',
            'reclassification',
            'incidentLocation.locationType',
            'workPackage',
            'subcontractor',
            'rootCause',
            'attachments.attachmentType',
            'attachments.attachmentCategory',
            'chronologies',
            'victims.victimType',
            'witnesses',
            'investigationTeamMembers',
            'damages.damageType',
            'immediateActions',
            'plannedActions',
            'comments.user',
            'comments.resolver',
            'comments.replies.user',
            'activities.user',
            'workflowLogs.performer',
            'immediateCauses',
            'contributingFactors',
            'workActivities',
            'externalParties',
        ]);

        $allowedTransitions = $this->workflowService->allowedTransitionsFor(
            request()->user(),
            $incident,
        );

        $blockedTransitionReasons = collect($allowedTransitions)
            ->mapWithKeys(function (string $toStatus) use ($incident) {
                $blocked = $this->workflowService->isTransitionBlockedByUnresolvedCriticalComments(
                    request()->user(),
                    $incident,
                    $toStatus,
                );

                return [$toStatus => $blocked
                    ? 'Resolve critical comments before progressing this stage.'
                    : null];
            })
            ->all();

        $workflowHistory = collect()
            ->merge($incident->activities->map(function ($activity) {
                return [
                    'type'      => 'activity',
                    'timestamp' => $activity->created_at,
                    'title'     => $activity->description ?? ucfirst(str_replace('_', ' ', $activity->action)),
                    'actor'     => $activity->user?->name ?? 'System',
                    'details'   => ($activity->metadata['from'] ?? null) && ($activity->metadata['to'] ?? null)
                        ? 'Status: '.$activity->metadata['from'].' → '.$activity->metadata['to']
                        : null,
                ];
            }))
            ->merge($incident->comments->map(function ($comment) {
                return [
                    'type'      => 'comment',
                    'timestamp' => $comment->created_at,
                    'title'     => 'Comment added',
                    'actor'     => $comment->user?->name ?? 'Unknown',
                    'details'   => $comment->comment,
                ];
            }))
            ->sortByDesc('timestamp')
            ->values();

        return view('incidents.show', compact(
            'incident',
            'workflowHistory',
            'allowedTransitions',
            'blockedTransitionReasons',
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateIncidentRequest $request, Incident $incident): RedirectResponse
    {
        $validated = $request->validated();
        $removeAttachmentIds = $validated['remove_attachment_ids'] ?? [];

        $this->incidentService->update(
            $incident,
            $validated,
            $this->normalizeAttachments($request, $validated),
            $request->user(),
            $removeAttachmentIds
        );

        return redirect()->route('incidents.index')->with('toast', [
            'type' => 'success',
            'title' => 'Incident Updated',
            'message' => 'The incident has been updated successfully.',
        ]);
    }

    /**
     * Advance the incident through the collaborative workflow.
     * The exact transition is validated inside IncidentWorkflowService.
     */
    public function transition(Request $request, Incident $incident): RedirectResponse
    {
        $this->authorize('transition', $incident);

        $validated = $request->validate([
            'to_status' => ['required', 'string', 'in:'.implode(',', Incident::STATUSES)],
            'remarks'   => ['nullable', 'string', 'max:2000'],
        ]);

        $this->workflowService->transition(
            $incident,
            $request->user(),
            $validated['to_status'],
            $validated['remarks'] ?? null,
        );

        $label = ucwords(str_replace('_', ' ', $validated['to_status']));

        return redirect()->route('incidents.show', $incident)->with('toast', [
            'type'    => 'success',
            'title'   => 'Workflow Updated',
            'message' => "Incident moved to {$label}.",
        ]);
    }

    public function comment(StoreIncidentCommentRequest $request, Incident $incident): RedirectResponse
    {
        $this->incidentService->addComment(
            $incident,
            $request->user(),
            $request->validated('comment'),
            (string) ($request->validated('comment_type') ?? 'general'),
            $request->filled('is_critical') ? (bool) $request->boolean('is_critical') : null,
        );

        return redirect()->route('incidents.show', $incident)->with('toast', [
            'type' => 'success',
            'title' => 'Comment Added',
            'message' => 'Your comment was added to the workflow history.',
        ]);
    }

    public function reply(StoreIncidentCommentReplyRequest $request, Incident $incident, IncidentComment $comment): RedirectResponse
    {
        abort_unless($comment->incident_id === $incident->id, 404);

        $this->incidentService->addCommentReply($incident, $comment, $request->user(), $request->validated('reply'));

        return redirect()->route('incidents.show', $incident)->with('toast', [
            'type' => 'success',
            'title' => 'Reply Added',
            'message' => 'Your reply was added to the discussion.',
        ]);
    }

    public function resolveComment(Request $request, Incident $incident, IncidentComment $comment): RedirectResponse
    {
        abort_unless($comment->incident_id === $incident->id, 404);
        $this->authorize('comment', $incident);

        $validated = $request->validate([
            'resolved' => ['required', 'boolean'],
            'resolution_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $resolved = (bool) $validated['resolved'];

        $this->incidentService->setCommentResolution(
            $incident,
            $comment,
            $request->user(),
            $resolved,
            $validated['resolution_note'] ?? null,
        );

        return redirect()->route('incidents.show', $incident)->with('toast', [
            'type' => 'success',
            'title' => $resolved ? 'Comment Resolved' : 'Comment Reopened',
            'message' => $resolved
                ? 'The comment has been marked as resolved.'
                : 'The comment has been marked as unresolved.',
        ]);
    }

    public function autosave(Request $request, ?Incident $incident = null): JsonResponse
    {
        if ($incident === null) {
            $this->authorize('create', Incident::class);
        } else {
            $this->authorize('update', $incident);
        }

        $validated = $request->validate(IncidentRules::partialPayload());

        if ($incident === null) {
            $incident = $this->incidentService->create(
                $validated,
                [],
                $request->user()
            );
        } else {
            $this->incidentService->update(
                $incident,
                $validated,
                [],
                $request->user(),
                []
            );
            $incident->refresh();
        }

        return response()->json([
            'id'           => $incident->id,
            'temporary_id' => $incident->temporary_id,
            'savedAt'      => $incident->updated_at?->toISOString() ?? now()->toISOString(),
        ]);
    }

    private function normalizeAttachments(Request $request, array $validated): array
    {
        return collect($validated['attachments'] ?? [])
            ->map(function (array $attachment, int $index) use ($request) {
                $file = $request->file("attachments.{$index}.file");

                if ($file) {
                    $attachment['file'] = $file;
                }

                return $attachment;
            })
            ->all();
    }
}
