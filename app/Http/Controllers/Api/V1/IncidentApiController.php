<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Incidents\StoreIncidentApiRequest;
use App\Http\Requests\Api\Incidents\UpdateIncidentApiRequest;
use App\Http\Resources\Api\IncidentResource;
use App\Models\Incident;
use App\Services\IncidentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncidentApiController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly IncidentService $incidentService)
    {
    }

    /**
     * GET /api/v1/incidents
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Incident::withTrashed()
            ->with(['reporter', 'incidentStatus', 'incidentClassification', 'incidentLocation'])
            ->when(
                ! $user->hasAnyRole(['Admin', 'Manager', 'Safety Officer']),
                fn ($q) => $q->where('reported_by', $user->id)
            )
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('classification'), fn ($q) => $q->where('classification', $request->query('classification')))
            ->when($request->filled('search'), fn ($q) => $q->search($request->query('search')))
            ->when($request->filled('from'), fn ($q) => $q->where('datetime', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('datetime', '<=', $request->query('to')));

        $allowed = ['created_at', 'updated_at', 'datetime', 'title', 'classification', 'status'];
        $sort    = in_array($request->get('sort'), $allowed, true) ? $request->get('sort') : 'created_at';
        $dir     = $request->get('direction') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $dir);

        $paginator = $query->paginate($request->integer('per_page', 15));

        return $this->paginated($paginator->through(fn ($i) => new IncidentResource($i)));
    }

    /**
     * POST /api/v1/incidents
     */
    public function store(StoreIncidentApiRequest $request): JsonResponse
    {
        $data     = $request->validated();
        $incident = $this->incidentService->create($data, $data['attachments'] ?? [], $request->user());

        return $this->created(new IncidentResource($incident));
    }

    /**
     * GET /api/v1/incidents/{incident}
     */
    public function show(Incident $incident): JsonResponse
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
            'comments.replies.user',
            'activities.user',
            'approvals.approver',
            'immediateCauses',
            'contributingFactors',
            'workActivities',
            'externalParties',
        ]);

        return $this->success(new IncidentResource($incident));
    }

    /**
     * PUT /api/v1/incidents/{incident}
     */
    public function update(UpdateIncidentApiRequest $request, Incident $incident): JsonResponse
    {
        $user = $request->user();

        $canEdit = $user->hasAnyRole(['Admin', 'Manager', 'Safety Officer'])
            || ($incident->reported_by === $user->id && in_array($incident->status, ['draft', 'rejected'], true));

        if (! $canEdit) {
            return $this->forbidden('You cannot edit this incident in its current state.');
        }

        $incident = $this->incidentService->update(
            $incident,
            $request->validated(),
            $request->validated('attachments', []),
            $user
        );

        return $this->success(new IncidentResource($incident));
    }

    /**
     * DELETE /api/v1/incidents/{incident}
     */
    public function destroy(Request $request, Incident $incident): JsonResponse
    {
        $user = $request->user();

        $canDelete = $user->hasAnyRole(['Admin', 'Manager', 'Safety Officer'])
            || $incident->reported_by === $user->id;

        if (! $canDelete) {
            return $this->forbidden();
        }

        $incident->delete();

        return $this->noContent('Incident deleted.');
    }
}
