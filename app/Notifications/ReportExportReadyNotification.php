<?php

namespace App\Notifications;

use App\Models\ReportExport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportExportReadyNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ReportExport $reportExport)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isSuccess = $this->reportExport->status === 'completed';

        $message = (new MailMessage)
            ->subject($isSuccess ? 'Report Export Ready' : 'Report Export Failed')
            ->line('Module: '.ucfirst($this->reportExport->module))
            ->line('Format: '.strtoupper($this->reportExport->format));

        if ($isSuccess) {
            $message->line('Your report export is ready for download.')
                ->action('Download Export', route('reports.exports.download', $this->reportExport));
        } else {
            $message->line('The export failed. Please try again with narrower filters.');
        }

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->reportExport->status === 'completed' ? 'Report Export Ready' : 'Report Export Failed',
            'module' => $this->reportExport->module,
            'format' => $this->reportExport->format,
            'status' => $this->reportExport->status,
            'report_export_id' => $this->reportExport->id,
            'url' => $this->reportExport->status === 'completed'
                ? route('reports.exports.download', $this->reportExport)
                : route('reports.index'),
            'error' => $this->reportExport->error_message,
        ];
    }
}
