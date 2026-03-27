<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateReportExportJob;
use App\Models\ReportExport;
use App\Models\ReportPreset;
use App\Models\User;
use App\Services\ReportService;
use App\Support\AdhocReportBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly AdhocReportBuilder $adhocReportBuilder,
    )
    {
    }

    public function builder(Request $request): View
    {
        abort_unless($request->user()?->hasPermissionTo('reports.view'), Response::HTTP_FORBIDDEN);

        $allowedModules = $this->adhocReportBuilder->allowedModulesForUser($request->user());
        abort_if($allowedModules === [], Response::HTTP_FORBIDDEN);

        $validated = $request->validate([
            'module' => ['nullable', 'string'],
            'fields' => ['nullable', 'array'],
            'fields.*' => ['string', 'max:100'],
            'filters' => ['nullable', 'array'],
            'filters.*.field' => ['nullable', 'string', 'max:100'],
            'filters.*.operator' => ['nullable', 'string', 'in:=,!=,like,>,<,between'],
            'filters.*.value' => ['nullable', 'string', 'max:250'],
            'filters.*.value_to' => ['nullable', 'string', 'max:250'],
            'filters.*.boolean' => ['nullable', 'string', 'in:and,or'],
            'sort_field' => ['nullable', 'string', 'max:100'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
            'preview' => ['nullable', 'boolean'],
        ]);

        $module = (string) ($validated['module'] ?? array_key_first($allowedModules));
        if (! isset($allowedModules[$module])) {
            $module = array_key_first($allowedModules);
        }

        $definition = $this->adhocReportBuilder->moduleDefinition($module);
        $fieldDefinitions = $definition['fields'];

        $selectedFields = array_values(array_filter(
            array_unique($validated['fields'] ?? []),
            fn ($field) => isset($fieldDefinitions[$field])
        ));

        if ($selectedFields === []) {
            $selectedFields = array_values(array_slice(array_keys($fieldDefinitions), 0, 5));
        }

        $safeFilters = $this->adhocReportBuilder->sanitizeFilters($validated['filters'] ?? [], $fieldDefinitions);

        $sortField = (string) ($validated['sort_field'] ?? '');
        $sortField = isset($fieldDefinitions[$sortField]) ? $sortField : $selectedFields[0];
        $sortDirection = (string) ($validated['sort_direction'] ?? 'desc');
        $perPage = (int) ($validated['per_page'] ?? 25);

        $query = $this->adhocReportBuilder->buildQuery(
            $module,
            $selectedFields,
            $safeFilters,
            $sortField,
            $sortDirection,
        );

        /** @var LengthAwarePaginator $previewRows */
        $previewRows = $query->paginate($perPage)->withQueryString();

        $columnLabels = $this->adhocReportBuilder->columnLabels($module, $selectedFields);

        return view('reports.report-builder', [
            'allowedModules' => $allowedModules,
            'module' => $module,
            'fieldsByModule' => collect($this->adhocReportBuilder->moduleDefinitions())->map(fn ($config) => $config['fields'])->all(),
            'selectedFields' => $selectedFields,
            'filters' => $safeFilters,
            'sortField' => $sortField,
            'sortDirection' => $sortDirection,
            'perPage' => $perPage,
            'previewRows' => $previewRows,
            'columnLabels' => $columnLabels,
            'previewEnabled' => $request->boolean('preview'),
        ]);
    }

    public function exportBuilder(Request $request, string $format)
    {
        abort_unless($request->user()?->hasPermissionTo('reports.view'), Response::HTTP_FORBIDDEN);

        if (! in_array($format, ['csv', 'pdf'], true)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $allowedModules = $this->adhocReportBuilder->allowedModulesForUser($request->user());
        abort_if($allowedModules === [], Response::HTTP_FORBIDDEN);

        $validated = $request->validate([
            'module' => ['required', 'string'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => ['string', 'max:100'],
            'filters' => ['nullable', 'array'],
            'filters.*.field' => ['nullable', 'string', 'max:100'],
            'filters.*.operator' => ['nullable', 'string', 'in:=,!=,like,>,<,between'],
            'filters.*.value' => ['nullable', 'string', 'max:250'],
            'filters.*.value_to' => ['nullable', 'string', 'max:250'],
            'filters.*.boolean' => ['nullable', 'string', 'in:and,or'],
            'sort_field' => ['nullable', 'string', 'max:100'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ]);

        $module = (string) $validated['module'];
        abort_unless(isset($allowedModules[$module]), Response::HTTP_FORBIDDEN);

        $definition = $this->adhocReportBuilder->moduleDefinition($module);
        $fieldDefinitions = $definition['fields'];
        $selectedFields = array_values(array_filter(array_unique($validated['fields']), fn ($field) => isset($fieldDefinitions[$field])));
        abort_if($selectedFields === [], Response::HTTP_UNPROCESSABLE_ENTITY, 'Select at least one valid field.');

        $safeFilters = $this->adhocReportBuilder->sanitizeFilters($validated['filters'] ?? [], $fieldDefinitions);
        $sortField = (string) ($validated['sort_field'] ?? $selectedFields[0]);
        if (! isset($fieldDefinitions[$sortField])) {
            $sortField = $selectedFields[0];
        }

        $sortDirection = (string) ($validated['sort_direction'] ?? 'desc');

        $rows = $this->adhocReportBuilder
            ->buildQuery($module, $selectedFields, $safeFilters, $sortField, $sortDirection)
            ->limit(5000)
            ->get();

        $mappedRows = $this->adhocReportBuilder->mapRows($module, $rows, $selectedFields);
        $columns = $this->adhocReportBuilder->columnLabels($module, $selectedFields);

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($columns, $mappedRows) {
                $handle = fopen('php://output', 'w');
                if ($handle === false) {
                    return;
                }

                fputcsv($handle, $columns);
                foreach ($mappedRows as $mappedRow) {
                    fputcsv($handle, $mappedRow);
                }

                fclose($handle);
            }, 'adhoc_'.Str::slug($module, '_').'_'.now()->format('Ymd_His').'.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        $pdf = Pdf::loadView('reports.export-pdf', [
            'title' => ucfirst($module).' Adhoc Report',
            'filters' => [
                'module' => $module,
                'date_from' => null,
                'date_to' => null,
                'user_id' => null,
                'status' => null,
            ],
            'columns' => $columns,
            'mappedRows' => $mappedRows,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('adhoc_'.Str::slug($module, '_').'_'.now()->format('Ymd_His').'.pdf');
    }

    public function storeBuilderPreset(Request $request)
    {
        abort_unless($request->user()?->hasPermissionTo('reports.view'), Response::HTTP_FORBIDDEN);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'module' => ['required', 'string'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => ['string', 'max:100'],
            'filters' => ['nullable', 'array'],
            'sort_field' => ['nullable', 'string', 'max:100'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);

        $allowedModules = $this->adhocReportBuilder->allowedModulesForUser($request->user());
        $module = (string) $validated['module'];
        abort_unless(isset($allowedModules[$module]), Response::HTTP_FORBIDDEN);

        $fieldDefinitions = $this->adhocReportBuilder->moduleDefinition($module)['fields'];
        $fields = array_values(array_filter(array_unique($validated['fields']), fn ($field) => isset($fieldDefinitions[$field])));
        abort_if($fields === [], Response::HTTP_UNPROCESSABLE_ENTITY, 'No valid fields selected.');

        $filters = $this->adhocReportBuilder->sanitizeFilters($validated['filters'] ?? [], $fieldDefinitions);
        $sortField = (string) ($validated['sort_field'] ?? $fields[0]);
        if (! isset($fieldDefinitions[$sortField])) {
            $sortField = $fields[0];
        }

        ReportPreset::query()->create([
            'user_id' => $request->user()->id,
            'name' => (string) $validated['name'],
            'module' => $module,
            'export_format' => 'csv',
            'filters' => [
                'module' => $module,
                'fields' => $fields,
                'filters' => $filters,
                'sort_field' => $sortField,
                'sort_direction' => (string) ($validated['sort_direction'] ?? 'desc'),
                'per_page' => (int) ($validated['per_page'] ?? 25),
                'type' => 'adhoc_builder',
            ],
            'schedule_enabled' => false,
        ]);

        return redirect()->route('reports.builder', [
            'module' => $module,
            'fields' => $fields,
            'filters' => $filters,
            'sort_field' => $sortField,
            'sort_direction' => (string) ($validated['sort_direction'] ?? 'desc'),
            'per_page' => (int) ($validated['per_page'] ?? 25),
            'preview' => 1,
        ])->with('toast', [
            'type' => 'success',
            'title' => 'Builder Preset Saved',
            'message' => 'Your adhoc report configuration has been saved.',
        ]);
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()?->hasPermissionTo('reports.view'), Response::HTTP_FORBIDDEN);

        $filters = $this->validatedFilters($request);
        $report = $this->reportService->build($filters['module'], $filters, paginate: true);
        $summary = $this->reportService->summary($filters['module'], $filters);

        $users = User::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        $presets = ReportPreset::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        $recentExports = ReportExport::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->limit(10)
            ->get();

        return view('reports.index', [
            'filters' => $filters,
            'users' => $users,
            'rows' => $report['rows'],
            'statusOptions' => $report['status_options'],
            'moduleLabel' => $report['module_label'],
            'summary' => $summary,
            'presets' => $presets,
            'recentExports' => $recentExports,
        ]);
    }

    public function export(Request $request, string $format)
    {
        abort_unless($request->user()?->hasPermissionTo('reports.view'), Response::HTTP_FORBIDDEN);

        if (! in_array($format, ['csv', 'pdf'], true)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $filters = $this->validatedFilters($request);
        $module = $filters['module'];
        $isAsync = $request->boolean('async');
        $estimatedRows = $this->reportService->estimateCount($module, $filters);

        if ($isAsync || $estimatedRows > 1000) {
            $reportExport = ReportExport::query()->create([
                'user_id' => $request->user()->id,
                'module' => $module,
                'format' => $format,
                'filters' => $filters,
                'status' => 'queued',
            ]);

            GenerateReportExportJob::dispatch($reportExport->id);

            return redirect()->route('reports.index', $filters)->with('toast', [
                'type' => 'success',
                'title' => 'Export Queued',
                'message' => 'The export is being generated. You will be notified once it is ready.',
            ]);
        }

        $report = $this->reportService->build($module, $filters, paginate: false, limit: 5000);
        $rows = $report['rows'] instanceof Collection ? $report['rows'] : collect($report['rows']->items());
        $mappedRows = $this->reportService->mapRows($module, $rows);

        if ($format === 'csv') {
            return $this->csvExport($report, $mappedRows, $module);
        }

        return $this->pdfExport($report, $mappedRows, $filters, $module);
    }

    public function storePreset(Request $request)
    {
        abort_unless($request->user()?->hasPermissionTo('reports.view'), Response::HTTP_FORBIDDEN);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'module' => ['nullable', 'in:incidents,trainings,audits'],
            'export_format' => ['nullable', 'in:csv,pdf'],
            'schedule_enabled' => ['nullable', 'boolean'],
            'schedule_frequency' => ['nullable', 'in:daily,weekly'],
            'schedule_time' => ['nullable', 'date_format:H:i'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $filters = [
            'module' => (string) ($validated['module'] ?? 'incidents'),
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'user_id' => isset($validated['user_id']) ? (int) $validated['user_id'] : null,
            'status' => isset($validated['status']) && $validated['status'] !== '' ? (string) $validated['status'] : null,
        ];

        ReportPreset::query()->create([
            'user_id' => $request->user()->id,
            'name' => (string) $validated['name'],
            'module' => $filters['module'],
            'export_format' => (string) ($validated['export_format'] ?? 'csv'),
            'filters' => $filters,
            'schedule_enabled' => (bool) ($validated['schedule_enabled'] ?? false),
            'schedule_frequency' => ($validated['schedule_enabled'] ?? false) ? ($validated['schedule_frequency'] ?? 'daily') : null,
            'schedule_time' => ($validated['schedule_enabled'] ?? false) ? ($validated['schedule_time'] ?? '07:00') : null,
            'next_run_at' => ($validated['schedule_enabled'] ?? false)
                ? now()->setTimeFromTimeString($validated['schedule_time'] ?? '07:00')
                : null,
        ]);

        return redirect()->route('reports.index', $filters)->with('toast', [
            'type' => 'success',
            'title' => 'Preset Saved',
            'message' => 'Report preset saved for quick reuse.',
        ]);
    }

    public function destroyPreset(Request $request, ReportPreset $reportPreset)
    {
        abort_unless($request->user()?->hasPermissionTo('reports.view'), Response::HTTP_FORBIDDEN);
        abort_unless($reportPreset->user_id === $request->user()->id, Response::HTTP_FORBIDDEN);

        $reportPreset->delete();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Preset Removed',
            'message' => 'Report preset deleted.',
        ]);
    }

    public function runPreset(Request $request, ReportPreset $reportPreset)
    {
        abort_unless($request->user()?->hasPermissionTo('reports.view'), Response::HTTP_FORBIDDEN);
        abort_unless($reportPreset->user_id === $request->user()->id, Response::HTTP_FORBIDDEN);

        $reportExport = ReportExport::query()->create([
            'user_id' => $request->user()->id,
            'module' => $reportPreset->module,
            'format' => $reportPreset->export_format ?: 'csv',
            'filters' => $reportPreset->filters,
            'status' => 'queued',
        ]);

        GenerateReportExportJob::dispatch($reportExport->id);

        $reportPreset->update([
            'last_run_at' => now(),
        ]);

        return redirect()->route('reports.index', $reportPreset->filters ?? [])->with('toast', [
            'type' => 'success',
            'title' => 'Preset Run Queued',
            'message' => 'Preset export was queued successfully.',
        ]);
    }

    public function downloadExport(Request $request, ReportExport $reportExport)
    {
        abort_unless($request->user()?->hasPermissionTo('reports.view'), Response::HTTP_FORBIDDEN);
        abort_unless($reportExport->user_id === $request->user()->id, Response::HTTP_FORBIDDEN);

        if ($reportExport->status !== 'completed' || ! $reportExport->file_path || ! Storage::disk('local')->exists($reportExport->file_path)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return Storage::disk('local')->download(
            $reportExport->file_path,
            basename($reportExport->file_path)
        );
    }

    /**
     * @param  array<string, mixed>  $report
     * @param  array<int, array<int, string>>  $mappedRows
     */
    private function csvExport(array $report, array $mappedRows, string $module): StreamedResponse
    {
        $filename = $this->exportFilename($module, 'csv');

        return response()->streamDownload(function () use ($report, $mappedRows) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, $report['columns']);
            foreach ($mappedRows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<string, mixed>  $report
     * @param  array<int, array<int, string>>  $mappedRows
     */
    private function pdfExport(array $report, array $mappedRows, array $filters, string $module)
    {
        $pdf = Pdf::loadView('reports.export-pdf', [
            'title' => $report['module_label'].' Report',
            'filters' => $filters,
            'columns' => $report['columns'],
            'mappedRows' => $mappedRows,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($this->exportFilename($module, 'pdf'));
    }

    private function exportFilename(string $module, string $extension): string
    {
        return Str::of($module)->slug('_')->append('_report_', now()->format('Ymd_His'), '.', $extension)->toString();
    }

    /**
     * @return array{module: string, date_from: string|null, date_to: string|null, user_id: int|null, status: string|null}
     */
    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'module' => ['nullable', 'in:incidents,trainings,audits'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        return [
            'module' => (string) ($validated['module'] ?? 'incidents'),
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'user_id' => isset($validated['user_id']) ? (int) $validated['user_id'] : null,
            'status' => isset($validated['status']) && $validated['status'] !== '' ? (string) $validated['status'] : null,
        ];
    }
}
