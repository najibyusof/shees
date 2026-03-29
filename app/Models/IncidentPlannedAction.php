<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentPlannedAction extends IncidentRelatedRecord
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'expected_date' => 'date',
            'actual_date' => 'date',
        ]);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }
}
