<?php

namespace App\Jobs;

use App\Models\ReportExport;
use App\Notifications\ReportExportReadyNotification;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateReportExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly int $reportExportId)
    {
    }

    public function handle(ReportService $reportService): void
    {
        $reportExport = ReportExport::query()->with('user')->find($this->reportExportId);

        if (! $reportExport || ! $reportExport->user) {
            return;
        }

        $reportExport->update([
            'status' => 'processing',
            'error_message' => null,
        ]);

        try {
            $report = $reportService->build(
                $reportExport->module,
                $reportExport->filters ?? [],
                paginate: false,
                perPage: 20,
                limit: 10000
            );

            $rows = $report['rows'];
            $mappedRows = $reportService->mapRows($reportExport->module, $rows);

            $extension = $reportExport->format === 'pdf' ? 'pdf' : 'csv';
            $filename = 'report_export_'.$reportExport->id.'_'.now()->format('Ymd_His').'.'.$extension;
            $filePath = 'reports/exports/user_'.$reportExport->user_id.'/'.$filename;

            if ($reportExport->format === 'csv') {
                $stream = fopen('php://temp', 'r+');
                if ($stream === false) {
                    throw new \RuntimeException('Unable to create temporary stream for CSV export.');
                }

                fputcsv($stream, $report['columns']);
                foreach ($mappedRows as $row) {
                    fputcsv($stream, $row);
                }

                rewind($stream);
                $csvContent = stream_get_contents($stream) ?: '';
                fclose($stream);

                Storage::disk('local')->put($filePath, $csvContent);
            } else {
                $pdf = Pdf::loadView('reports.export-pdf', [
                    'title' => $report['module_label'].' Report',
                    'filters' => $reportExport->filters ?? [],
                    'columns' => $report['columns'],
                    'mappedRows' => $mappedRows,
                    'generatedAt' => now(),
                ])->setPaper('a4', 'landscape');

                Storage::disk('local')->put($filePath, $pdf->output());
            }

            $reportExport->update([
                'status' => 'completed',
                'file_path' => $filePath,
                'completed_at' => now(),
            ]);

            $reportExport->user->notify(new ReportExportReadyNotification($reportExport->fresh()));
        } catch (\Throwable $e) {
            $reportExport->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            $reportExport->user->notify(new ReportExportReadyNotification($reportExport->fresh()));
        }
    }
}
