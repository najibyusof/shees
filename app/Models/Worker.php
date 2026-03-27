<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Worker extends Model
{
    use SoftDeletes;

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
}
