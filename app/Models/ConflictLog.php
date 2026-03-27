<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConflictLog extends Model
{
    protected $fillable = [
        'user_id',
        'device_id',
        'record_id',
        'module',
        'local_version',
        'server_version',
        'resolution_strategy',
        'winner',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'local_version' => 'datetime',
            'server_version' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
