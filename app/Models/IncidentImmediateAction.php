<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentImmediateAction extends IncidentRelatedRecord
{
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }
}
