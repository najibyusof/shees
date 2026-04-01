<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\InspectionChecklist;
use App\Models\User;
use App\Services\InspectionExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InspectionMobileController extends Controller
{
    public function __construct(private readonly InspectionExecutionService $executionService) {}

    public function checklists(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', InspectionChecklist::class);

        $query = InspectionChecklist::query()
            ->with('items')
            ->where('is_active', true)
            ->orderBy('id');

        if ($request->filled('since')) {
            $query->where('updated_at', '>=', $request->string('since'));
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        Gate::authorize('create', Inspection::class);

        $validated = $request->validate([
            'inspection_checklist_id' => ['required', 'integer', 'exists:inspection_checklists,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'device_identifier' => ['nullable', 'string', 'max:255'],
            'offline_reference' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:draft,completed,submitted'],
        ]);

        $inspector = $request->user();
        $inspection = $this->executionService->start($validated, $inspector);

        return response()->json(['data' => $inspection], 201);
    }

    public function show(Inspection $inspection): JsonResponse
    {
        $this->authorize('view', $inspection);
        $this->authorizeInspectionOwner($inspection->inspector_id, request()->user());

        return response()->json([
            'data' => $inspection->load(['checklist.items', 'inspector', 'responses.images', 'responses.checklistItem']),
        ]);
    }

    public function updateResponses(Request $request, Inspection $inspection): JsonResponse
    {
        $this->authorize('update', $inspection);
        $this->authorizeInspectionOwner($inspection->inspector_id, $request->user());

        $validated = $request->validate([
            'responses' => ['required', 'array', 'min:1'],
            'responses.*.response_id' => ['required', 'integer', 'exists:inspection_responses,id'],
            'responses.*.response_value' => ['nullable', 'string'],
            'responses.*.comment' => ['nullable', 'string'],
            'responses.*.is_non_compliant' => ['nullable', 'boolean'],
            'mark_as_completed' => ['nullable', 'boolean'],
        ]);

        $mapped = [];
        foreach ($validated['responses'] as $row) {
            $mapped[$row['response_id']] = [
                'response_value' => $row['response_value'] ?? null,
                'comment' => $row['comment'] ?? null,
                'is_non_compliant' => (bool) ($row['is_non_compliant'] ?? false),
            ];
        }

        $updated = $this->executionService->updateResponses(
            $inspection,
            $mapped,
            (bool) ($validated['mark_as_completed'] ?? false)
        );

        return response()->json(['data' => $updated]);
    }

    public function submit(Inspection $inspection): JsonResponse
    {
        $this->authorize('update', $inspection);
        $this->authorizeInspectionOwner($inspection->inspector_id, request()->user());

        return response()->json([
            'data' => $this->executionService->submit($inspection),
        ]);
    }

    private function authorizeInspectionOwner(int $ownerId, ?User $user): void
    {
        if (! $user || $user->id !== $ownerId) {
            abort(403, 'You are not allowed to access this inspection run.');
        }
    }
}
