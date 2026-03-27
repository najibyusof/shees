<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class InspectionChecklist extends Model
{
    public const SYNC_STATUSES = ['synced', 'pending_sync', 'conflict'];

    protected $fillable = [
        'offline_uuid',
        'code',
        'title',
        'description',
        'title_translations',
        'description_translations',
        'version',
        'is_active',
        'sync_status',
        'sync_batch_uuid',
        'last_synced_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'title_translations' => 'array',
            'description_translations' => 'array',
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(InspectionChecklistItem::class)->orderBy('sort_order');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function titleForLocale(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $translations = $this->title_translations ?? [];

        return $translations[$locale] ?? $this->title;
    }
}
