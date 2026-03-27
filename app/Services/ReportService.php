<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\SiteAudit;
use App\Models\Training;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * @return array<string, mixed>
     */
    public function build(string $module, array $filters, bool $paginate = true, int $perPage = 20, int $limit = 5000): array
    {
        return match ($module) {
            'trainings' => $this->trainingReport($filters, $paginate, $perPage, $limit),
            'audits' => $this->auditReport($filters, $paginate, $perPage, $limit),
            default => $this->incidentReport($filters, $paginate, $perPage, $limit),
        };
    }

    public function estimateCount(string $module, array $filters): int
    {
        return match ($module) {
            'trainings' => $this->trainingBaseQuery($filters)->count(),
            'audits' => $this->auditBaseQuery($filters)->count(),
            default => $this->incidentBaseQuery($filters)->count(),
        };
    }

    /**
     * @return array{status_breakdown: array<int, array{label: string, count: int}>, trend: array<int, array{label: string, count: int}>}
     */
    public function summary(string $module, array $filters): array
    {
        $statusBreakdown = [];
        $trend = [];

        if ($module === 'trainings') {
            $base = $this->applyTrainingFilters(Training::query(), $filters);

            $statusBreakdown = [
                ['label' => 'active', 'count' => (clone $base)->where('is_active', true)->count()],
                ['label' => 'inactive', 'count' => (clone $base)->where('is_active', false)->count()],
            ];

            $trendRows = (clone $base)
                ->selectRaw('DATE(created_at) as day_key, COUNT(*) as total')
                ->where('created_at', '>=', now()->subDays(13)->startOfDay())
                ->groupByRaw('DATE(created_at)')
                ->orderByRaw('DATE(created_at) asc')
                ->get();

            $trend = $this->fillTrendBuckets($trendRows, 14);
        }

        if ($module === 'audits') {
            $base = $this->applyAuditFilters(SiteAudit::query(), $filters);

            $statusBreakdown = collect(SiteAudit::STATUSES)
                ->map(fn (string $status) => [
                    'label' => $status,
                    'count' => (clone $base)->where('status', $status)->count(),
                ])
                ->values()
                ->all();

            $trendRows = (clone $base)
                ->selectRaw('DATE(created_at) as day_key, COUNT(*) as total')
                ->where('created_at', '>=', now()->subDays(13)->startOfDay())
                ->groupByRaw('DATE(created_at)')
                ->orderByRaw('DATE(created_at) asc')
                ->get();

            $trend = $this->fillTrendBuckets($trendRows, 14);
        }

        if ($module === 'incidents') {
            $base = $this->applyIncidentFilters(Incident::query(), $filters);

            $statusBreakdown = collect(Incident::STATUSES)
                ->map(fn (string $status) => [
                    'label' => $status,
                    'count' => (clone $base)->where('status', $status)->count(),
                ])
                ->values()
                ->all();

            $trendRows = (clone $base)
                ->selectRaw('DATE(datetime) as day_key, COUNT(*) as total')
                ->where('datetime', '>=', now()->subDays(13)->startOfDay())
                ->groupByRaw('DATE(datetime)')
                ->orderByRaw('DATE(datetime) asc')
                ->get();

            $trend = $this->fillTrendBuckets($trendRows, 14);
        }

        return [
            'status_breakdown' => $statusBreakdown,
            'trend' => $trend,
        ];
    }

    /**
     * @param  Incident|Training|SiteAudit  $row
     * @return array<int, string>
     */
    public function mapRow(string $module, mixed $row): array
    {
        if ($module === 'trainings') {
            /** @var Training $row */
            return [
                (string) $row->id,
                $row->titleForLocale(),
                $row->is_active ? 'active' : 'inactive',
                optional($row->starts_at)?->format('Y-m-d') ?? '-',
                optional($row->ends_at)?->format('Y-m-d') ?? '-',
                (string) ($row->users_count ?? 0),
                (string) ($row->certificates_count ?? 0),
            ];
        }

        if ($module === 'audits') {
            /** @var SiteAudit $row */
            return [
                (string) $row->id,
                $row->reference_no,
                $row->site_name,
                $row->status,
                optional($row->scheduled_for)?->format('Y-m-d') ?? '-',
                $row->kpi_score !== null ? number_format((float) $row->kpi_score, 1).'%' : '-',
                (string) ($row->ncr_reports_count ?? 0),
                $row->creator?->name ?? 'N/A',
            ];
        }

        /** @var Incident $row */
        return [
            (string) $row->id,
            $row->title,
            $row->status,
            $row->classification,
            $row->location,
            optional($row->datetime)?->format('Y-m-d H:i') ?? '-',
            $row->reporter?->name ?? 'N/A',
        ];
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array<int, array<int, string>>
     */
    public function mapRows(string $module, Collection $rows): array
    {
        return $rows->map(fn ($row) => $this->mapRow($module, $row))->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function incidentReport(array $filters, bool $paginate, int $perPage, int $limit): array
    {
        $query = $this->incidentBaseQuery($filters)->orderByDesc('datetime');
        $rows = $this->resolveRows($query, $paginate, $perPage, $limit);

        return [
            'module' => 'incidents',
            'module_label' => 'Incidents',
            'status_options' => Incident::STATUSES,
            'columns' => ['ID', 'Title', 'Status', 'Classification', 'Location', 'Date/Time', 'User'],
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function trainingReport(array $filters, bool $paginate, int $perPage, int $limit): array
    {
        $query = $this->trainingBaseQuery($filters)
            ->orderByDesc('starts_at')
            ->orderByDesc('id');

        $rows = $this->resolveRows($query, $paginate, $perPage, $limit);

        return [
            'module' => 'trainings',
            'module_label' => 'Training',
            'status_options' => ['active', 'inactive'],
            'columns' => ['ID', 'Title', 'Status', 'Start Date', 'End Date', 'Users', 'Certificates'],
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function auditReport(array $filters, bool $paginate, int $perPage, int $limit): array
    {
        $query = $this->auditBaseQuery($filters)
            ->orderByDesc('scheduled_for')
            ->orderByDesc('id');

        $rows = $this->resolveRows($query, $paginate, $perPage, $limit);

        return [
            'module' => 'audits',
            'module_label' => 'Audits',
            'status_options' => SiteAudit::STATUSES,
            'columns' => ['ID', 'Reference', 'Site', 'Status', 'Scheduled', 'KPI', 'NCR', 'User'],
            'rows' => $rows,
        ];
    }

    private function incidentBaseQuery(array $filters): Builder
    {
        return $this->applyIncidentFilters(Incident::query(), $filters)
            ->select([
                'id',
                'reported_by',
                'title',
                'location',
                'classification',
                'status',
                'datetime',
                'created_at',
            ])
            ->with(['reporter:id,name']);
    }

    private function trainingBaseQuery(array $filters): Builder
    {
        return $this->applyTrainingFilters(Training::query(), $filters)
            ->select([
                'id',
                'title',
                'starts_at',
                'ends_at',
                'is_active',
                'created_at',
            ])
            ->withCount(['users', 'certificates']);
    }

    private function auditBaseQuery(array $filters): Builder
    {
        return $this->applyAuditFilters(SiteAudit::query(), $filters)
            ->select([
                'id',
                'reference_no',
                'site_name',
                'created_by',
                'status',
                'scheduled_for',
                'kpi_score',
                'created_at',
            ])
            ->with(['creator:id,name'])
            ->withCount('ncrReports');
    }

    private function applyIncidentFilters(Builder $builder, array $filters): Builder
    {
        return $builder
            ->when($filters['date_from'], fn (Builder $query, string $date) => $query->whereDate('datetime', '>=', $date))
            ->when($filters['date_to'], fn (Builder $query, string $date) => $query->whereDate('datetime', '<=', $date))
            ->when($filters['user_id'], fn (Builder $query, int $userId) => $query->where('reported_by', $userId))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status));
    }

    private function applyTrainingFilters(Builder $builder, array $filters): Builder
    {
        return $builder
            ->when($filters['date_from'], fn (Builder $query, string $date) => $query->whereDate('starts_at', '>=', $date))
            ->when($filters['date_to'], fn (Builder $query, string $date) => $query->whereDate('starts_at', '<=', $date))
            ->when($filters['user_id'], fn (Builder $query, int $userId) => $query->whereHas('users', fn (Builder $related) => $related->where('users.id', $userId)))
            ->when($filters['status'], function (Builder $query, string $status) {
                if ($status === 'active') {
                    $query->where('is_active', true);
                }

                if ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            });
    }

    private function applyAuditFilters(Builder $builder, array $filters): Builder
    {
        return $builder
            ->when($filters['date_from'], fn (Builder $query, string $date) => $query->whereDate('scheduled_for', '>=', $date))
            ->when($filters['date_to'], fn (Builder $query, string $date) => $query->whereDate('scheduled_for', '<=', $date))
            ->when($filters['user_id'], fn (Builder $query, int $userId) => $query->where('created_by', $userId))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status));
    }

    /**
     * @return LengthAwarePaginator|Collection<int, mixed>
     */
    private function resolveRows(Builder $query, bool $paginate, int $perPage, int $limit): LengthAwarePaginator|Collection
    {
        if ($paginate) {
            return $query->paginate($perPage)->withQueryString();
        }

        return $query->limit($limit)->get();
    }

    /**
     * @param  Collection<int, object>  $trendRows
     * @return array<int, array{label: string, count: int}>
     */
    private function fillTrendBuckets(Collection $trendRows, int $days): array
    {
        $bucket = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $bucket[$day] = [
                'label' => now()->subDays($i)->format('M d'),
                'count' => 0,
            ];
        }

        foreach ($trendRows as $row) {
            $dayKey = (string) ($row->day_key ?? '');
            if (! isset($bucket[$dayKey])) {
                continue;
            }

            $bucket[$dayKey]['count'] = (int) ($row->total ?? 0);
        }

        return array_values($bucket);
    }
}
