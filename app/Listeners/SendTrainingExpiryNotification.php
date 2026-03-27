<?php

namespace App\Listeners;

use App\Events\TrainingExpiryDetected;
use App\Notifications\TrainingCertificateExpiryNotification;

class SendTrainingExpiryNotification
{
    public function handle(TrainingExpiryDetected $event): void
    {
        $certificate = $event->certificate->loadMissing(['user', 'training']);

        if (! $certificate->user) {
            return;
        }

        $certificate->user->notify(new TrainingCertificateExpiryNotification($certificate));
        $certificate->update(['expiry_notified_at' => now()]);
    }
}
