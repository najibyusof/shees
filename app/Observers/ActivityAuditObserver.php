<?php

namespace App\Observers;

use App\Models\CorrectiveAction;
use App\Models\Inspection;
use App\Models\InspectionChecklist;
use App\Models\Incident;
use App\Models\NcrReport;
use App\Models\ReportExport;
use App\Models\ReportPreset;
use App\Models\SiteAudit;
use App\Models\Training;
use App\Models\Worker;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;

class ActivityAuditObserver
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    public function created(Model $model): void
    {
        $module = $this->resolveModule($model);
        if (! $module) {
            return;
        }

        $this->auditLogService->log(
            auth()->id(),
            'create',
            $module,
            $model
        );
    }

    public function updated(Model $model): void
    {
        $module = $this->resolveModule($model);
        if (! $module || ! $model->wasChanged()) {
            return;
        }

        $this->auditLogService->log(
            auth()->id(),
            'update',
            $module,
            $model,
            [
                'changed_fields' => array_keys($model->getChanges()),
            ]
        );
    }

    public function deleted(Model $model): void
    {
        $module = $this->resolveModule($model);
        if (! $module) {
            return;
        }

        $this->auditLogService->log(
            auth()->id(),
            'delete',
            $module,
            $model
        );
    }

    private function resolveModule(Model $model): ?string
    {
        return match ($model::class) {
            Incident::class => 'incidents',
            Training::class => 'trainings',
            SiteAudit::class,
            NcrReport::class,
            CorrectiveAction::class => 'audits',
            Worker::class => 'workers',
            ReportPreset::class,
            ReportExport::class => 'reports',
            InspectionChecklist::class,
            Inspection::class => 'inspections',
            default => null,
        };
    }
}
