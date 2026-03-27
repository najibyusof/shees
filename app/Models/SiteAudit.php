<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SiteAudit extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'draft',
        'scheduled',
        'in_progress',
        'submitted',
        'under_review',
        'approved',
        'rejected',
        'closed',
    ];

    public const APPROVAL_REQUIRED_ROLES = [
        'Manager',
        'Safety Officer',
    ];

    protected $fillable = [
        'created_by',
        'submitted_by',
        'reviewed_by',
        'approved_by',
        'rejected_by',
        'reference_no',
        'site_name',
        'area',
        'audit_type',
        'scheduled_for',
        'conducted_at',
        'status',
        'kpi_score',
        'scope',
        'summary',
        'rejection_reason',
        'submitted_at',
        'reviewed_at',
        'approved_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'date',
            'conducted_at' => 'datetime',
            'kpi_score' => 'float',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(SiteAuditApproval::class)->latest('decided_at');
    }

    public function kpis(): HasMany
    {
        return $this->hasMany(SiteAuditKpi::class);
    }

    public function ncrReports(): HasMany
    {
        return $this->hasMany(NcrReport::class)->latest();
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $nestedQuery) use ($search) {
            $nestedQuery->where('reference_no', 'like', "%{$search}%")
                ->orWhere('site_name', 'like', "%{$search}%")
                ->orWhere('summary', 'like', "%{$search}%");
        });
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $query->when(
            filled($status),
            fn (Builder $builder) => $builder->where('status', $status)
        );
    }

    public function scopeAuditType(Builder $query, ?string $auditType): Builder
    {
        return $query->when(
            filled($auditType),
            fn (Builder $builder) => $builder->where('audit_type', $auditType)
        );
    }

    public function scopeCreator(Builder $query, ?int $creatorId): Builder
    {
        return $query->when(
            filled($creatorId),
            fn (Builder $builder) => $builder->where('created_by', $creatorId)
        );
    }

    public function scopeCreatorRoles(Builder $query, array $roleIds): Builder
    {
        $roleIds = array_values(array_filter($roleIds, static fn ($id) => is_numeric($id)));

        if ($roleIds === []) {
            return $query;
        }

        return $query->whereHas('creator.roles', fn (Builder $roleQuery) => $roleQuery->whereIn('roles.id', $roleIds));
    }

    public function scopeDateBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when(
                filled($from),
                fn (Builder $builder) => $builder->whereDate('scheduled_for', '>=', $from)
            )
            ->when(
                filled($to),
                fn (Builder $builder) => $builder->whereDate('scheduled_for', '<=', $to)
            );
    }

    public function scopeSortByField(Builder $query, ?string $sort, ?string $direction): Builder
    {
        $allowedSorts = [
            'reference_no',
            'site_name',
            'scheduled_for',
            'kpi_score',
            'status',
            'created_at',
        ];

        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'created_at';
        $direction = strtolower((string) $direction) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)->orderBy('id', 'desc');
    }
}
