<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentDamage extends IncidentRelatedRecord
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estimate_cost' => 'decimal:2',
        ]);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function damageType(): BelongsTo
    {
        return $this->belongsTo(DamageType::class);
    }
}
