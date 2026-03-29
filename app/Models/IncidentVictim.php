<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentVictim extends IncidentRelatedRecord
{
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function victimType(): BelongsTo
    {
        return $this->belongsTo(VictimType::class);
    }
}
