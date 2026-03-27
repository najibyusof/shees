<?php

namespace App\Listeners;

use App\Events\ApprovalRequired;
use App\Notifications\IncidentWorkflowNotification;

class SendApprovalRequiredNotification
{
    public function handle(ApprovalRequired $event): void
    {
        $event->recipient->notify(new IncidentWorkflowNotification($event->incident, 'approval_pending'));
    }
}
