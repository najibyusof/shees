<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class IncidentAttachment extends IncidentRelatedRecord
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'size' => 'integer',
        ]);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function attachmentType(): BelongsTo
    {
        return $this->belongsTo(AttachmentType::class);
    }

    public function attachmentCategory(): BelongsTo
    {
        return $this->belongsTo(AttachmentCategory::class);
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn () => Storage::disk('public')->url($this->path));
    }
}
