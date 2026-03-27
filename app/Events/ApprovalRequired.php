<?php

namespace App\Events;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApprovalRequired
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Incident $incident,
        public User $recipient,
    ) {}
}
