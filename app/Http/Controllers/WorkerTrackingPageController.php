<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkerTrackingPageController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->hasPermissionTo('workers.view'), 403);

        $workers = Worker::query()
            ->with('user')
            ->withCount([
                'attendanceLogs as attendance_today_count' => fn ($query) => $query->whereDate('logged_at', now()->toDateString()),
                'attendanceLogs as geofence_alerts_count' => fn ($query) => $query->where('inside_geofence', false)->where('logged_at', '>=', now()->subDay()),
            ])
            ->orderBy('full_name')
            ->paginate(15);

        $summary = [
            'total_workers' => Worker::query()->count(),
            'active_workers' => Worker::query()->where('status', 'active')->count(),
            'on_leave_workers' => Worker::query()->where('status', 'on-leave')->count(),
            'alerts_24h' => AttendanceLog::query()->where('inside_geofence', false)->where('logged_at', '>=', now()->subDay())->count(),
        ];

        return view('worker-tracking.index', compact('workers', 'summary'));
    }

    public function show(Request $request, Worker $worker): View
    {
        abort_unless($request->user()?->hasPermissionTo('workers.view'), 403);

        $worker->load('user');

        $attendanceLogs = $worker->attendanceLogs()
            ->paginate(20);

        return view('worker-tracking.show', compact('worker', 'attendanceLogs'));
    }
}
