<?php

namespace App\Notifications;

use App\Models\Incident;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IncidentWorkflowNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly Incident $incident,
        private readonly string $event
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = match ($this->event) {
            'submitted' => 'Incident Submitted for Review',
            'approval_pending' => 'Incident Approval In Progress',
            'approved' => 'Incident Approved',
            'rejected' => 'Incident Rejected',
            default => 'Incident Workflow Update',
        };

        $eventLine = match ($this->event) {
            'submitted' => 'An incident requires your approval review.',
            'approval_pending' => 'An approval was recorded. Additional approval role is still required.',
            'approved' => 'Your incident has been fully approved.',
            'rejected' => 'Your incident has been rejected. Please review the reason and update if needed.',
            default => 'The workflow state has been updated.',
        };

        return (new MailMessage)
            ->subject($subject)
            ->line('Incident: '.$this->incident->title)
            ->line($eventLine)
            ->line('Current status: '.$this->incident->status)
            ->action('Open Incident', route('incidents.show', $this->incident));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'incident_id' => $this->incident->id,
            'incident_title' => $this->incident->title,
            'event' => $this->event,
            'status' => $this->incident->status,
            'url' => route('incidents.show', $this->incident),
        ];
    }
}
