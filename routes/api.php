<?php

use App\Http\Controllers\Api\InspectionMobileController;
use App\Http\Controllers\Api\InspectionSyncController;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\SwaggerController;
use App\Http\Controllers\Api\V1\AuditApiController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\IncidentApiController;
use App\Http\Controllers\Api\V1\IncidentWorkflowApiController;
use App\Http\Controllers\Api\V1\InspectionApiController;
use App\Http\Controllers\Api\V1\NcrApiController;
use App\Http\Controllers\Api\V1\SyncController;
use App\Http\Controllers\Api\V1\TrainingApiController;
use App\Http\Controllers\Api\V1\UserApiController;
use App\Http\Controllers\Api\V1\WorkerApiController;
use Illuminate\Support\Facades\Route;

// ============================================================================
//  V1 – General Mobile API
//  All routes prefixed /api/v1  |  auth via mobile.token middleware
// ============================================================================

Route::prefix('v1')->group(function () {

    // --- Authentication ---------------------------------------------------
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('api.v1.auth.login');

    Route::middleware('mobile.token')->group(function () {

        Route::post('/auth/logout', [AuthController::class, 'logout'])
            ->name('api.v1.auth.logout');

        Route::get('/user', [AuthController::class, 'user'])
            ->name('api.v1.auth.user');

        // --- Device Registration ------------------------------------------
        Route::post('/device/register', [DeviceController::class, 'register'])
            ->name('api.v1.device.register');
        Route::get('/device/registrations', [DeviceController::class, 'index'])
            ->name('api.v1.device.index');
        Route::delete('/device/{deviceId}', [DeviceController::class, 'deregister'])
            ->where('deviceId', '[A-Za-z0-9_\-]+')
            ->name('api.v1.device.deregister');

        // --- General Offline Sync -----------------------------------------
        Route::post('/sync', [SyncController::class, 'sync'])
            ->middleware('throttle:60,1')
            ->name('api.v1.sync');

        // --- Users --------------------------------------------------------
        Route::apiResource('users', UserApiController::class)
            ->names('api.v1.users');

        // --- Incidents ----------------------------------------------------
        Route::apiResource('incidents', IncidentApiController::class)
            ->names('api.v1.incidents');

        // --- Incident Workflow (collaborative, comment-driven) ------------
        Route::post('incidents/{incident}/transition', [IncidentWorkflowApiController::class, 'transition'])
            ->name('api.v1.incidents.transition');
        Route::post('incidents/{incident}/comments', [IncidentWorkflowApiController::class, 'storeComment'])
            ->name('api.v1.incidents.comments.store');
        Route::get('incidents/{incident}/allowed-transitions', [IncidentWorkflowApiController::class, 'allowedTransitions'])
            ->name('api.v1.incidents.transitions.index');
        Route::post('comments/{comment}/reply', [IncidentWorkflowApiController::class, 'storeReply'])
            ->name('api.v1.comments.reply');
        Route::patch('comments/{comment}/resolve', [IncidentWorkflowApiController::class, 'resolveComment'])
            ->name('api.v1.comments.resolve');

        // --- Trainings ----------------------------------------------------
        Route::apiResource('trainings', TrainingApiController::class)
            ->names('api.v1.trainings');

        // --- Inspections --------------------------------------------------
        Route::apiResource('inspections', InspectionApiController::class)
            ->names('api.v1.inspections');

        // --- Site Audits --------------------------------------------------
        Route::apiResource('audits', AuditApiController::class)
            ->names('api.v1.audits');

        // --- NCR Reports --------------------------------------------------
        Route::apiResource('ncr', NcrApiController::class)
            ->parameters(['ncr' => 'ncrReport'])
            ->names('api.v1.ncr');

        // --- Workers & Attendance -----------------------------------------
        Route::post('workers/{worker}/attendance', [WorkerApiController::class, 'logAttendance'])
            ->name('api.v1.workers.attendance');
        Route::apiResource('workers', WorkerApiController::class)
            ->names('api.v1.workers');
    });
});

// ============================================================================
//  V1 – Inspection-specific Mobile API (existing, preserved)
//  Prefix: /api/v1/inspection
// ============================================================================

Route::prefix('v1/inspection')->group(function () {
    Route::post('/auth/login', [MobileAuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('api.inspection.auth.login');

    Route::middleware('mobile.token')->group(function () {
        Route::post('/auth/logout', [MobileAuthController::class, 'logout'])
            ->name('api.inspection.auth.logout');
        Route::get('/auth/sessions', [MobileAuthController::class, 'sessions'])
            ->name('api.inspection.auth.sessions.index');
        Route::post('/auth/sessions/{mobileAccessToken}/revoke', [MobileAuthController::class, 'revokeSession'])
            ->name('api.inspection.auth.sessions.revoke');
        Route::patch('/auth/sessions/{mobileAccessToken}', [MobileAuthController::class, 'renameSession'])
            ->name('api.inspection.auth.sessions.rename');
        Route::post('/auth/rotate', [MobileAuthController::class, 'rotate'])
            ->middleware('throttle:20,1')
            ->name('api.inspection.auth.rotate');

        Route::get('/checklists', [InspectionMobileController::class, 'checklists'])
            ->name('api.inspection.checklists.index');

        Route::post('/runs', [InspectionMobileController::class, 'start'])
            ->name('api.inspection.runs.store');
        Route::get('/runs/{inspection}', [InspectionMobileController::class, 'show'])
            ->name('api.inspection.runs.show');
        Route::put('/runs/{inspection}/responses', [InspectionMobileController::class, 'updateResponses'])
            ->name('api.inspection.runs.responses.update');
        Route::post('/runs/{inspection}/submit', [InspectionMobileController::class, 'submit'])
            ->name('api.inspection.runs.submit');

        Route::post('/sync/upload', [InspectionSyncController::class, 'enqueueUpload'])
            ->middleware('throttle:120,1')
            ->name('api.inspection.sync.upload');
        Route::get('/sync/contract', [InspectionSyncController::class, 'contract'])
            ->name('api.inspection.sync.contract');
        Route::get('/sync/metrics', [InspectionSyncController::class, 'metrics'])
            ->name('api.inspection.sync.metrics');
        Route::get('/sync/pending', [InspectionSyncController::class, 'pending'])
            ->name('api.inspection.sync.pending');
        Route::post('/sync/jobs/{inspectionSyncJob}/ack', [InspectionSyncController::class, 'acknowledge'])
            ->name('api.inspection.sync.jobs.ack');
        Route::post('/sync/jobs/{inspectionSyncJob}/conflict', [InspectionSyncController::class, 'markConflict'])
            ->name('api.inspection.sync.jobs.conflict');
        Route::post('/sync/conflicts/{inspectionSyncConflict}/resolve', [InspectionSyncController::class, 'resolveConflict'])
            ->name('api.inspection.sync.conflicts.resolve');
    });
});

// ============================================================================
// API Documentation (Swagger/OpenAPI)
// ============================================================================

Route::get('/documentation', [SwaggerController::class, 'index'])
    ->name('api.documentation');

Route::get('/documentation/json', [SwaggerController::class, 'json'])
    ->name('api.documentation.json');
