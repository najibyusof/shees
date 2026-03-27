<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrectiveAction extends Model
{
    protected $fillable = [
        'ncr_report_id',
        'assigned_to',
        'verified_by',
        'title',
        'description',
        'status',
        'due_date',
        'completed_at',
        'verified_at',
        'completion_notes',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function ncrReport(): BelongsTo
    {
        return $this->belongsTo(NcrReport::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
