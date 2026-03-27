<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncidentRequest;
use App\Http\Requests\StoreIncidentCommentRequest;
use App\Http\Requests\UpdateIncidentRequest;
use App\Models\Incident;
use App\Models\Role;
use App\Models\User;
use App\Services\IncidentService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class IncidentController extends Controller
{
    public function __construct(private readonly IncidentService $incidentService)
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

        $authorizedIncidents = $incidents->filter(fn (Incident $incident) => $request->user()->can('update', $incident));

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
        $classifications = Incident::CLASSIFICATIONS;

        return view('incidents.create', compact('classifications'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreIncidentRequest $request): RedirectResponse
    {
        $this->incidentService->create(
            $request->validated(),
            $request->file('attachments', []),
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
        $incident->load('attachments');
        $classifications = Incident::CLASSIFICATIONS;

        return view('incidents.edit', compact('incident', 'classifications'));
    }

    public function show(Incident $incident): View
    {
        $incident->load(['reporter', 'attachments', 'activities.user', 'comments.user', 'approvals.approver']);

        $requiredApprovalRoles = Incident::APPROVAL_REQUIRED_ROLES;
        $approvedRoles = $incident->approvals
            ->where('decision', 'approved')
            ->pluck('approver_role')
            ->intersect($requiredApprovalRoles)
            ->unique()
            ->values()
            ->all();
        $missingApprovalRoles = array_values(array_diff($requiredApprovalRoles, $approvedRoles));

        $workflowHistory = collect()
            ->merge($incident->activities->map(function ($activity) {
                return [
                    'type' => 'activity',
                    'timestamp' => $activity->created_at,
                    'title' => $activity->description ?? ucfirst(str_replace('_', ' ', $activity->action)),
                    'actor' => $activity->user?->name ?? 'System',
                    'details' => ($activity->metadata['from'] ?? null) && ($activity->metadata['to'] ?? null)
                        ? 'Status: '.$activity->metadata['from'].' -> '.$activity->metadata['to']
                        : null,
                ];
            }))
            ->merge($incident->comments->map(function ($comment) {
                return [
                    'type' => 'comment',
                    'timestamp' => $comment->created_at,
                    'title' => 'Comment added',
                    'actor' => $comment->user?->name ?? 'Unknown',
                    'details' => $comment->comment,
                ];
            }))
            ->merge($incident->approvals->map(function ($approval) {
                return [
                    'type' => 'approval',
                    'timestamp' => $approval->decided_at,
                    'title' => 'Decision: '.$approval->decision,
                    'actor' => ($approval->approver?->name ?? 'Unknown').' ('.$approval->approver_role.')',
                    'details' => $approval->remarks,
                ];
            }))
            ->sortByDesc('timestamp')
            ->values();

        return view('incidents.show', compact('incident', 'workflowHistory', 'requiredApprovalRoles', 'approvedRoles', 'missingApprovalRoles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateIncidentRequest $request, Incident $incident): RedirectResponse
    {
        $validated = $request->validated();
        $removeAttachmentIds = $validated['remove_attachment_ids'] ?? [];
        unset($validated['remove_attachment_ids']);

        $this->incidentService->update(
            $incident,
            $validated,
            $request->file('attachments', []),
            $request->user(),
            $removeAttachmentIds
        );

        return redirect()->route('incidents.index')->with('toast', [
            'type' => 'success',
            'title' => 'Incident Updated',
            'message' => 'The incident has been updated successfully.',
        ]);
    }

    public function submit(Request $request, Incident $incident): RedirectResponse
    {
        $this->authorize('submit', $incident);

        $this->incidentService->submitForApproval($incident, $request->user());

        return redirect()->route('incidents.show', $incident)->with('toast', [
            'type' => 'success',
            'title' => 'Submitted',
            'message' => 'Incident has been submitted for approval.',
        ]);
    }

    public function approve(Request $request, Incident $incident): RedirectResponse
    {
        $this->authorize('approve', $incident);

        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->incidentService->approve($incident, $request->user(), $validated['remarks'] ?? null);

        return redirect()->route('incidents.show', $incident)->with('toast', [
            'type' => 'success',
            'title' => 'Approved',
            'message' => 'Incident approved successfully.',
        ]);
    }

    public function reject(Request $request, Incident $incident): RedirectResponse
    {
        $this->authorize('reject', $incident);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->incidentService->reject($incident, $request->user(), $validated['reason']);

        return redirect()->route('incidents.show', $incident)->with('toast', [
            'type' => 'warning',
            'title' => 'Rejected',
            'message' => 'Incident rejected.',
        ]);
    }

    public function comment(StoreIncidentCommentRequest $request, Incident $incident): RedirectResponse
    {
        $this->incidentService->addComment($incident, $request->user(), $request->validated('comment'));

        return redirect()->route('incidents.show', $incident)->with('toast', [
            'type' => 'success',
            'title' => 'Comment Added',
            'message' => 'Your comment was added to the workflow history.',
        ]);
    }
}
