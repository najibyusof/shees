<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentLocation extends IncidentLookup
{
    public function locationType(): BelongsTo
    {
        return $this->belongsTo(LocationType::class);
    }
}
