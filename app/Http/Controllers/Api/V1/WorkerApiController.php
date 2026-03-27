<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Workers\LogAttendanceApiRequest;
use App\Http\Requests\Api\Workers\StoreWorkerApiRequest;
use App\Http\Requests\Api\Workers\UpdateWorkerApiRequest;
use App\Http\Resources\Api\AttendanceLogResource;
use App\Http\Resources\Api\WorkerResource;
use App\Models\Worker;
use App\Services\WorkerTrackingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkerApiController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly WorkerTrackingService $workerTrackingService)
    {
    }

    /**
     * GET /api/v1/workers
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasAnyRole(['Admin', 'Manager', 'Safety Officer'])) {
            return $this->forbidden();
        }

        $query = Worker::withTrashed()
            ->with('user')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('department'), fn ($q) => $q->where('department', $request->query('department')))
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q2) use ($request) {
                $q2->where('full_name', 'like', '%'.$request->query('search').'%')
                    ->orWhere('employee_code', 'like', '%'.$request->query('search').'%');
            }));

        $sort = in_array($request->get('sort'), ['full_name', 'employee_code', 'created_at', 'last_seen_at'], true)
            ? $request->get('sort')
            : 'full_name';
        $dir  = $request->get('direction') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sort, $dir);

        $paginator = $query->paginate($request->integer('per_page', 15));

        return $this->paginated($paginator->through(fn ($w) => new WorkerResource($w)));
    }

    /**
     * POST /api/v1/workers
     */
    public function store(StoreWorkerApiRequest $request): JsonResponse
    {
        $worker = $this->workerTrackingService->createWorker($request->validated());

        return $this->created(new WorkerResource($worker->load('user')));
    }

    /**
     * GET /api/v1/workers/{worker}
     */
    public function show(Worker $worker): JsonResponse
    {
        $worker->load(['user', 'attendanceLogs' => fn ($q) => $q->latest('logged_at')->limit(20)]);

        return $this->success(new WorkerResource($worker));
    }

    /**
     * PUT /api/v1/workers/{worker}
     */
    public function update(UpdateWorkerApiRequest $request, Worker $worker): JsonResponse
    {
        $worker = $this->workerTrackingService->updateWorker($worker, $request->validated());

        return $this->success(new WorkerResource($worker->load('user')));
    }

    /**
     * DELETE /api/v1/workers/{worker}
     */
    public function destroy(Request $request, Worker $worker): JsonResponse
    {
        if (! $request->user()->hasAnyRole(['Admin', 'Manager'])) {
            return $this->forbidden();
        }

        $worker->delete();

        return $this->noContent('Worker deleted.');
    }

    /**
     * POST /api/v1/workers/{worker}/attendance
     *
     * Log an attendance event for a worker.
     */
    public function logAttendance(LogAttendanceApiRequest $request, Worker $worker): JsonResponse
    {
        $data = $request->validated();

        $log = $this->workerTrackingService->logAttendance($worker, $data, $request->user());

        // Persist offline sync fields if provided
        if (! empty($data['temporary_id']) || ! empty($data['local_created_at'])) {
            $log->update(array_filter([
                'temporary_id'     => $data['temporary_id'] ?? null,
                'local_created_at' => $data['local_created_at'] ?? null,
            ]));
        }

        return $this->created(new AttendanceLogResource($log));
    }
}
