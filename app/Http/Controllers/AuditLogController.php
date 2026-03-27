<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        abort_unless($request->user()?->hasPermissionTo('audits.view'), Response::HTTP_FORBIDDEN);

        $filters = $this->validatedFilters($request);

        $logs = AuditLog::query()
            ->with('user:id,name')
            ->when($filters['action'], function ($query, string $action) {
                $query->where('action', $action);
            })
            ->when($filters['module'], function ($query, string $module) {
                $query->where('module', $module);
            })
            ->when($filters['user_id'], function ($query, int $userId) {
                $query->where('user_id', $userId);
            })
            ->when($filters['from'], function ($query, string $from) {
                $query->whereDate('created_at', '>=', $from);
            })
            ->when($filters['to'], function ($query, string $to) {
                $query->whereDate('created_at', '<=', $to);
            })
            ->latest()
            ->paginate($filters['per_page'])
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Audit logs retrieved successfully.',
                'data' => $logs,
            ]);
        }

        return view('audit-logs.index', [
            'logs' => $logs,
            'filters' => $filters,
            'actions' => ['create', 'update', 'delete', 'approve'],
            'modules' => $this->moduleOptions(),
        ]);
    }

    public function export(Request $request, string $format): StreamedResponse|Response
    {
        abort_unless($request->user()?->hasPermissionTo('audits.view'), Response::HTTP_FORBIDDEN);

        if (! in_array($format, ['csv', 'pdf'], true)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $filters = $this->validatedFilters($request, forExport: true);

        $logs = AuditLog::query()
            ->with('user:id,name')
            ->when($filters['action'], function ($query, string $action) {
                $query->where('action', $action);
            })
            ->when($filters['module'], function ($query, string $module) {
                $query->where('module', $module);
            })
            ->when($filters['user_id'], function ($query, int $userId) {
                $query->where('user_id', $userId);
            })
            ->when($filters['from'], function ($query, string $from) {
                $query->whereDate('created_at', '>=', $from);
            })
            ->when($filters['to'], function ($query, string $to) {
                $query->whereDate('created_at', '<=', $to);
            })
            ->latest()
            ->limit(5000)
            ->get();

        if ($format === 'csv') {
            return $this->exportCsv($logs, $filters);
        }

        return $this->exportPdf($logs, $filters);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, AuditLog>  $logs
     */
    private function exportCsv($logs, array $filters): StreamedResponse
    {
        $filename = 'audit_logs_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['id', 'timestamp', 'user', 'action', 'module', 'auditable_type', 'auditable_id', 'request_id', 'ip_address']);

            foreach ($logs as $log) {
                $metadata = is_array($log->metadata) ? $log->metadata : [];

                fputcsv($handle, [
                    (string) $log->id,
                    (string) $log->created_at,
                    (string) ($log->user?->name ?? 'System'),
                    (string) $log->action,
                    (string) $log->module,
                    (string) ($log->auditable_type ?? ''),
                    (string) ($log->auditable_id ?? ''),
                    (string) ($metadata['request_id'] ?? ''),
                    (string) ($metadata['ip_address'] ?? ''),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, AuditLog>  $logs
     */
    private function exportPdf($logs, array $filters): Response
    {
        $pdf = Pdf::loadView('audit-logs.export-pdf', [
            'logs' => $logs,
            'filters' => $filters,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('audit_logs_'.now()->format('Ymd_His').'.pdf');
    }

    /**
     * @return array{action: string|null, module: string|null, user_id: int|null, from: string|null, to: string|null, per_page: int}
     */
    private function validatedFilters(Request $request, bool $forExport = false): array
    {
        $validated = $request->validate([
            'action' => ['nullable', 'in:create,update,delete,approve'],
            'module' => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        return [
            'action' => $validated['action'] ?? null,
            'module' => isset($validated['module']) && $validated['module'] !== '' ? (string) $validated['module'] : null,
            'user_id' => isset($validated['user_id']) ? (int) $validated['user_id'] : null,
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
            'per_page' => $forExport ? 5000 : (int) ($validated['per_page'] ?? 25),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function moduleOptions(): array
    {
        return AuditLog::query()
            ->select('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module')
            ->values()
            ->all();
    }
}
