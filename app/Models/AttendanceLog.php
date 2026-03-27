<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceLog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'worker_id',
        'recorded_by',
        'event_type',
        'logged_at',
        'latitude',
        'longitude',
        'accuracy_meters',
        'speed_mps',
        'heading_degrees',
        'source',
        'device_identifier',
        'external_event_id',
        'inside_geofence',
        'distance_from_geofence_meters',
        'alert_level',
        'alert_message',
        'meta',
        'temporary_id',
        'local_created_at',
    ];

    protected function casts(): array
    {
        return [
            'logged_at'                     => 'datetime',
            'local_created_at'              => 'datetime',
            'latitude'                      => 'float',
            'longitude'                     => 'float',
            'accuracy_meters'               => 'float',
            'speed_mps'                     => 'float',
            'heading_degrees'               => 'float',
            'inside_geofence'               => 'boolean',
            'distance_from_geofence_meters' => 'integer',
            'meta'                          => 'array',
        ];
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
