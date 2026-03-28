<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\InspectionChecklistController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\NcrReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SiteAuditController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkerTrackingPageController;
use App\Http\Controllers\WorkerTrackingController;
use App\Models\CorrectiveAction;
use App\Models\Incident;
use App\Models\SiteAudit;
use App\Models\Worker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', function () {
    $landingMetrics = Cache::remember('landing.metrics', now()->addMinutes(5), function () {
        $defaults = [
            'stats' => [
                ['label' => 'Open Incidents', 'value' => 0],
                ['label' => 'Training Completion', 'value' => 0, 'suffix' => '%'],
                ['label' => 'Active Workers', 'value' => 0],
                ['label' => 'Active Sites', 'value' => 0],
            ],
            'today_summary' => 'Live operational metrics refresh automatically as your SHEES data grows.',
            'last_updated_label' => now()->format('M d, Y H:i'),
            'last_updated_at' => now()->toIso8601String(),
        ];

        if (! Schema::hasTable('incidents') || ! Schema::hasTable('workers') || ! Schema::hasTable('site_audits') || ! Schema::hasTable('training_user')) {
            return $defaults;
        }

        $openIncidents = Incident::query()
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereRaw('LOWER(status) NOT IN (?, ?, ?)', ['approved', 'rejected', 'closed']);
            })
            ->count();

        $activeWorkers = Worker::query()
            ->whereRaw('LOWER(status) = ?', ['active'])
            ->count();

        $activeSites = SiteAudit::query()
            ->whereIn('status', ['scheduled', 'in_progress', 'submitted', 'under_review'])
            ->distinct('site_name')
            ->count('site_name');

        if ($activeSites === 0) {
            $activeSites = SiteAudit::query()->distinct('site_name')->count('site_name');
        }

        $totalAssignments = (int) DB::table('training_user')->count();
        $completedAssignments = (int) DB::table('training_user')
            ->where(function ($query) {
                $query->whereNotNull('completed_at')
                    ->orWhereRaw('LOWER(completion_status) = ?', ['completed']);
            })
            ->count();

        $trainingCompletion = $totalAssignments > 0
            ? (int) round(($completedAssignments / $totalAssignments) * 100)
            : 0;

        $incidentsToday = Incident::query()->whereDate('created_at', today())->count();
        $actionsCompletedToday = Schema::hasTable('corrective_actions')
            ? CorrectiveAction::query()->whereDate('completed_at', today())->count()
            : 0;

        return [
            'stats' => [
                ['label' => 'Open Incidents', 'value' => $openIncidents],
                ['label' => 'Training Completion', 'value' => $trainingCompletion, 'suffix' => '%'],
                ['label' => 'Active Workers', 'value' => $activeWorkers],
                ['label' => 'Active Sites', 'value' => $activeSites],
            ],
            'today_summary' => "{$incidentsToday} new incidents logged today and {$actionsCompletedToday} corrective actions completed.",
            'last_updated_label' => now()->format('M d, Y H:i'),
            'last_updated_at' => now()->toIso8601String(),
        ];
    });

    return view('welcome', [
        'landingMetrics' => $landingMetrics,
    ]);
})->name('landing');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('/admin/users')->name('admin.users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('');
        Route::post('/bulk-action', [UserController::class, 'bulkAction'])->name('.bulk-action');
        Route::get('/create', [UserController::class, 'create'])->name('.create');
        Route::post('/', [UserController::class, 'store'])->name('.store');
        Route::get('/{user}', [UserController::class, 'show'])->name('.show');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('.edit');
        Route::match(['put', 'patch'], '/{user}', [UserController::class, 'update'])->name('.update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('.destroy');
    });

    Route::prefix('/admin/roles')->name('admin.roles')->middleware('role:Admin')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('');
        Route::get('/create', [RoleController::class, 'create'])->name('.create');
        Route::post('/', [RoleController::class, 'store'])->name('.store');
        Route::get('/{role}', [RoleController::class, 'show'])->name('.show');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('.edit');
        Route::match(['put', 'patch'], '/{role}', [RoleController::class, 'update'])->name('.update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('.destroy');
        Route::post('/{role}/clone', [RoleController::class, 'clone'])->name('.clone');
        Route::get('/{role}/export/{format}', [RoleController::class, 'export'])->whereIn('format', ['csv', 'pdf'])->name('.export');
    });

    Route::get('/audit/logs', [AuditLogController::class, 'index'])
        ->middleware(['permission:audits.view'])
        ->name('audit.logs');
    Route::get('/audit/logs/export/{format}', [AuditLogController::class, 'export'])
        ->middleware(['permission:audits.view'])
        ->whereIn('format', ['csv', 'pdf'])
        ->name('audit.logs.export');

    Route::post('/incidents/bulk-action', [IncidentController::class, 'bulkAction'])
        ->name('incidents.bulk-action');

    Route::resource('incidents', IncidentController::class)
        ->only(['index', 'show', 'create', 'store', 'edit', 'update']);

    Route::post('/incidents/{incident}/submit', [IncidentController::class, 'submit'])
        ->name('incidents.submit');
    Route::post('/incidents/{incident}/approve', [IncidentController::class, 'approve'])
        ->name('incidents.approve');
    Route::post('/incidents/{incident}/reject', [IncidentController::class, 'reject'])
        ->name('incidents.reject');
    Route::post('/incidents/{incident}/comment', [IncidentController::class, 'comment'])
        ->name('incidents.comment');

    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])
        ->name('notifications.read-all');

    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware(['permission:reports.view'])
        ->name('reports.index');
    Route::get('/reports/builder', [ReportController::class, 'builder'])
        ->middleware(['permission:reports.view'])
        ->name('reports.builder');
    Route::get('/reports/builder/export/{format}', [ReportController::class, 'exportBuilder'])
        ->middleware(['permission:reports.view'])
        ->whereIn('format', ['csv', 'pdf'])
        ->name('reports.builder.export');
    Route::post('/reports/builder/presets', [ReportController::class, 'storeBuilderPreset'])
        ->middleware(['permission:reports.view'])
        ->name('reports.builder.presets.store');
    Route::get('/reports/export/{format}', [ReportController::class, 'export'])
        ->middleware(['permission:reports.view'])
        ->whereIn('format', ['csv', 'pdf'])
        ->name('reports.export');
    Route::post('/reports/presets', [ReportController::class, 'storePreset'])
        ->middleware(['permission:reports.view'])
        ->name('reports.presets.store');
    Route::delete('/reports/presets/{reportPreset}', [ReportController::class, 'destroyPreset'])
        ->middleware(['permission:reports.view'])
        ->name('reports.presets.destroy');
    Route::post('/reports/presets/{reportPreset}/run', [ReportController::class, 'runPreset'])
        ->middleware(['permission:reports.view'])
        ->name('reports.presets.run');
    Route::get('/reports/exports/{reportExport}/download', [ReportController::class, 'downloadExport'])
        ->middleware(['permission:reports.view'])
        ->name('reports.exports.download');

    Route::resource('trainings', TrainingController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update']);

    Route::post('/trainings/bulk-action', [TrainingController::class, 'bulkAction'])
        ->name('trainings.bulk-action');

    Route::post('/trainings/{training}/assign-users', [TrainingController::class, 'assignUsers'])
        ->name('trainings.assign-users');
    Route::post('/trainings/{training}/users/{user}/completion', [TrainingController::class, 'markCompletion'])
        ->name('trainings.mark-completion');
    Route::post('/trainings/{training}/certificates', [TrainingController::class, 'uploadCertificate'])
        ->name('trainings.upload-certificate');

    Route::resource('inspection-checklists', InspectionChecklistController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update']);

    Route::resource('inspections', InspectionController::class)
        ->only(['index', 'create', 'store', 'show']);

    Route::post('/inspections/{inspection}/responses', [InspectionController::class, 'updateResponses'])
        ->name('inspections.responses.update');
    Route::post('/inspections/{inspection}/responses/{response}/images', [InspectionController::class, 'uploadImage'])
        ->name('inspections.responses.images.store');
    Route::post('/inspections/{inspection}/submit', [InspectionController::class, 'submit'])
        ->name('inspections.submit');

    Route::resource('site-audits', SiteAuditController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update']);

    Route::post('/site-audits/bulk-action', [SiteAuditController::class, 'bulkAction'])
        ->name('site-audits.bulk-action');

    Route::get('/worker-tracking/workers-ui', [WorkerTrackingPageController::class, 'index'])
        ->name('worker-tracking.ui.index');
    Route::get('/worker-tracking/workers-ui/{worker}', [WorkerTrackingPageController::class, 'show'])
        ->name('worker-tracking.ui.show');

    Route::post('/site-audits/{site_audit}/submit', [SiteAuditController::class, 'submit'])
        ->name('site-audits.submit');
    Route::post('/site-audits/{site_audit}/approve', [SiteAuditController::class, 'approve'])
        ->name('site-audits.approve');
    Route::post('/site-audits/{site_audit}/reject', [SiteAuditController::class, 'reject'])
        ->name('site-audits.reject');
    Route::post('/site-audits/{siteAudit}/kpis', [SiteAuditController::class, 'storeKpi'])
        ->name('site-audits.kpis.store');

    Route::post('/site-audits/{siteAudit}/ncrs', [NcrReportController::class, 'store'])
        ->name('site-audits.ncrs.store');
    Route::put('/ncr-reports/{ncrReport}', [NcrReportController::class, 'update'])
        ->name('ncr-reports.update');
    Route::post('/ncr-reports/{ncrReport}/corrective-actions', [NcrReportController::class, 'storeCorrectiveAction'])
        ->name('ncr-reports.corrective-actions.store');
    Route::put('/corrective-actions/{correctiveAction}', [NcrReportController::class, 'updateCorrectiveAction'])
        ->name('corrective-actions.update');

    Route::get('/worker-tracking/workers', [WorkerTrackingController::class, 'index'])
        ->name('worker-tracking.workers.index');
    Route::post('/worker-tracking/workers', [WorkerTrackingController::class, 'store'])
        ->name('worker-tracking.workers.store');
    Route::get('/worker-tracking/workers/{worker}', [WorkerTrackingController::class, 'show'])
        ->name('worker-tracking.workers.show');
    Route::put('/worker-tracking/workers/{worker}', [WorkerTrackingController::class, 'update'])
        ->name('worker-tracking.workers.update');
    Route::post('/worker-tracking/workers/{worker}/attendance', [WorkerTrackingController::class, 'logAttendance'])
        ->name('worker-tracking.workers.attendance.store');
    Route::post('/worker-tracking/workers/{worker}/simulate', [WorkerTrackingController::class, 'simulateTracking'])
        ->name('worker-tracking.workers.simulate');
    Route::get('/worker-tracking/workers/{worker}/attendance', [WorkerTrackingController::class, 'attendanceFeed'])
        ->name('worker-tracking.workers.attendance.index');
});

require __DIR__.'/auth.php';
