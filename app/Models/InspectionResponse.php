<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class InspectionResponse extends Model
{
    protected $fillable = [
        'inspection_id',
        'inspection_checklist_item_id',
        'offline_uuid',
        'response_value',
        'response_meta',
        'is_non_compliant',
        'comment',
        'sync_status',
        'sync_batch_uuid',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'response_meta' => 'array',
            'is_non_compliant' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(InspectionChecklistItem::class, 'inspection_checklist_item_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(InspectionResponseImage::class);
    }
}
