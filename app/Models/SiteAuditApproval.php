<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteAuditApproval extends Model
{
    protected $fillable = [
        'site_audit_id',
        'approver_id',
        'approver_role',
        'decision',
        'remarks',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    public function siteAudit(): BelongsTo
    {
        return $this->belongsTo(SiteAudit::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
