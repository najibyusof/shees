<?php

namespace App\Notifications;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrainingCertificateExpiryNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private readonly Certificate $certificate) {}

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
        $trainingTitle = $this->certificate->training?->titleForLocale() ?? 'Training';

        return (new MailMessage)
            ->subject('Certificate Expiry Reminder')
            ->line('Your certificate for '.$trainingTitle.' is nearing expiry.')
            ->line('Expiry date: '.optional($this->certificate->expires_at)->format('Y-m-d'))
            ->action('View Training', route('trainings.show', $this->certificate->training_id));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'training_id' => $this->certificate->training_id,
            'training_title' => $this->certificate->training?->title,
            'certificate_id' => $this->certificate->id,
            'expires_at' => optional($this->certificate->expires_at)->format('Y-m-d'),
            'url' => route('trainings.show', $this->certificate->training_id),
        ];
    }
}
