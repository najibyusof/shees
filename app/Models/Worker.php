<?php

namespace App\Models;

use App\Traits\HasResourceScoping;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Worker extends Model
{
    use HasFactory, SoftDeletes, HasResourceScoping;

    protected $fillable = [
        'user_id',
        'employee_code',
        'full_name',
        'phone',
        'department',
        'position',
        'status',
        'geofence_center_latitude',
        'geofence_center_longitude',
        'geofence_radius_meters',
        'last_latitude',
        'last_longitude',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'geofence_center_latitude' => 'float',
            'geofence_center_longitude' => 'float',
            'last_latitude' => 'float',
            'last_longitude' => 'float',
            'geofence_radius_meters' => 'integer',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class)->latest('logged_at');
    }

    /**
     * Scope query to workers accessible by the given user.
     *
     * Access rules:
     * - Admin, Manager, Supervisor: all workers
     * - Worker: only self
     */
    public function scopeAccessibleTo(\Illuminate\Database\Eloquent\Builder $query, User $user): \Illuminate\Database\Eloquent\Builder
    {
        if ($user->hasAnyRole(['Admin', 'Manager', 'Supervisor'])) {
            return $query;
        }

        // Workers see only themselves
        return $query->where('user_id', $user->id);
    }
}
