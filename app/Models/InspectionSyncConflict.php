<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class InspectionSyncConflict extends Model
{
    protected $fillable = [
        'inspection_sync_job_id',
        'inspection_id',
        'resolved_by',
        'entity_type',
        'entity_offline_uuid',
        'conflict_type',
        'client_payload',
        'server_payload',
        'resolution_status',
        'resolution_notes',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'client_payload' => 'array',
            'server_payload' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function syncJob(): BelongsTo
    {
        return $this->belongsTo(InspectionSyncJob::class, 'inspection_sync_job_id');
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
