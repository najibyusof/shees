<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\InspectionSyncConflict;
use App\Models\InspectionSyncJob;
use App\Models\MobileAccessToken;
use App\Models\AttendanceLog;
use App\Models\NcrReport;
use App\Models\CorrectiveAction;
use App\Models\SiteAudit;
use App\Models\Incident;
use App\Models\Training;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $now = now();
        $workerWindowOptions = [
            '15m' => ['label' => '15 Min', 'minutes' => 15],
            '30m' => ['label' => '30 Min', 'minutes' => 30],
            '60m' => ['label' => '60 Min', 'minutes' => 60],
        ];

        $selectedWorkerWindow = $request->string('worker_window')->toString();
        if (! isset($workerWindowOptions[$selectedWorkerWindow])) {
            $selectedWorkerWindow = '30m';
        }

        $workerWindow = $workerWindowOptions[$selectedWorkerWindow];

        $windowOptions = [
            '24h' => ['label' => '24 Hours', 'hours' => 24, 'bucket' => 'hour'],
            '7d' => ['label' => '7 Days', 'hours' => 24 * 7, 'bucket' => 'day'],
            '30d' => ['label' => '30 Days', 'hours' => 24 * 30, 'bucket' => 'day'],
        ];

        $selectedWindow = $request->string('sync_window')->toString();
        if (! isset($windowOptions[$selectedWindow])) {
            $selectedWindow = '7d';
        }

        $windowConfig = $windowOptions[$selectedWindow];
        $from = $now->copy()->subHours($windowConfig['hours']);

        $jobsBase = InspectionSyncJob::query()
            ->whereBetween('created_at', [$from, $now]);

        $latency = (clone $jobsBase)
            ->whereNotNull('processing_latency_ms')
            ->selectRaw('AVG(processing_latency_ms) as avg_latency_ms, MAX(processing_latency_ms) as max_latency_ms')
            ->first();

        $trendBuckets = [];
        $bucketPointer = $windowConfig['bucket'] === 'hour'
            ? $from->copy()->startOfHour()
            : $from->copy()->startOfDay();

        while ($bucketPointer->lte($now)) {
            $key = $windowConfig['bucket'] === 'hour'
                ? $bucketPointer->format('Y-m-d H:00:00')
                : $bucketPointer->toDateString();

            $trendBuckets[$key] = [
                'label' => $windowConfig['bucket'] === 'hour' ? $bucketPointer->format('H:i') : $bucketPointer->format('M d'),
                'jobs' => 0,
                'latency_sum' => 0.0,
                'latency_count' => 0,
            ];

            $windowConfig['bucket'] === 'hour' ? $bucketPointer->addHour() : $bucketPointer->addDay();
        }

        $trendRows = (clone $jobsBase)
            ->select(['created_at', 'processing_latency_ms'])
            ->get();

        foreach ($trendRows as $row) {
            if (! $row->created_at) {
                continue;
            }

            $key = $windowConfig['bucket'] === 'hour'
                ? $row->created_at->copy()->startOfHour()->format('Y-m-d H:00:00')
                : $row->created_at->toDateString();

            if (! isset($trendBuckets[$key])) {
                continue;
            }

            $trendBuckets[$key]['jobs']++;

            if ($row->processing_latency_ms !== null) {
                $trendBuckets[$key]['latency_sum'] += (float) $row->processing_latency_ms;
                $trendBuckets[$key]['latency_count']++;
            }
        }

        $failingDevicesBase = InspectionSyncJob::query()
            ->whereBetween('created_at', [$from, $now])
            ->whereIn('status', ['failed', 'conflict'])
            ->selectRaw("COALESCE(NULLIF(device_identifier, ''), 'unknown-device') as device")
            ->select(['status', 'created_at']);

        $topFailingDevices = DB::query()
            ->fromSub($failingDevicesBase, 'sync_jobs')
            ->select('device')
            ->selectRaw('COUNT(*) as failures')
            ->selectRaw("SUM(CASE WHEN status = 'conflict' THEN 1 ELSE 0 END) as conflicts")
            ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed")
            ->selectRaw('MAX(created_at) as last_seen_at')
            ->groupBy('device')
            ->orderByDesc('failures')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                return [
                    'device' => (string) $row->device,
                    'failures' => (int) $row->failures,
                    'conflicts' => (int) $row->conflicts,
                    'failed' => (int) $row->failed,
                    'last_seen_at' => $row->last_seen_at ? Carbon::parse($row->last_seen_at)->toIso8601String() : null,
                ];
            })
            ->values()
            ->all();

        $trend = collect($trendBuckets)
            ->map(function (array $bucket) {
                return [
                    'label' => $bucket['label'],
                    'jobs' => (int) $bucket['jobs'],
                    'avg_latency_ms' => $bucket['latency_count'] > 0
                        ? ($bucket['latency_sum'] / $bucket['latency_count'])
                        : null,
                ];
            })
            ->values()
            ->all();

        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();

        $topModulesToday = AuditLog::query()
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->selectRaw('module, COUNT(*) as total')
            ->groupBy('module')
            ->orderByDesc('total')
            ->limit(3)
            ->get()
            ->map(fn ($row) => [
                'module' => (string) $row->module,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        $cleanupStatus = Cache::get('audit_logs_cleanup_status');
        $cleanupStatus = is_array($cleanupStatus) ? $cleanupStatus : null;

        $stats = [
            'kpis' => [
                'incidents' => Incident::query()->count(),
                'audits' => SiteAudit::query()->count(),
                'trainings' => Training::query()->count(),
            ],
            'totalUsers' => User::query()->count(),
            'activeSessions' => MobileAccessToken::query()->where('is_active', true)->count(),
            'revenue' => 9420,
            'tasks' => 17,
            'workerTracking' => [
                'last_updated_at' => $now->toIso8601String(),
                'window' => [
                    'selected' => $selectedWorkerWindow,
                    'label' => $workerWindow['label'],
                    'options' => collect($workerWindowOptions)->map(fn (array $option, string $key) => [
                        'key' => $key,
                        'label' => $option['label'],
                    ])->values()->all(),
                ],
                'active_workers' => Worker::query()->where('status', 'active')->count(),
                'on_site_now' => AttendanceLog::query()
                    ->where('inside_geofence', true)
                    ->where('logged_at', '>=', $now->copy()->subMinutes($workerWindow['minutes']))
                    ->distinct('worker_id')
                    ->count('worker_id'),
                'alerts_last_24h' => AttendanceLog::query()
                    ->where('inside_geofence', false)
                    ->whereBetween('logged_at', [$now->copy()->subDay(), $now])
                    ->count(),
            ],
            'siteAuditSummary' => [
                'scheduled' => SiteAudit::query()->whereIn('status', ['draft', 'scheduled'])->count(),
                'in_review' => SiteAudit::query()->whereIn('status', ['submitted', 'under_review'])->count(),
                'approved' => SiteAudit::query()->where('status', 'approved')->count(),
                'open_ncr' => NcrReport::query()->whereIn('status', ['open', 'in_progress', 'pending_verification'])->count(),
                'overdue_actions' => CorrectiveAction::query()
                    ->whereIn('status', ['open', 'in_progress'])
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->count(),
            ],
            'auditHealth' => [
                'today_count' => AuditLog::query()->whereBetween('created_at', [$todayStart, $todayEnd])->count(),
                'top_modules_today' => $topModulesToday,
                'cleanup' => [
                    'last_run_at' => $cleanupStatus['last_run_at'] ?? null,
                    'deleted_count' => (int) ($cleanupStatus['deleted_count'] ?? 0),
                    'days' => (int) ($cleanupStatus['days'] ?? 180),
                    'dry_run' => (bool) ($cleanupStatus['dry_run'] ?? false),
                ],
            ],
            'syncTelemetry' => [
                'window' => [
                    'selected' => $selectedWindow,
                    'label' => $windowConfig['label'],
                    'options' => collect($windowOptions)->map(fn (array $option, string $key) => [
                        'key' => $key,
                        'label' => $option['label'],
                    ])->values()->all(),
                ],
                'jobs_total' => (clone $jobsBase)->count(),
                'jobs_pending' => (clone $jobsBase)->where('status', 'pending')->count(),
                'jobs_conflict' => (clone $jobsBase)->where('status', 'conflict')->count(),
                'open_conflicts' => InspectionSyncConflict::query()
                    ->where('resolution_status', 'open')
                    ->whereBetween('created_at', [$from, $now])
                    ->count(),
                'avg_latency_ms' => $latency?->avg_latency_ms !== null ? (float) $latency->avg_latency_ms : null,
                'max_latency_ms' => $latency?->max_latency_ms !== null ? (int) $latency->max_latency_ms : null,
                'trend' => $trend,
                'top_failing_devices' => $topFailingDevices,
            ],
            'recentActivity' => AuditLog::query()
                ->latest('created_at')
                ->limit(6)
                ->get(['module', 'action', 'created_at'])
                ->map(fn (AuditLog $log) => [
                    'module' => $log->module,
                    'action' => $log->action,
                    'created_at' => optional($log->created_at)?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ];

        return view('dashboard', compact('stats'));
    }
}
