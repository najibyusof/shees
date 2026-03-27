<?php

namespace App\Events;

use App\Models\Certificate;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrainingExpiryDetected
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Certificate $certificate)
    {
    }
}
