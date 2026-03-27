<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class InspectionResponseImage extends Model
{
    protected $fillable = [
        'inspection_response_id',
        'uploaded_by',
        'offline_uuid',
        'file_path',
        'original_name',
        'mime_type',
        'size',
        'captured_at',
        'sync_status',
        'sync_batch_uuid',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(InspectionResponse::class, 'inspection_response_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
