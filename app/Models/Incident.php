<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
// IncidentWorkflowLog is resolved via the HasMany call below

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
        'draft_submitted',
        'draft_reviewed',
        'final_submitted',
        'final_reviewed',
        'pending_closure',
        'closed',
    ];

    /**
     * Ordered workflow steps with display label and responsible role label.
     * Used by views and services — do not reorder.
     */
    public const WORKFLOW_STEPS = [
        'draft'           => ['label' => 'Draft',            'responsible' => 'Creator (PC/SSS)'],
        'draft_submitted' => ['label' => 'Draft Submitted',  'responsible' => 'Manager'],
        'draft_reviewed'  => ['label' => 'Draft Reviewed',   'responsible' => 'HOD HSSE'],
        'final_submitted' => ['label' => 'Final Submitted',  'responsible' => 'HOD HSSE / APSB PD'],
        'final_reviewed'  => ['label' => 'Final Reviewed',   'responsible' => 'MRTS'],
        'pending_closure' => ['label' => 'Pending Closure',  'responsible' => 'HOD HSSE'],
        'closed'          => ['label' => 'Closed',           'responsible' => 'MRTS'],
    ];

    /**
     * Roles that participate in workflow progression (for notifications/filtering).
     */
    public const WORKFLOW_ROLES = [
        'Manager',
        'HOD HSSE',
        'APSB PD',
        'MRTS',
    ];

    protected $fillable = [
        'reported_by',
        'submitted_by',
        'reviewed_by',
        'approved_by',
        'rejected_by',
        'incident_reference_number',
        'title',
        'description',
        'incident_type_id',
        'location',
        'location_type_id',
        'location_id',
        'other_location',
        'datetime',
        'incident_date',
        'incident_time',
        'classification',
        'classification_id',
        'reclassification_id',
        'status',
        'status_id',
        'work_package_id',
        'work_activity_id',
        'incident_description',
        'immediate_response',
        'subcontractor_id',
        'person_in_charge',
        'subcontractor_contact_number',
        'gps_location',
        'activity_during_incident',
        'type_of_accident',
        'basic_effect',
        'conclusion',
        'close_remark',
        'rootcause_id',
        'other_rootcause',
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
            'incident_date'    => 'date',
            'incident_time'    => 'datetime:H:i',
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

    public function incidentType(): BelongsTo
    {
        return $this->belongsTo(IncidentType::class);
    }

    public function incidentStatus(): BelongsTo
    {
        return $this->belongsTo(IncidentStatus::class, 'status_id');
    }

    public function incidentClassification(): BelongsTo
    {
        return $this->belongsTo(IncidentClassification::class, 'classification_id');
    }

    public function reclassification(): BelongsTo
    {
        return $this->belongsTo(IncidentClassification::class, 'reclassification_id');
    }

    public function incidentLocation(): BelongsTo
    {
        return $this->belongsTo(IncidentLocation::class, 'location_id');
    }

    public function locationType(): BelongsTo
    {
        return $this->belongsTo(LocationType::class, 'location_type_id');
    }

    public function workPackage(): BelongsTo
    {
        return $this->belongsTo(WorkPackage::class);
    }

    public function workActivity(): BelongsTo
    {
        return $this->belongsTo(WorkActivity::class, 'work_activity_id');
    }

    public function subcontractor(): BelongsTo
    {
        return $this->belongsTo(Subcontractor::class);
    }

    public function rootCause(): BelongsTo
    {
        return $this->belongsTo(CauseType::class, 'rootcause_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(IncidentAttachment::class);
    }

    public function chronologies(): HasMany
    {
        return $this->hasMany(IncidentChronology::class)->orderBy('event_date')->orderBy('event_time')->orderBy('sort_order');
    }

    public function victims(): HasMany
    {
        return $this->hasMany(IncidentVictim::class);
    }

    public function witnesses(): HasMany
    {
        return $this->hasMany(IncidentWitness::class);
    }

    public function investigationTeamMembers(): HasMany
    {
        return $this->hasMany(IncidentInvestigationTeamMember::class);
    }

    public function damages(): HasMany
    {
        return $this->hasMany(IncidentDamage::class);
    }

    public function immediateActions(): HasMany
    {
        return $this->hasMany(IncidentImmediateAction::class);
    }

    public function plannedActions(): HasMany
    {
        return $this->hasMany(IncidentPlannedAction::class);
    }

    public function workflowLogs(): HasMany
    {
        return $this->hasMany(IncidentWorkflowLog::class)->latest();
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

    public function immediateCauses(): BelongsToMany
    {
        return $this->belongsToMany(CauseType::class, 'incident_immediate_cause')
            ->withTimestamps();
    }

    public function contributingFactors(): BelongsToMany
    {
        return $this->belongsToMany(FactorType::class, 'incident_contributing_factor')
            ->withTimestamps();
    }

    public function workActivities(): BelongsToMany
    {
        return $this->belongsToMany(WorkActivity::class, 'incident_work_activity')
            ->withTimestamps();
    }

    public function externalParties(): BelongsToMany
    {
        return $this->belongsToMany(ExternalParty::class, 'incident_external_party')
            ->withTimestamps();
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $nestedQuery) use ($search) {
            $nestedQuery->where('title', 'like', "%{$search}%")
                ->orWhere('incident_reference_number', 'like', "%{$search}%")
                ->orWhere('incident_description', 'like', "%{$search}%")
                ->orWhereHas('reporter', function (Builder $reporterQuery) use ($search) {
                    $reporterQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
        });
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $query->when(
            filled($status) && in_array($status, static::STATUSES, true),
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
