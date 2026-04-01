<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function uiPreferences(): HasMany
    {
        return $this->hasMany(UserUiPreference::class);
    }

    public function trainings(): BelongsToMany
    {
        return $this->belongsToMany(Training::class)
            ->withPivot([
                'assigned_by',
                'assigned_at',
                'completed_at',
                'completion_status',
                'expiry_notified_at',
            ])
            ->withTimestamps();
    }

    public function reportedIncidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'reported_by');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function createdInspectionChecklists(): HasMany
    {
        return $this->hasMany(InspectionChecklist::class, 'created_by');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class, 'inspector_id');
    }

    public function inspectionResponseImages(): HasMany
    {
        return $this->hasMany(InspectionResponseImage::class, 'uploaded_by');
    }

    public function inspectionSyncJobs(): HasMany
    {
        return $this->hasMany(InspectionSyncJob::class);
    }

    public function resolvedInspectionSyncConflicts(): HasMany
    {
        return $this->hasMany(InspectionSyncConflict::class, 'resolved_by');
    }

    public function mobileAccessTokens(): HasMany
    {
        return $this->hasMany(MobileAccessToken::class);
    }

    public function siteAuditsCreated(): HasMany
    {
        return $this->hasMany(SiteAudit::class, 'created_by');
    }

    public function siteAuditsSubmitted(): HasMany
    {
        return $this->hasMany(SiteAudit::class, 'submitted_by');
    }

    public function siteAuditApprovals(): HasMany
    {
        return $this->hasMany(SiteAuditApproval::class, 'approver_id');
    }

    public function ncrReportsCreated(): HasMany
    {
        return $this->hasMany(NcrReport::class, 'reported_by');
    }

    public function correctiveActionsAssigned(): HasMany
    {
        return $this->hasMany(CorrectiveAction::class, 'assigned_to');
    }

    public function workerProfile(): HasMany
    {
        return $this->hasMany(Worker::class);
    }

    public function attendanceLogsRecorded(): HasMany
    {
        return $this->hasMany(AttendanceLog::class, 'recorded_by');
    }

    public function reportPresets(): HasMany
    {
        return $this->hasMany(ReportPreset::class);
    }

    public function reportExports(): HasMany
    {
        return $this->hasMany(ReportExport::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    public function hasPermissionTo(string $permission): bool
    {
        $aliases = $this->permissionAliases($permission);
        $permissions = array_values(array_unique(array_merge([$permission], $aliases)));

        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permissions) {
                $query->whereIn('name', $permissions);
            })
            ->exists();
    }

    /**
     * @return array<int, string>
     */
    private function permissionAliases(string $permission): array
    {
        $aliases = [
            'audits.view' => ['view_audit'],
            'view_audit' => ['audits.view'],
            'view_report' => ['reports.view'],
            'reports.view' => ['view_report'],
            'audits.conduct' => ['create_audit'],
            'create_audit' => ['audits.conduct'],
            'audits.approve' => ['approve_audit'],
            'approve_audit' => ['audits.approve'],
            'audits.ncr.manage' => ['edit_audit'],
            'edit_audit' => ['audits.ncr.manage'],
            'workers.view' => ['view_worker'],
            'view_worker' => ['workers.view'],
            'workers.manage' => ['edit_worker'],
            'edit_worker' => ['workers.manage', 'workers.track'],
            'workers.track' => ['edit_worker'],
            'users.view' => ['view_user_management'],
            'view_user_management' => ['users.view'],
            'users.create' => ['create_user_management'],
            'create_user_management' => ['users.create'],
            'users.update' => ['edit_user_management'],
            'edit_user_management' => ['users.update'],
            'users.delete' => ['delete_user_management'],
            'delete_user_management' => ['users.delete'],
            'incidents.submit' => ['create_incident'],
            'create_incident' => ['incidents.submit', 'submit_incident'],
            'submit_incident' => ['incidents.submit', 'create_incident'],
            'view_incident' => ['incidents.view'],
            'incidents.view' => ['view_incident'],
            'incidents.approve' => ['approve_incident'],
            'approve_incident' => ['incidents.approve', 'approve_final'],
            'approve_final' => ['approve_incident', 'incidents.approve'],
            'incidents.update' => ['edit_incident'],
            'edit_incident' => ['incidents.update'],
            'review_incident' => ['approve_incident'],
            'request_closure' => ['approve_closure'],
            'approve_closure' => ['request_closure'],
            'view_training' => ['training.view'],
            'training.view' => ['view_training'],
            'incidents.comment' => ['create_incident', 'approve_incident'],
        ];

        return $aliases[$permission] ?? [];
    }

    public function isProtectedAccount(): bool
    {
        return $this->email === 'superadmin@shees.local';
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $nestedQuery) use ($search) {
            $nestedQuery->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }

    public function scopeVerificationStatus(Builder $query, ?string $status): Builder
    {
        return match ($status) {
            'verified' => $query->whereNotNull('email_verified_at'),
            'unverified' => $query->whereNull('email_verified_at'),
            default => $query,
        };
    }

    public function scopeRoleIds(Builder $query, array $roleIds): Builder
    {
        $roleIds = array_values(array_filter($roleIds, static fn ($id) => is_numeric($id)));

        if ($roleIds === []) {
            return $query;
        }

        return $query->whereHas('roles', fn (Builder $roleQuery) => $roleQuery->whereIn('roles.id', $roleIds));
    }

    public function scopeDateBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when(
                filled($from),
                fn (Builder $builder) => $builder->whereDate('created_at', '>=', $from)
            )
            ->when(
                filled($to),
                fn (Builder $builder) => $builder->whereDate('created_at', '<=', $to)
            );
    }

    public function scopeSortByField(Builder $query, ?string $sort, ?string $direction): Builder
    {
        $allowedSorts = [
            'name',
            'email',
            'created_at',
        ];

        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'created_at';
        $direction = strtolower((string) $direction) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)->orderBy('id', 'desc');
    }
}
