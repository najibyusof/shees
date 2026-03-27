<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class InspectionChecklistItem extends Model
{
    public const ITEM_TYPES = ['boolean', 'text', 'number', 'choice'];

    protected $fillable = [
        'inspection_checklist_id',
        'offline_uuid',
        'label',
        'label_translations',
        'item_type',
        'options',
        'is_required',
        'sort_order',
        'sync_status',
        'sync_batch_uuid',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'label_translations' => 'array',
            'options' => 'array',
            'is_required' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(InspectionChecklist::class, 'inspection_checklist_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(InspectionResponse::class);
    }

    public function labelForLocale(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $translations = $this->label_translations ?? [];

        return $translations[$locale] ?? $this->label;
    }
}
