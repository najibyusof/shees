<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use App\Services\WorkerTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkerTrackingController extends Controller
{
    public function __construct(private readonly WorkerTrackingService $trackingService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Worker::class);

        $workers = Worker::query()
            ->with('user')
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status'));
            })
            ->when($request->filled('department'), function ($query) use ($request) {
                $query->where('department', $request->string('department'));
            })
            ->orderBy('full_name')
            ->paginate((int) ($request->integer('per_page') ?: 15));

        return response()->json(['data' => $workers]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Worker::class);

        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'employee_code' => ['required', 'string', 'max:50', 'unique:workers,employee_code'],
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'department' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:active,inactive,on-leave'],
            'geofence_center_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'geofence_center_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'geofence_radius_meters' => ['nullable', 'integer', 'min:20', 'max:5000'],
        ]);

        $worker = $this->trackingService->createWorker($validated);

        return response()->json(['data' => $worker], 201);
    }

    public function show(Request $request, Worker $worker): JsonResponse
    {
        $this->authorize('view', $worker);

        return response()->json([
            'data' => $worker->load(['user', 'attendanceLogs' => fn ($q) => $q->limit(50)]),
        ]);
    }

    public function update(Request $request, Worker $worker): JsonResponse
    {
        $this->authorize('update', $worker);

        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'employee_code' => ['nullable', 'string', 'max:50', 'unique:workers,employee_code,'.$worker->id],
            'full_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'department' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:active,inactive,on-leave'],
            'geofence_center_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'geofence_center_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'geofence_radius_meters' => ['nullable', 'integer', 'min:20', 'max:5000'],
        ]);

        $updated = $this->trackingService->updateWorker($worker, $validated);

        return response()->json(['data' => $updated]);
    }

    public function logAttendance(Request $request, Worker $worker): JsonResponse
    {
        $this->authorize('logAttendance', $worker);

        $validated = $request->validate([
            'event_type' => ['nullable', 'string', 'in:check_in,check_out,ping,manual_adjustment'],
            'logged_at' => ['nullable', 'date'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_meters' => ['nullable', 'numeric', 'min:0'],
            'speed_mps' => ['nullable', 'numeric', 'min:0'],
            'heading_degrees' => ['nullable', 'numeric', 'between:0,360'],
            'source' => ['nullable', 'string', 'in:simulated,gps,manual,api'],
            'device_identifier' => ['nullable', 'string', 'max:255'],
            'external_event_id' => ['nullable', 'string', 'max:255'],
            'meta' => ['nullable', 'array'],
        ]);

        $log = $this->trackingService->logAttendance($worker, $validated, $request->user());

        return response()->json(['data' => $log], 201);
    }

    public function simulateTracking(Request $request, Worker $worker): JsonResponse
    {
        $this->authorize('logAttendance', $worker);

        $validated = $request->validate([
            'breach_chance' => ['nullable', 'numeric', 'between:0,1'],
            'inside_offset_meters' => ['nullable', 'numeric', 'min:1', 'max:200'],
            'breach_offset_meters' => ['nullable', 'numeric', 'min:10', 'max:2000'],
            'device_identifier' => ['nullable', 'string', 'max:255'],
        ]);

        $log = $this->trackingService->simulatePing($worker, $validated, $request->user());

        return response()->json(['data' => $log], 201);
    }

    public function attendanceFeed(Request $request, Worker $worker): JsonResponse
    {
        $this->authorize('view', $worker);

        $logs = $worker->attendanceLogs()
            ->when($request->filled('from'), function ($query) use ($request) {
                $query->where('logged_at', '>=', $request->string('from'));
            })
            ->when($request->filled('to'), function ($query) use ($request) {
                $query->where('logged_at', '<=', $request->string('to'));
            })
            ->limit((int) ($request->integer('limit') ?: 100))
            ->get();

        return response()->json(['data' => $logs]);
    }
}
