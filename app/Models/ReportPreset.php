<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportPreset extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'module',
        'export_format',
        'filters',
        'schedule_enabled',
        'schedule_frequency',
        'schedule_time',
        'next_run_at',
        'last_run_at',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'schedule_enabled' => 'boolean',
            'next_run_at' => 'datetime',
            'last_run_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
