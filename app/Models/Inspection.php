<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inspection extends Model
{
    use SoftDeletes;

    public const STATUSES = ['draft', 'completed', 'submitted'];

    protected $fillable = [
        'offline_uuid',
        'inspection_checklist_id',
        'inspector_id',
        'status',
        'location',
        'performed_at',
        'submitted_at',
        'notes',
        'device_identifier',
        'offline_reference',
        'sync_status',
        'sync_batch_uuid',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'performed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(InspectionChecklist::class, 'inspection_checklist_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(InspectionResponse::class);
    }

    public function syncJobs(): HasMany
    {
        return $this->hasMany(InspectionSyncJob::class);
    }

    public function syncConflicts(): HasMany
    {
        return $this->hasMany(InspectionSyncConflict::class);
    }
}
