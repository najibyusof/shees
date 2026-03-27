<?php

namespace App\Events;

use App\Models\Incident;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class IncidentSubmitted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Incident $incident,
        public Collection $approvers,
    ) {}
}
