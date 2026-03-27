<?php

namespace App\Notifications;

use App\Models\AttendanceLog;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkerGeofenceAlertNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly AttendanceLog $attendanceLog) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $worker = $this->attendanceLog->worker;

        return [
            'title' => 'Geofence Alert',
            'worker_id' => $worker?->id,
            'worker_name' => $worker?->full_name,
            'employee_code' => $worker?->employee_code,
            'logged_at' => optional($this->attendanceLog->logged_at)->toIso8601String(),
            'distance_from_geofence_meters' => $this->attendanceLog->distance_from_geofence_meters,
            'alert_level' => $this->attendanceLog->alert_level,
            'message' => $this->attendanceLog->alert_message,
        ];
    }
}
