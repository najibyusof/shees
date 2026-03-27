<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NcrReport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'site_audit_id',
        'reported_by',
        'owner_id',
        'verified_by',
        'reference_no',
        'title',
        'description',
        'severity',
        'status',
        'root_cause',
        'containment_action',
        'corrective_action_plan',
        'due_date',
        'verified_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'verified_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function siteAudit(): BelongsTo
    {
        return $this->belongsTo(SiteAudit::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function correctiveActions(): HasMany
    {
        return $this->hasMany(CorrectiveAction::class)->latest();
    }
}
