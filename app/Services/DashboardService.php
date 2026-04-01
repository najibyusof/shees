<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\AttendanceLog;
use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\CorrectiveAction;
use App\Models\Incident;
use App\Models\Inspection;
use App\Models\NcrReport;
use App\Models\SiteAudit;
use App\Models\Training;
use App\Models\User;
use App\Models\Worker;
use App\Models\IncidentType;
use Carbon\CarbonInterface;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardService
{
    /**
     * @var array{window_days:int,higher_is_better:bool,cache_seconds:int}
     */
    private array $activeTrendSettings = [
        'window_days' => 7,
        'higher_is_better' => false,
        'cache_seconds' => 120,
    ];

    private const ROLE_PRIORITY = [
        'Admin',
        'Manager',
        'Safety Officer',
        'Auditor',
        'Supervisor',
        'Worker',
        'HOD HSSE',
        'APSB PD',
        'MRTS',
    ];

    /**
     * @return array{active_role:string, roles:array<int,string>, widgets:array<int,array<string,mixed>>}
     */
    public function buildWebDashboard(User $user, array $filters = []): array
    {
        $activeRole = $this->resolveActiveRole($user);
        $roles = $user->roles->pluck('name')->values()->all();

        return [
            'active_role' => $activeRole,
            'roles' => $roles,
            'widgets' => $this->widgetsForRole($user, $activeRole),
            'chart_profile' => $this->getChartProfileByRole($user),
            'analytics' => $this->buildAnalytics($user, $filters),
        ];
    }

    /*
     * ─────────────────────────────────────────────────────────────────────────
     *  Advanced analytics helpers
     * ─────────────────────────────────────────────────────────────────────────
     */

    /**
     * Month-over-month comparison for any timestamped query.
     *
     * @return array{current_month:int,previous_month:int,change_pct:float,direction:string,current_label:string,previous_label:string}
     */
    private function buildMomComparison(Builder $query, string $column): array
    {
        $currentStart = now()->startOfMonth();
        $currentEnd   = now()->endOfDay();
        $prevStart    = now()->subMonth()->startOfMonth();
        $prevEnd      = now()->subMonth()->endOfMonth()->endOfDay();

        $current  = (clone $query)->whereBetween($column, [$currentStart, $currentEnd])->count();
        $previous = (clone $query)->whereBetween($column, [$prevStart, $prevEnd])->count();

        if ($previous > 0) {
            $changePct = round((($current - $previous) / $previous) * 100, 1);
        } else {
            $changePct = $current > 0 ? 100.0 : 0.0;
        }

        $direction = $current > $previous ? 'up' : ($current < $previous ? 'down' : 'neutral');

        return [
            'current_month'  => $current,
            'previous_month' => $previous,
            'change_pct'     => $changePct,
            'direction'      => $direction,
            'current_label'  => now()->format('M Y'),
            'previous_label' => now()->subMonth()->format('M Y'),
        ];
    }

    /**
     * Top-N ranking by a string column within a query.
     *
     * @return array{labels:array<int,string>,data:array<int,int>}
     */
    private function buildTopList(Builder $query, string $column, int $limit = 5): array
    {
        $rows = $query
            ->selectRaw("COALESCE(NULLIF(TRIM({$column}), ''), 'Unknown') as metric_key, COUNT(*) as aggregate_count")
            ->groupBy('metric_key')
            ->orderByDesc('aggregate_count')
            ->limit($limit)
            ->get();

        return [
            'labels' => $rows->pluck('metric_key')->map(fn ($l) => (string) $l)->values()->all(),
            'data'   => $rows->pluck('aggregate_count')->map(fn ($c) => (int) $c)->values()->all(),
        ];
    }

    /**
     * Weekly trend — groups data by ISO week and returns up to 16 weeks.
     *
     * @return array{labels:array<int,string>,data:array<int,int>}
     */
    private function buildWeeklyTrend(Builder $query, string $column, CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = $query
            ->selectRaw("YEARWEEK({$column}, 1) as metric_week, MIN({$column}) as week_start_date, COUNT(*) as aggregate_count")
            ->groupBy('metric_week')
            ->orderBy('metric_week')
            ->get();

        $labels = [];
        $series = [];
        foreach ($rows as $row) {
            $labels[] = Carbon::parse((string) $row->week_start_date)->format('d M');
            $series[] = (int) ($row->aggregate_count ?? 0);
        }

        return ['labels' => $labels, 'data' => $series];
    }

    /**
     * Daily trend for recent period.
     *
     * @return array{labels:array<int,string>,data:array<int,int>}
     */
    private function buildDailyTrend(Builder $query, string $column, CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = $query
            ->selectRaw("DATE({$column}) as metric_day, COUNT(*) as aggregate_count")
            ->groupBy('metric_day')
            ->orderBy('metric_day')
            ->get();

        return [
            'labels' => $rows->pluck('metric_day')->map(fn ($d) => Carbon::parse((string) $d)->format('d M'))->values()->all(),
            'data' => $rows->pluck('aggregate_count')->map(fn ($c) => (int) $c)->values()->all(),
        ];
    }

    /**
     * Top workers by incident count in the given date range.
     *
     * @return array{labels:array<int,string>,data:array<int,int>}
     */
    private function buildTopWorkers(User $user, CarbonInterface $from, CarbonInterface $to, int $limit = 5): array
    {
        if (! $user->hasPermissionTo('view_incident')) {
            return ['labels' => [], 'data' => []];
        }

        $rows = $this->incidentAnalyticsQuery($user)
            ->whereBetween('incidents.created_at', [$from, $to])
            ->selectRaw('reported_by, COUNT(*) as incident_count')
            ->groupBy('reported_by')
            ->orderByDesc('incident_count')
            ->limit($limit)
            ->get();

        $userIds   = $rows->pluck('reported_by')->filter()->all();
        $userNames = User::query()->whereIn('id', $userIds)->pluck('name', 'id');

        return [
            'labels' => $rows->map(fn ($row) => (string) ($userNames[$row->reported_by] ?? 'Unknown'))->values()->all(),
            'data'   => $rows->pluck('incident_count')->map(fn ($c) => (int) $c)->values()->all(),
        ];
    }

    /**
     * Top incident types by count in the given date range.
     *
     * @return array{labels:array<int,string>,data:array<int,int>}
     */
    private function buildTopIncidentTypes(User $user, CarbonInterface $from, CarbonInterface $to, int $limit = 5): array
    {
        if (! $user->hasPermissionTo('view_incident')) {
            return ['labels' => [], 'data' => []];
        }

        $rows = $this->incidentAnalyticsQuery($user)
            ->whereBetween('incidents.created_at', [$from, $to])
            ->whereNotNull('incident_type_id')
            ->selectRaw('incident_type_id, COUNT(*) as incident_count')
            ->groupBy('incident_type_id')
            ->orderByDesc('incident_count')
            ->limit($limit)
            ->get();

        $typeIds   = $rows->pluck('incident_type_id')->filter()->all();
        $typeNames = IncidentType::query()->whereIn('id', $typeIds)->pluck('name', 'id');

        return [
            'labels' => $rows->map(fn ($row) => (string) ($typeNames[$row->incident_type_id] ?? 'Unknown'))->values()->all(),
            'data'   => $rows->pluck('incident_count')->map(fn ($c) => (int) $c)->values()->all(),
        ];
    }

    /**
     * @return array{role:string, roles:array<int,string>, widgets:array<string,int|float|string|null>}
     */
    public function buildApiDashboard(User $user, array $filters = []): array
    {
        $activeRole = $this->resolveActiveRole($user);
        $widgets = collect($this->widgetsForRole($user, $activeRole))
            ->mapWithKeys(fn (array $widget) => [$widget['key'] => $widget['value']])
            ->all();

        return [
            'role' => $activeRole,
            'roles' => $user->roles->pluck('name')->values()->all(),
            'widgets' => $widgets,
            'chart_profile' => $this->getChartProfileByRole($user),
            'analytics' => $this->buildAnalytics($user, $filters),
        ];
    }

    /**
     * Returns chart-level visibility profile by active role.
     *
     * @return array<string,array<int,string>>
     */
    public function getChartProfileByRole(User $user): array
    {
        $activeRole = $this->resolveActiveRole($user);

        return match ($activeRole) {
            'Worker' => [
                'incident' => ['over_time', 'weekly_trend'],
                'training' => ['by_status', 'certificate_expiry_health'],
                'worker' => ['attendance_trends', 'weekly_attendance'],
            ],
            'Supervisor' => [
                'incident' => ['by_status', 'by_classification', 'over_time', 'top_locations'],
                'inspection' => ['passed_vs_failed', 'trends'],
                'worker' => ['attendance_trends', 'active_vs_inactive'],
            ],
            'Manager' => [
                'incident' => ['by_status', 'by_classification', 'top_locations', 'over_time', 'weekly_trend', 'top_types'],
                'training' => ['by_status', 'certificate_expiry_health'],
                'inspection' => ['passed_vs_failed', 'trends'],
                'audit' => ['ncr_by_severity', 'open_vs_closed_ncr'],
                'worker' => ['attendance_trends', 'active_vs_inactive', 'weekly_attendance'],
            ],
            'HOD HSSE' => [
                'incident' => ['by_status', 'by_classification', 'over_time', 'weekly_trend'],
                'audit' => ['ncr_by_severity', 'open_vs_closed_ncr'],
            ],
            'MRTS' => [
                'incident' => ['by_status', 'over_time', 'weekly_trend'],
                'audit' => ['open_vs_closed_ncr', 'ncr_by_severity'],
            ],
            default => [
                'incident' => ['*'],
                'training' => ['*'],
                'inspection' => ['*'],
                'audit' => ['*'],
                'worker' => ['*'],
            ],
        };
    }

    /**
     * @param  array{from?:string,to?:string,module?:string}  $filters
     * @return array<string,mixed>
     */
    public function buildAnalytics(User $user, array $filters = []): array
    {
        [$from, $to] = $this->resolveDateRange($filters);
        $module = strtolower((string) ($filters['module'] ?? 'all'));

        $analyticsResolvers = [
            'incident' => fn () => $this->getIncidentStats($user, ['from' => $from->toDateString(), 'to' => $to->toDateString()]),
            'training' => fn () => $this->getTrainingStats($user, ['from' => $from->toDateString(), 'to' => $to->toDateString()]),
            'inspection' => fn () => $this->getInspectionStats($user, ['from' => $from->toDateString(), 'to' => $to->toDateString()]),
            'audit' => fn () => $this->getAuditStats($user, ['from' => $from->toDateString(), 'to' => $to->toDateString()]),
            'worker' => fn () => $this->getWorkerStats($user, ['from' => $from->toDateString(), 'to' => $to->toDateString()]),
        ];

        $allowedModules = $this->getAnalyticsByRole($user);
        $all = [];

        foreach ($allowedModules as $moduleKey) {
            if (isset($analyticsResolvers[$moduleKey])) {
                $all[$moduleKey] = $analyticsResolvers[$moduleKey]();
            }
        }

        if ($module !== '' && $module !== 'all' && array_key_exists($module, $all)) {
            return [$module => $all[$module]];
        }

        return $all;
    }

    /**
     * Returns analytics modules a user can access based on role focus and permissions.
     *
     * @return array<int,string>
     */
    public function getAnalyticsByRole(User $user): array
    {
        $activeRole = $this->resolveActiveRole($user);

        $roleModules = match ($activeRole) {
            'Worker' => ['worker', 'training', 'incident'],
            'Supervisor' => ['incident', 'worker', 'inspection'],
            'Manager' => ['incident', 'training', 'inspection', 'audit', 'worker'],
            'HOD HSSE' => ['incident', 'audit'],
            'MRTS' => ['incident', 'audit'],
            default => ['incident', 'training', 'inspection', 'audit', 'worker'],
        };

        $permissionMap = [
            'incident' => ['view_incident_analytics', 'view_incident'],
            'training' => ['view_training_analytics', 'view_training'],
            'inspection' => ['view_inspection_analytics', 'view_audit'],
            'audit' => ['view_audit_analytics', 'view_audit'],
            'worker' => ['view_worker_analytics', 'view_worker'],
        ];

        return collect($roleModules)
            ->filter(fn (string $module) => $this->userCanAnyPermission($user, $permissionMap[$module] ?? []))
            ->values()
            ->all();
    }

    /**
     * @param  array<int,string>  $permissions
     */
    private function userCanAnyPermission(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{from?:string,to?:string}  $filters
     * @return array<string,mixed>
     */
    public function getIncidentStats(User $user, array $filters = []): array
    {
        [$from, $to] = $this->resolveDateRange($filters);
        $query = $this->incidentAnalyticsQuery($user);

        $statusCounts = $this->countByColumn(
            $this->applyDateRange(clone $query, 'created_at', $from, $to),
            'status'
        );

        $classificationCounts = $this->countByColumn(
            $this->applyDateRange(clone $query, 'created_at', $from, $to),
            'classification'
        );

        return [
            'total_incidents' => (clone $query)->count(),
            'by_status' => [
                'labels' => array_keys($statusCounts),
                'data' => array_values($statusCounts),
            ],
            'over_time' => $this->buildDateTrend(
                $this->applyDateRange(clone $query, 'created_at', $from, $to),
                'created_at',
                $from,
                $to
            ),
            'by_classification' => [
                'labels' => array_keys($classificationCounts),
                'data' => array_values($classificationCounts),
            ],
            'mom_comparison'     => $this->buildMomComparison(clone $query, 'created_at'),
            'top_locations'      => $this->buildTopList($this->applyDateRange(clone $query, 'created_at', $from, $to), 'location', 5),
            'top_classifications'=> $this->buildTopList($this->applyDateRange(clone $query, 'created_at', $from, $to), 'classification', 5),
            'top_workers'        => $this->buildTopWorkers($user, $from, $to),
            'top_types'          => $this->buildTopIncidentTypes($user, $from, $to),
            'weekly_trend'       => $this->buildWeeklyTrend($this->applyDateRange(clone $query, 'created_at', $from, $to), 'created_at', $from, $to),
            'daily_trend'        => $this->buildDailyTrend($this->applyDateRange(clone $query, 'created_at', $from, $to), 'created_at', $from, $to),
        ];
    }

    /**
     * @param  array{from?:string,to?:string}  $filters
     * @return array<string,mixed>
     */
    public function getTrainingStats(User $user, array $filters = []): array
    {
        [$from, $to] = $this->resolveDateRange($filters);
        $query = $this->trainingAnalyticsQuery($user);

        $statusCounts = $this->countByBooleanColumn(
            $this->applyDateRange(clone $query, 'created_at', $from, $to),
            'is_active',
            'Active',
            'Inactive'
        );

        $assignmentQuery = DB::table('training_user')
            ->join('trainings', 'trainings.id', '=', 'training_user.training_id')
            ->whereNull('trainings.deleted_at');

        if (! $user->hasPermissionTo('edit_training')
            && ! $user->hasPermissionTo('approve_training')
            && ! $user->hasPermissionTo('create_training')) {
            $assignmentQuery->where('training_user.user_id', $user->id);
        }

        $assignmentQuery = $this->applyDateRangeToQueryBuilder($assignmentQuery, 'training_user.created_at', $from, $to);

        $totalAssignments = (clone $assignmentQuery)->count();
        $completedAssignments = (clone $assignmentQuery)
            ->where(function ($inner) {
                $inner->whereNotNull('training_user.completed_at')
                    ->orWhereRaw('LOWER(training_user.completion_status) = ?', ['completed']);
            })
            ->count();

        $completionRate = $totalAssignments > 0
            ? round(($completedAssignments / $totalAssignments) * 100, 1)
            : 0.0;

        $expiringCertificates = $this->certificateAnalyticsQuery($user)
            ->whereDate('expires_at', '>=', now()->toDateString())
            ->whereDate('expires_at', '<=', now()->addDays(30)->toDateString())
            ->count();

        return [
            'total_trainings' => (clone $query)->count(),
            'completion_rate' => $completionRate,
            'expiring_certificates' => $expiringCertificates,
            'by_status' => [
                'labels' => array_keys($statusCounts),
                'data' => array_values($statusCounts),
            ],
            'mom_comparison' => $this->buildMomComparison(clone $query, 'created_at'),
        ];
    }

    /**
     * @param  array{from?:string,to?:string}  $filters
     * @return array<string,mixed>
     */
    public function getInspectionStats(User $user, array $filters = []): array
    {
        [$from, $to] = $this->resolveDateRange($filters);
        $query = $this->inspectionAnalyticsQuery($user);
        $windowQuery = $this->applyDateRange(clone $query, 'created_at', $from, $to);

        $failed = (clone $windowQuery)
            ->whereHas('responses', fn (Builder $builder) => $builder->where('is_non_compliant', true))
            ->count();

        $passed = (clone $windowQuery)
            ->whereIn('status', ['completed', 'submitted'])
            ->whereDoesntHave('responses', fn (Builder $builder) => $builder->where('is_non_compliant', true))
            ->count();

        return [
            'total_inspections' => (clone $query)->count(),
            'passed_vs_failed' => [
                'labels' => ['Passed', 'Failed'],
                'data' => [$passed, $failed],
            ],
            'trends' => $this->buildDateTrend(
                $windowQuery,
                'created_at',
                $from,
                $to
            ),
        ];
    }

    /**
     * @param  array{from?:string,to?:string}  $filters
     * @return array<string,mixed>
     */
    public function getAuditStats(User $user, array $filters = []): array
    {
        [$from, $to] = $this->resolveDateRange($filters);
        $auditQuery = $this->auditAnalyticsQuery($user);
        $ncrQuery = $this->ncrAnalyticsQuery($user);

        $severityCounts = $this->countByColumn(
            $this->applyDateRange(clone $ncrQuery, 'created_at', $from, $to),
            'severity'
        );

        $openCount = $this->applyDateRange(clone $ncrQuery, 'created_at', $from, $to)
            ->where(function (Builder $builder) {
                $builder->whereNull('status')
                    ->orWhereRaw('LOWER(status) <> ?', ['closed']);
            })
            ->count();

        $closedCount = $this->applyDateRange(clone $ncrQuery, 'created_at', $from, $to)
            ->whereRaw('LOWER(status) = ?', ['closed'])
            ->count();

            return [
                'total_audits' => (clone $auditQuery)->count(),
                'ncr_by_severity' => [
                    'labels' => array_keys($severityCounts),
                    'data' => array_values($severityCounts),
                ],
                'open_vs_closed_ncr' => [
                    'labels' => ['Open', 'Closed'],
                    'data' => [$openCount, $closedCount],
                ],
                'mom_comparison' => $this->buildMomComparison(clone $ncrQuery, 'created_at'),
        ];
    }

    /**
     * @param  array{from?:string,to?:string}  $filters
     * @return array<string,mixed>
     */
    public function getWorkerStats(User $user, array $filters = []): array
    {
        [$from, $to] = $this->resolveDateRange($filters);
        $workerQuery = $this->workerAnalyticsQuery($user);

        $statusCounts = $this->countByColumn(clone $workerQuery, 'status');
        $activeCount = 0;
        $inactiveCount = 0;

        foreach ($statusCounts as $status => $count) {
            if (strtolower($status) === 'active') {
                $activeCount += $count;
            } else {
                $inactiveCount += $count;
            }
        }

        return [
            'total_workers' => (clone $workerQuery)->count(),
            'attendance_trends' => $this->buildDateTrend(
                $this->applyDateRange(clone $this->attendanceAnalyticsQuery($user), 'logged_at', $from, $to),
                'logged_at',
                $from,
                $to
            ),
            'active_vs_inactive' => [
                'labels' => ['Active', 'Inactive'],
                'data' => [$activeCount, $inactiveCount],
            ],
                'weekly_attendance'  => $this->buildWeeklyTrend(
                    $this->applyDateRange(clone $this->attendanceAnalyticsQuery($user), 'logged_at', $from, $to),
                    'logged_at', $from, $to
                ),
                'mom_comparison' => $this->buildMomComparison(clone $this->attendanceAnalyticsQuery($user), 'logged_at'),
            ];
    }

    private function resolveActiveRole(User $user): string
    {
        $assigned = $user->roles->pluck('name');

        foreach (self::ROLE_PRIORITY as $roleName) {
            if ($assigned->contains($roleName)) {
                return $roleName;
            }
        }

        return $assigned->first() ?? 'Worker';
    }

    /**
     * @param  array{from?:string,to?:string}  $filters
     * @return array{0:Carbon,1:Carbon}
     */
    private function resolveDateRange(array $filters): array
    {
        $to = isset($filters['to']) && $filters['to'] !== ''
            ? Carbon::parse($filters['to'])->endOfDay()
            : now()->endOfDay();

        $from = isset($filters['from']) && $filters['from'] !== ''
            ? Carbon::parse($filters['from'])->startOfDay()
            : now()->subMonths(6)->startOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    /**
     * @param  Builder<Incident>  $query
     * @return Builder<Incident>
     */
    private function incidentAnalyticsQuery(User $user): Builder
    {
        if (! $user->hasPermissionTo('view_incident')) {
            return Incident::query()->whereRaw('1 = 0');
        }

        return Incident::query()->accessibleTo($user);
    }

    /**
     * @param  Builder<Training>  $query
     * @return Builder<Training>
     */
    private function trainingAnalyticsQuery(User $user): Builder
    {
        if (! $user->hasPermissionTo('view_training')) {
            return Training::query()->whereRaw('1 = 0');
        }

        return Training::query()->accessibleTo($user);
    }

    /**
     * @param  Builder<Inspection>  $query
     * @return Builder<Inspection>
     */
    private function inspectionAnalyticsQuery(User $user): Builder
    {
        if (! $user->hasPermissionTo('view_audit')) {
            return Inspection::query()->whereRaw('1 = 0');
        }

        if ($user->hasPermissionTo('edit_audit')
            || $user->hasPermissionTo('approve_audit')
            || $user->hasPermissionTo('create_audit')) {
            return Inspection::query();
        }

        return Inspection::query()->where('inspector_id', $user->id);
    }

    /**
     * @param  Builder<SiteAudit>  $query
     * @return Builder<SiteAudit>
     */
    private function auditAnalyticsQuery(User $user): Builder
    {
        if (! $user->hasPermissionTo('view_audit')) {
            return SiteAudit::query()->whereRaw('1 = 0');
        }

        if ($user->hasPermissionTo('edit_audit')
            || $user->hasPermissionTo('approve_audit')
            || $user->hasPermissionTo('create_audit')) {
            return SiteAudit::query();
        }

        return SiteAudit::query()->where('created_by', $user->id);
    }

    /**
     * @param  Builder<NcrReport>  $query
     * @return Builder<NcrReport>
     */
    private function ncrAnalyticsQuery(User $user): Builder
    {
        if (! $user->hasPermissionTo('view_audit')) {
            return NcrReport::query()->whereRaw('1 = 0');
        }

        if ($user->hasPermissionTo('edit_audit') || $user->hasPermissionTo('approve_audit')) {
            return NcrReport::query();
        }

        return NcrReport::query()->where(function (Builder $builder) use ($user) {
            $builder->where('reported_by', $user->id)
                ->orWhere('owner_id', $user->id);
        });
    }

    /**
     * @param  Builder<Worker>  $query
     * @return Builder<Worker>
     */
    private function workerAnalyticsQuery(User $user): Builder
    {
        if (! $user->hasPermissionTo('view_worker')) {
            return Worker::query()->whereRaw('1 = 0');
        }

        return Worker::query()->accessibleTo($user);
    }

    /**
     * @param  Builder<AttendanceLog>  $query
     * @return Builder<AttendanceLog>
     */
    private function attendanceAnalyticsQuery(User $user): Builder
    {
        if (! $user->hasPermissionTo('view_worker')) {
            return AttendanceLog::query()->whereRaw('1 = 0');
        }

        $workerIds = $this->workerAnalyticsQuery($user)->pluck('id');

        if ($workerIds->isEmpty()) {
            return AttendanceLog::query()->whereRaw('1 = 0');
        }

        return AttendanceLog::query()->whereIn('worker_id', $workerIds->all());
    }

    /**
     * @param  Builder<Certificate>  $query
     * @return Builder<Certificate>
     */
    private function certificateAnalyticsQuery(User $user): Builder
    {
        if (! $user->hasPermissionTo('view_training')) {
            return Certificate::query()->whereRaw('1 = 0');
        }

        if ($user->hasPermissionTo('edit_training')
            || $user->hasPermissionTo('approve_training')
            || $user->hasPermissionTo('create_training')) {
            return Certificate::query();
        }

        return Certificate::query()->where('user_id', $user->id);
    }

    /**
     * @param  Builder<mixed>  $query
     * @return Builder<mixed>
     */
    private function applyDateRange(Builder $query, string $column, CarbonInterface $from, CarbonInterface $to): Builder
    {
        return $query->whereBetween($column, [$from, $to]);
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Query\Builder
     */
    private function applyDateRangeToQueryBuilder($query, string $column, CarbonInterface $from, CarbonInterface $to)
    {
        return $query->whereBetween($column, [$from, $to]);
    }

    /**
     * @param  Builder<mixed>  $query
     * @return array<string,int>
     */
    private function countByColumn(Builder $query, string $column): array
    {
        /** @var \Illuminate\Support\Collection<int,array{key:string|null,count:int}> $rows */
        $rows = $query
            ->selectRaw("COALESCE(NULLIF(TRIM({$column}), ''), 'Unknown') as metric_key, COUNT(*) as aggregate_count")
            ->groupBy('metric_key')
            ->orderByDesc('aggregate_count')
            ->get()
            ->map(fn ($row) => [
                'key' => (string) ($row->metric_key ?? 'Unknown'),
                'count' => (int) ($row->aggregate_count ?? 0),
            ]);

        return $rows
            ->mapWithKeys(fn (array $row) => [$row['key'] => $row['count']])
            ->all();
    }

    /**
     * @param  Builder<mixed>  $query
     * @return array<string,int>
     */
    private function countByBooleanColumn(Builder $query, string $column, string $trueLabel, string $falseLabel): array
    {
        $trueCount = (clone $query)->where($column, true)->count();
        $falseCount = (clone $query)->where($column, false)->count();

        return [
            $trueLabel => $trueCount,
            $falseLabel => $falseCount,
        ];
    }

    /**
     * @param  Builder<mixed>  $query
     * @return array{labels:array<int,string>,data:array<int,int>}
     */
    private function buildDateTrend(Builder $query, string $dateColumn, CarbonInterface $from, CarbonInterface $to): array
    {
        $start = Carbon::instance($from)->startOfMonth();
        $end = Carbon::instance($to)->startOfMonth();

        $period = CarbonPeriod::create($start, '1 month', $end);
        $labels = [];
        $series = [];

        foreach ($period as $date) {
            $key = $date->format('Y-m');
            $labels[$key] = $date->format('M');
            $series[$key] = 0;
        }

        $rows = $query
            ->selectRaw("DATE_FORMAT({$dateColumn}, '%Y-%m') as metric_month, COUNT(*) as aggregate_count")
            ->groupBy('metric_month')
            ->orderBy('metric_month')
            ->get();

        foreach ($rows as $row) {
            $key = (string) ($row->metric_month ?? '');
            if (array_key_exists($key, $series)) {
                $series[$key] = (int) ($row->aggregate_count ?? 0);
            }
        }

        return [
            'labels' => array_values($labels),
            'data' => array_values($series),
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function widgetsForRole(User $user, string $roleName): array
    {
        return match ($roleName) {
            'Admin' => [
                $this->metric('total_incidents', 'Total Incidents', $this->count('dashboard.admin.total_incidents', fn () => Incident::query()->count()), 'view_incident', 'Incident', $this->trendForWidget('total_incidents', $user)),
                $this->metric('total_users', 'Total Users', $this->count('dashboard.admin.total_users', fn () => User::query()->count()), 'view_user_management', 'Admin', $this->trendForWidget('total_users', $user)),
                $this->metric('system_logs', 'System Logs', $this->count('dashboard.admin.system_logs', fn () => AuditLog::query()->count()), 'view_audit', 'Admin', $this->trendForWidget('system_logs', $user)),
                $this->metric('kpi_overview', 'KPI Overview', $this->count('dashboard.admin.kpi_overview', fn () => SiteAudit::query()->where('status', 'approved')->count()), 'view_report', 'Reports', $this->trendForWidget('kpi_overview', $user)),
            ],
            'Manager' => [
                $this->metric('draft_pending_submission', 'Draft Incidents Pending Submission', $this->count("dashboard.manager.draft_pending_submission.{$user->id}", fn () => $this->teamIncidentQuery($user)->where('status', 'draft')->count()), 'submit_incident', 'Incident', $this->trendForWidget('draft_pending_submission', $user)),
                $this->metric('team_incidents', 'Team Incidents (30 Days)', $this->count("dashboard.manager.team_incidents.{$user->id}", fn () => $this->teamIncidentQuery($user)->whereDate('created_at', '>=', now()->subDays(30)->toDateString())->count()), 'view_incident', 'Incident', $this->trendForWidget('team_incidents', $user)),
                $this->metric('overdue_actions', 'Overdue Actions', $this->count("dashboard.manager.overdue_actions.{$user->id}", fn () => CorrectiveAction::query()->whereIn('status', ['open', 'in_progress'])->whereDate('due_date', '<', now()->toDateString())->count()), 'view_audit', 'Incident', $this->trendForWidget('overdue_actions', $user)),
                $this->metric('review_sla_breach', 'Review SLA Breach (>48h)', $this->count("dashboard.manager.review_sla_breach.{$user->id}", fn () => $this->teamIncidentQuery($user)->where('status', 'draft_submitted')->where('created_at', '<=', now()->subHours(48))->count()), 'review_incident', 'Incident', $this->trendForWidget('review_sla_breach', $user)),
            ],
            'Safety Officer' => [
                $this->metric('assigned_incidents', 'Assigned Incidents', $this->count('dashboard.safety.assigned_incidents', fn () => Incident::query()->where('reviewed_by', $user->id)->orWhere('approved_by', $user->id)->count()), 'review_incident', 'Incident', $this->trendForWidget('assigned_incidents', $user)),
                $this->metric('investigation_tasks', 'Investigation Tasks', $this->count('dashboard.safety.investigation_tasks', fn () => Incident::query()->whereIn('status', ['draft_submitted', 'draft_reviewed'])->count()), 'review_incident', 'Incident', $this->trendForWidget('investigation_tasks', $user)),
                $this->metric('open_corrective_actions', 'Open Corrective Actions', $this->count('dashboard.safety.open_corrective_actions', fn () => CorrectiveAction::query()->whereIn('status', ['open', 'in_progress'])->count()), 'view_audit', 'Incident', $this->trendForWidget('open_corrective_actions', $user)),
            ],
            'Auditor' => [
                $this->metric('audit_schedules', 'Audit Schedules', $this->count('dashboard.auditor.audit_schedules', fn () => SiteAudit::query()->whereIn('status', ['draft', 'scheduled', 'in_progress'])->count()), 'view_audit', 'Audit', $this->trendForWidget('audit_schedules', $user)),
                $this->metric('ncr_status', 'NCR Status', $this->count('dashboard.auditor.ncr_status', fn () => CorrectiveAction::query()->whereIn('status', ['open', 'in_progress', 'completed'])->count()), 'view_audit', 'Audit', $this->trendForWidget('ncr_status', $user)),
                $this->metric('compliance_metrics', 'Compliance Metrics', $this->count('dashboard.auditor.compliance_metrics', fn () => SiteAudit::query()->where('status', 'approved')->count()), 'view_report', 'Audit', $this->trendForWidget('compliance_metrics', $user)),
            ],
            'Supervisor' => [
                $this->metric('site_incidents', 'Site Incidents', $this->count('dashboard.supervisor.site_incidents', fn () => Incident::query()->where('reported_by', $user->id)->count()), 'view_incident', 'Incident', $this->trendForWidget('site_incidents', $user)),
                $this->metric('worker_attendance', 'Worker Attendance', $this->count('dashboard.supervisor.worker_attendance', fn () => AttendanceLog::query()->whereDate('logged_at', today())->distinct('worker_id')->count('worker_id')), 'view_worker', 'Worker', $this->trendForWidget('worker_attendance', $user)),
                $this->metric('daily_activity_logs', 'Daily Activity Logs', $this->count('dashboard.supervisor.daily_activity_logs', fn () => AuditLog::query()->whereDate('created_at', today())->count()), 'view_worker', 'Worker', $this->trendForWidget('daily_activity_logs', $user)),
            ],
            'Worker' => [
                $this->metric('personal_incidents_submitted', 'Personal Incidents Submitted', $this->count("dashboard.worker.personal_incidents_submitted.{$user->id}", fn () => Incident::query()->where('reported_by', $user->id)->count()), 'view_incident', 'Incident', $this->trendForWidget('personal_incidents_submitted', $user)),
                $this->metric('training_status', 'Training Status', $this->count("dashboard.worker.training_status.{$user->id}", function () use ($user) {
                    if (! Schema::hasTable('training_user')) {
                        return 0;
                    }

                    return (int) Training::query()
                        ->join('training_user', 'trainings.id', '=', 'training_user.training_id')
                        ->where('training_user.user_id', $user->id)
                        ->where(function ($query) {
                            $query->whereNotNull('training_user.completed_at')
                                ->orWhereRaw('LOWER(training_user.completion_status) = ?', ['completed']);
                        })
                        ->count();
                }), 'view_training', 'Training', $this->trendForWidget('training_status', $user)),
                $this->metric('notifications', 'Notifications', $this->count("dashboard.worker.notifications.{$user->id}", fn () => $user->unreadNotifications()->count()), 'view_incident', 'Incident', $this->trendForWidget('notifications', $user)),
            ],
            'HOD HSSE' => [
                $this->metric('pending_draft_review', 'Draft Approvals (Review Stage)', $this->count('dashboard.hod.pending_draft_review', fn () => Incident::query()->where('status', 'draft_submitted')->count()), 'review_incident', 'Incident', $this->trendForWidget('pending_draft_review', $user)),
                $this->metric('final_report_submissions', 'Final Report Submissions', $this->count('dashboard.hod.final_report_submissions', fn () => Incident::query()->where('status', 'final_submitted')->count()), 'submit_incident', 'Incident', $this->trendForWidget('final_report_submissions', $user)),
                $this->metric('closure_requests', 'Closure Requests', $this->count('dashboard.hod.closure_requests', fn () => Incident::query()->where('status', 'pending_closure')->count()), 'request_closure', 'Incident', $this->trendForWidget('closure_requests', $user)),
                $this->metric('incident_escalation_overview', 'Incident Escalation Overview', $this->count('dashboard.hod.incident_escalation_overview', fn () => Incident::query()->whereIn('status', ['draft_reviewed', 'final_reviewed', 'pending_closure'])->count()), 'review_incident', 'Incident', $this->trendForWidget('incident_escalation_overview', $user)),
                $this->metric('closure_sla_breach', 'Closure SLA Breach (>7d)', $this->count('dashboard.hod.closure_sla_breach', fn () => Incident::query()->where('status', 'pending_closure')->where('updated_at', '<=', now()->subDays(7))->count()), 'request_closure', 'Incident', $this->trendForWidget('closure_sla_breach', $user)),
            ],
            'APSB PD' => [
                $this->metric('final_submission_monitoring', 'Final Incident Submission Monitoring', $this->count('dashboard.apsb.final_submission_monitoring', fn () => Incident::query()->where('status', 'final_submitted')->count()), 'submit_incident', 'Incident', $this->trendForWidget('final_submission_monitoring', $user)),
                $this->metric('project_incident_overview', 'Project-level Incident Overview', $this->count('dashboard.apsb.project_incident_overview', fn () => Incident::query()->count()), 'view_incident', 'Incident', $this->trendForWidget('project_incident_overview', $user)),
                $this->metric('active_projects', 'Active Projects', $this->count('dashboard.apsb.active_projects', fn () => Incident::query()->whereNotNull('work_package_id')->distinct('work_package_id')->count('work_package_id')), 'view_incident', 'Incident', $this->trendForWidget('active_projects', $user)),
                $this->metric('compliance_status', 'Compliance Status', $this->count('dashboard.apsb.compliance_status', fn () => SiteAudit::query()->where('status', 'approved')->count()), 'view_audit', 'Audit', $this->trendForWidget('compliance_status', $user)),
            ],
            'MRTS' => [
                $this->metric('final_approval_queue', 'Final Approval Queue', $this->count('dashboard.mrts.final_approval_queue', fn () => Incident::query()->where('status', 'final_submitted')->count()), 'approve_final', 'Incident', $this->trendForWidget('final_approval_queue', $user)),
                $this->metric('closure_approvals', 'Closure Approvals', $this->count('dashboard.mrts.closure_approvals', fn () => Incident::query()->where('status', 'pending_closure')->count()), 'approve_closure', 'Incident', $this->trendForWidget('closure_approvals', $user)),
                $this->metric('high_level_analytics', 'High-level Analytics Dashboard', $this->count('dashboard.mrts.high_level_analytics', fn () => Incident::query()->where('status', 'closed')->count()), 'view_report', 'Reports', $this->trendForWidget('high_level_analytics', $user)),
                $this->metric('final_approval_sla_breach', 'Final Approval SLA Breach (>72h)', $this->count('dashboard.mrts.final_approval_sla_breach', fn () => Incident::query()->where('status', 'final_submitted')->where('updated_at', '<=', now()->subHours(72))->count()), 'approve_final', 'Incident', $this->trendForWidget('final_approval_sla_breach', $user)),
            ],
            default => [
                $this->metric('total_incidents', 'Total Incidents', $this->count('dashboard.default.total_incidents', fn () => Incident::query()->count()), 'view_incident', 'Incident', $this->trendForWidget('total_incidents', $user)),
            ],
        };
    }

    /**
     * @return array<string,mixed>
     */
    private function metric(string $key, string $label, int|float|string|null $value, string $permission, string $module, ?array $trend = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'permission' => $permission,
            'module' => $module,
            'trend' => $trend,
        ];
    }

    /**
     * @return array{direction:string,label:string}|null
     */
    private function trendForWidget(string $widgetKey, User $user): ?array
    {
        $this->activeTrendSettings = $this->trendSettings($widgetKey);

        return match ($widgetKey) {
            'total_incidents' => $this->windowTrend(
                "dashboard.trend.total_incidents.{$user->id}",
                fn (CarbonInterface $from, CarbonInterface $to): int => Incident::query()->whereBetween('created_at', [$from, $to])->count()
            ),
            'total_users' => $this->windowTrend(
                "dashboard.trend.total_users.{$user->id}",
                fn (CarbonInterface $from, CarbonInterface $to): int => User::query()->whereBetween('created_at', [$from, $to])->count()
            ),
            'system_logs', 'daily_activity_logs' => $this->windowTrend(
                "dashboard.trend.{$widgetKey}.{$user->id}",
                fn (CarbonInterface $from, CarbonInterface $to): int => AuditLog::query()->whereBetween('created_at', [$from, $to])->count()
            ),
            'kpi_overview', 'compliance_metrics', 'compliance_status' => $this->windowTrend(
                "dashboard.trend.{$widgetKey}.{$user->id}",
                fn (CarbonInterface $from, CarbonInterface $to): int => SiteAudit::query()
                    ->where('status', 'approved')
                    ->whereBetween('created_at', [$from, $to])
                    ->count()
            ),
            'draft_pending_submission' => $this->windowTrend(
                "dashboard.trend.draft_pending_submission.{$user->id}",
                fn (CarbonInterface $from, CarbonInterface $to): int => $this->teamIncidentQuery($user)
                    ->where('status', 'draft')
                    ->whereBetween('created_at', [$from, $to])
                    ->count()
            ),
            'team_incidents' => $this->windowTrend(
                "dashboard.trend.team_incidents.{$user->id}",
                fn (CarbonInterface $from, CarbonInterface $to): int => $this->teamIncidentQuery($user)
                    ->whereBetween('created_at', [$from, $to])
                    ->count()
            ),
            'overdue_actions', 'open_corrective_actions', 'ncr_status' => $this->windowTrend(
                "dashboard.trend.{$widgetKey}.{$user->id}",
                fn (CarbonInterface $from, CarbonInterface $to): int => CorrectiveAction::query()
                    ->whereBetween('created_at', [$from, $to])
                    ->count()
            ),
            'review_sla_breach' => $this->windowTrend(
                "dashboard.trend.review_sla_breach.{$user->id}",
                fn (CarbonInterface $from, CarbonInterface $to): int => Incident::query()
                    ->where('status', 'draft_submitted')
                    ->whereBetween('created_at', [$from, $to])
                    ->count()
            ),
            'assigned_incidents' => $this->windowTrend(
                "dashboard.trend.assigned_incidents.{$user->id}",
                fn (CarbonInterface $from, CarbonInterface $to): int => Incident::query()
                    ->where(fn (Builder $query) => $query->where('reviewed_by', $user->id)->orWhere('approved_by', $user->id))
                    ->whereBetween('created_at', [$from, $to])
                    ->count()
            ),
            'investigation_tasks', 'pending_draft_review', 'final_report_submissions', 'closure_requests', 'incident_escalation_overview', 'final_submission_monitoring', 'project_incident_overview', 'final_approval_queue', 'closure_approvals', 'high_level_analytics', 'final_approval_sla_breach', 'site_incidents', 'personal_incidents_submitted' => $this->windowTrend(
                "dashboard.trend.{$widgetKey}.{$user->id}",
                fn (CarbonInterface $from, CarbonInterface $to): int => Incident::query()->whereBetween('created_at', [$from, $to])->count()
            ),
            'audit_schedules' => $this->windowTrend(
                "dashboard.trend.audit_schedules.{$user->id}",
                fn (CarbonInterface $from, CarbonInterface $to): int => SiteAudit::query()
                    ->whereIn('status', ['draft', 'scheduled', 'in_progress'])
                    ->whereBetween('created_at', [$from, $to])
                    ->count()
            ),
            'worker_attendance' => $this->windowTrend(
                "dashboard.trend.worker_attendance.{$user->id}",
                fn (CarbonInterface $from, CarbonInterface $to): int => AttendanceLog::query()
                    ->whereBetween('logged_at', [$from, $to])
                    ->distinct('worker_id')
                    ->count('worker_id')
            ),
            'training_status' => $this->windowTrend(
                "dashboard.trend.training_status.{$user->id}",
                function (CarbonInterface $from, CarbonInterface $to) use ($user): int {
                    if (! Schema::hasTable('training_user')) {
                        return 0;
                    }

                    return (int) Training::query()
                        ->join('training_user', 'trainings.id', '=', 'training_user.training_id')
                        ->where('training_user.user_id', $user->id)
                        ->whereBetween('training_user.updated_at', [$from, $to])
                        ->where(function ($query) {
                            $query->whereNotNull('training_user.completed_at')
                                ->orWhereRaw('LOWER(training_user.completion_status) = ?', ['completed']);
                        })
                        ->count();
                }
            ),
            'notifications' => $this->windowTrend(
                "dashboard.trend.notifications.{$user->id}",
                fn (CarbonInterface $from, CarbonInterface $to): int => $user->notifications()->whereBetween('created_at', [$from, $to])->count()
            ),
            'closure_sla_breach' => $this->windowTrend(
                "dashboard.trend.closure_sla_breach.{$user->id}",
                fn (CarbonInterface $from, CarbonInterface $to): int => Incident::query()
                    ->where('status', 'pending_closure')
                    ->whereBetween('updated_at', [$from, $to])
                    ->count()
            ),
            'active_projects' => $this->windowTrend(
                "dashboard.trend.active_projects.{$user->id}",
                fn (CarbonInterface $from, CarbonInterface $to): int => Incident::query()
                    ->whereNotNull('work_package_id')
                    ->whereBetween('created_at', [$from, $to])
                    ->distinct('work_package_id')
                    ->count('work_package_id')
            ),
            default => null,
        };
    }

    /**
     * @param  \Closure(CarbonInterface,CarbonInterface):int  $resolver
     * @return array{direction:string,label:string}
     */
    private function windowTrend(
        string $cacheBaseKey,
        \Closure $resolver,
        ?int $windowDays = null,
        ?bool $higherIsBetter = null,
        ?int $cacheSeconds = null
    ): array
    {
        $windowDays ??= $this->activeTrendSettings['window_days'];
        $higherIsBetter ??= $this->activeTrendSettings['higher_is_better'];
        $cacheSeconds ??= $this->activeTrendSettings['cache_seconds'];

        $currentTo = now();
        $currentFrom = (clone $currentTo)->subDays($windowDays);
        $previousTo = $currentFrom;
        $previousFrom = (clone $previousTo)->subDays($windowDays);

        $current = $this->count(
            "{$cacheBaseKey}.current",
            fn (): int => (int) $resolver($currentFrom, $currentTo),
            $cacheSeconds
        );

        $previous = $this->count(
            "{$cacheBaseKey}.previous",
            fn (): int => (int) $resolver($previousFrom, $previousTo),
            $cacheSeconds
        );

        return $this->buildTrend($current, $previous, $windowDays, $higherIsBetter);
    }

    /**
     * @return array{window_days:int,higher_is_better:bool,cache_seconds:int}
     */
    private function trendSettings(string $widgetKey): array
    {
        $defaultWindow = (int) config('dashboard.trends.default_window_days', 7);
        $defaultCacheSeconds = (int) config('dashboard.trends.cache_seconds', 120);

        /** @var array<string,mixed> $widgetOverrides */
        $widgetOverrides = (array) config("dashboard.trends.widgets.{$widgetKey}", []);

        return [
            'window_days' => max(1, (int) ($widgetOverrides['window_days'] ?? $defaultWindow)),
            'higher_is_better' => (bool) ($widgetOverrides['higher_is_better'] ?? false),
            'cache_seconds' => max(1, (int) ($widgetOverrides['cache_seconds'] ?? $defaultCacheSeconds)),
        ];
    }

    /**
     * @return array{direction:string,label:string}
     */
    private function buildTrend(int $current, int $previous, int $windowDays, bool $higherIsBetter = false): array
    {
        $delta = $current - $previous;

        if ($delta === 0) {
            return [
                'direction' => 'neutral',
                'label' => "No change (vs {$windowDays}d)",
            ];
        }

        return [
            'direction' => $delta > 0 ? 'up' : 'down',
            'label' => sprintf('%+d vs %sd', $delta, $windowDays),
        ];
    }

    private function count(string $cacheKey, \Closure $resolver, int $seconds = 120): int
    {
        return (int) Cache::remember($cacheKey, now()->addSeconds($seconds), $resolver);
    }

    /**
     * @return Builder<Incident>
     */
    private function teamIncidentQuery(User $user): Builder
    {
        $teamUserIds = $this->teamUserIds($user);

        return Incident::query()->whereIn('reported_by', $teamUserIds);
    }

    /**
     * @return array<int,int>
     */
    private function teamUserIds(User $user): array
    {
        return Cache::remember(
            "dashboard.team_user_ids.{$user->id}",
            now()->addMinutes(10),
            function () use ($user): array {
                $roleIds = $user->roles->pluck('id')->all();

                if ($roleIds === []) {
                    return [$user->id];
                }

                $ids = User::query()
                    ->whereHas('roles', fn (Builder $query) => $query->whereIn('roles.id', $roleIds))
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();

                return $ids === [] ? [$user->id] : $ids;
            }
        );
    }
}
