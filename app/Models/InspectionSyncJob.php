<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class InspectionSyncJob extends Model
{
    public const STATUSES = ['pending', 'applied', 'conflict', 'failed', 'acked'];

    protected $fillable = [
        'inspection_id',
        'user_id',
        'device_identifier',
        'direction',
        'entity_type',
        'entity_offline_uuid',
        'operation',
        'contract_name',
        'contract_version',
        'payload',
        'status',
        'sync_batch_uuid',
        'idempotency_key',
        'error_message',
        'received_at',
        'processing_started_at',
        'processed_at',
        'processing_finished_at',
        'processing_latency_ms',
        'retry_count',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'contract_version' => 'integer',
            'received_at' => 'datetime',
            'processing_started_at' => 'datetime',
            'processed_at' => 'datetime',
            'processing_finished_at' => 'datetime',
            'processing_latency_ms' => 'integer',
            'retry_count' => 'integer',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conflicts(): HasMany
    {
        return $this->hasMany(InspectionSyncConflict::class);
    }
}
