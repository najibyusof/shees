<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\InspectionResource;
use App\Models\Inspection;
use App\Services\InspectionChecklistService;
use App\Services\InspectionExecutionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InspectionApiController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly InspectionChecklistService $checklistService,
        private readonly InspectionExecutionService $executionService,
    ) {
        $this->authorizeResource(Inspection::class, 'inspection');
        $this->middleware('permission:view_audit')->only(['index', 'show']);
        $this->middleware('permission:create_audit')->only(['store']);
        $this->middleware('permission:edit_audit')->only(['update']);
        $this->middleware('permission:approve_audit')->only(['destroy']);
    }

    /**
     * GET /api/v1/inspections
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Inspection::with(['inspector', 'checklist'])
            ->when(
                ! $user->hasPermissionTo('edit_audit')
                    && ! $user->hasPermissionTo('approve_audit')
                    && ! $user->hasPermissionTo('create_audit'),
                fn ($q) => $q->where('inspector_id', $user->id)
            )
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('checklist_id'), fn ($q) => $q->where('inspection_checklist_id', $request->query('checklist_id')));

        $sort = in_array($request->get('sort'), ['created_at', 'updated_at', 'performed_at', 'submitted_at'], true)
            ? $request->get('sort')
            : 'created_at';
        $dir  = $request->get('direction') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $dir);

        $paginator = $query->paginate($request->integer('per_page', 15));

        return $this->paginated($paginator->through(fn ($i) => new InspectionResource($i)));
    }

    /**
     * POST /api/v1/inspections
     * Start a new inspection run.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inspection_checklist_id' => ['required', 'integer', 'exists:inspection_checklists,id'],
            'location'                => ['nullable', 'string', 'max:255'],
            'performed_at'            => ['nullable', 'date'],
            'notes'                   => ['nullable', 'string'],
            'offline_uuid'            => ['nullable', 'uuid'],
            'device_identifier'       => ['nullable', 'string', 'max:255'],
        ]);

        $inspection = $this->executionService->start($validated, $request->user());

        return $this->created(new InspectionResource($inspection->load(['inspector', 'checklist'])));
    }

    /**
     * GET /api/v1/inspections/{inspection}
     */
    public function show(Inspection $inspection): JsonResponse
    {
        $this->authorize('view', $inspection);

        $inspection->load(['inspector', 'checklist.items', 'responses.checklistItem', 'responses.images']);

        return $this->success(new InspectionResource($inspection));
    }

    /**
     * PUT /api/v1/inspections/{inspection}
     * Update responses for an in-progress inspection.
     */
    public function update(Request $request, Inspection $inspection): JsonResponse
    {
        $this->authorize('update', $inspection);

        $validated = $request->validate([
            'responses'                         => ['required', 'array'],
            'responses.*.checklist_item_id'     => ['required', 'integer', 'exists:inspection_checklist_items,id'],
            'responses.*.response_value'        => ['nullable', 'string'],
            'responses.*.is_non_compliant'      => ['nullable', 'boolean'],
            'responses.*.comment'               => ['nullable', 'string'],
        ]);

        $inspection = $this->executionService->updateResponses($inspection, $validated['responses']);

        return $this->success(new InspectionResource($inspection->load(['checklist', 'responses'])));
    }

    /**
     * DELETE /api/v1/inspections/{inspection}
     */
    public function destroy(Request $request, Inspection $inspection): JsonResponse
    {
        $this->authorize('delete', $inspection);

        $inspection->delete();

        return $this->noContent('Inspection deleted.');
    }
}
