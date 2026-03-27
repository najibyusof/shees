<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Trainings\StoreTrainingApiRequest;
use App\Http\Requests\Api\Trainings\UpdateTrainingApiRequest;
use App\Http\Resources\Api\TrainingResource;
use App\Models\Training;
use App\Services\TrainingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainingApiController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly TrainingService $trainingService)
    {
    }

    /**
     * GET /api/v1/trainings
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Training::withTrashed()
            ->when($request->filled('status'), function ($q) use ($request) {
                $active = filter_var($request->query('status'), FILTER_VALIDATE_BOOLEAN);
                $q->where('is_active', $active);
            })
            ->when($request->filled('search'), fn ($q) => $q->search($request->query('search')));

        // Non-admin users only see trainings they are assigned to
        if (! $user->hasAnyRole(['Admin', 'Manager', 'Safety Officer'])) {
            $query->whereHas('users', fn ($q) => $q->where('users.id', $user->id));
        }

        $sort    = in_array($request->get('sort'), ['title', 'starts_at', 'ends_at', 'created_at'], true)
            ? $request->get('sort')
            : 'created_at';
        $dir     = $request->get('direction') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $dir);

        $paginator = $query->paginate($request->integer('per_page', 15));

        return $this->paginated($paginator->through(fn ($t) => new TrainingResource($t)));
    }

    /**
     * POST /api/v1/trainings
     */
    public function store(StoreTrainingApiRequest $request): JsonResponse
    {
        $training = $this->trainingService->create($request->validated(), $request->user());

        return $this->created(new TrainingResource($training));
    }

    /**
     * GET /api/v1/trainings/{training}
     */
    public function show(Training $training): JsonResponse
    {
        $training->load(['users', 'certificates']);

        return $this->success(new TrainingResource($training));
    }

    /**
     * PUT /api/v1/trainings/{training}
     */
    public function update(UpdateTrainingApiRequest $request, Training $training): JsonResponse
    {
        $training = $this->trainingService->update($training, $request->validated());

        return $this->success(new TrainingResource($training));
    }

    /**
     * DELETE /api/v1/trainings/{training}
     */
    public function destroy(Request $request, Training $training): JsonResponse
    {
        if (! $request->user()->hasAnyRole(['Admin', 'Manager'])) {
            return $this->forbidden();
        }

        $training->delete();

        return $this->noContent('Training deleted.');
    }
}
