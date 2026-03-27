<?php

namespace App\Listeners;

use App\Events\IncidentSubmitted;
use App\Notifications\IncidentWorkflowNotification;
use Illuminate\Support\Facades\Notification;

class SendIncidentSubmittedNotification
{
    public function handle(IncidentSubmitted $event): void
    {
        if ($event->approvers->isEmpty()) {
            return;
        }

        Notification::send($event->approvers, new IncidentWorkflowNotification($event->incident, 'submitted'));
    }
}
