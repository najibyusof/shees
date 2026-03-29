<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentChronology extends IncidentRelatedRecord
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'event_date' => 'date',
            'event_time' => 'datetime:H:i',
        ]);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }
}
