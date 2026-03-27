<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteAuditKpi extends Model
{
    protected $fillable = [
        'site_audit_id',
        'name',
        'target_value',
        'actual_value',
        'unit',
        'weight',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'target_value' => 'float',
            'actual_value' => 'float',
            'weight' => 'integer',
        ];
    }

    public function siteAudit(): BelongsTo
    {
        return $this->belongsTo(SiteAudit::class);
    }
}
