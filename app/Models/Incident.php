<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Incident extends Model
{
    use SoftDeletes;

    public const CLASSIFICATIONS = [
        'Minor',
        'Moderate',
        'Major',
        'Critical',
    ];

    public const STATUSES = [
        'draft',
        'submitted',
        'under_review',
        'approved',
        'rejected',
    ];

    public const APPROVAL_REQUIRED_ROLES = [
        'Manager',
        'Safety Officer',
    ];

    protected $fillable = [
        'reported_by',
        'submitted_by',
        'reviewed_by',
        'approved_by',
        'rejected_by',
        'title',
        'description',
        'location',
        'datetime',
        'classification',
        'status',
        'submitted_at',
        'reviewed_at',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'temporary_id',
        'local_created_at',
    ];

    protected function casts(): array
    {
        return [
            'datetime'         => 'datetime',
            'submitted_at'     => 'datetime',
            'reviewed_at'      => 'datetime',
            'approved_at'      => 'datetime',
            'rejected_at'      => 'datetime',
            'local_created_at' => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(IncidentAttachment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(IncidentActivity::class)->latest();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(IncidentComment::class)->latest();
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(IncidentApproval::class)->latest('decided_at');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $nestedQuery) use ($search) {
            $nestedQuery->where('title', 'like', "%{$search}%")
                ->orWhereHas('reporter', function (Builder $reporterQuery) use ($search) {
                    $reporterQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
        });
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $query->when(
            filled($status),
            fn (Builder $builder) => $builder->where('status', $status)
        );
    }

    public function scopeClassification(Builder $query, ?string $classification): Builder
    {
        return $query->when(
            filled($classification),
            fn (Builder $builder) => $builder->where('classification', $classification)
        );
    }

    public function scopeReporter(Builder $query, ?int $reporterId): Builder
    {
        return $query->when(
            filled($reporterId),
            fn (Builder $builder) => $builder->where('reported_by', $reporterId)
        );
    }

    public function scopeReporterRoles(Builder $query, array $roleIds): Builder
    {
        $roleIds = array_values(array_filter($roleIds, static fn ($id) => is_numeric($id)));

        if ($roleIds === []) {
            return $query;
        }

        return $query->whereHas('reporter.roles', fn (Builder $roleQuery) => $roleQuery->whereIn('roles.id', $roleIds));
    }

    public function scopeDateBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when(
                filled($from),
                fn (Builder $builder) => $builder->whereDate('datetime', '>=', $from)
            )
            ->when(
                filled($to),
                fn (Builder $builder) => $builder->whereDate('datetime', '<=', $to)
            );
    }

    public function scopeSortByField(Builder $query, ?string $sort, ?string $direction): Builder
    {
        $allowedSorts = [
            'title',
            'status',
            'classification',
            'datetime',
            'created_at',
        ];

        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'datetime';
        $direction = strtolower((string) $direction) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)->orderBy('id', 'desc');
    }
}
