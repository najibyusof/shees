<?php

namespace App\Providers;

use App\Events\ApprovalRequired;
use App\Events\IncidentSubmitted;
use App\Events\TrainingExpiryDetected;
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
use App\Listeners\SendApprovalRequiredNotification;
use App\Listeners\SendIncidentSubmittedNotification;
use App\Listeners\SendTrainingExpiryNotification;
use App\Observers\ActivityAuditObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(IncidentSubmitted::class, SendIncidentSubmittedNotification::class);
        Event::listen(ApprovalRequired::class, SendApprovalRequiredNotification::class);
        Event::listen(TrainingExpiryDetected::class, SendTrainingExpiryNotification::class);

        Incident::observe(ActivityAuditObserver::class);
        Training::observe(ActivityAuditObserver::class);
        SiteAudit::observe(ActivityAuditObserver::class);
        NcrReport::observe(ActivityAuditObserver::class);
        CorrectiveAction::observe(ActivityAuditObserver::class);
        Worker::observe(ActivityAuditObserver::class);
        ReportPreset::observe(ActivityAuditObserver::class);
        ReportExport::observe(ActivityAuditObserver::class);
        InspectionChecklist::observe(ActivityAuditObserver::class);
        Inspection::observe(ActivityAuditObserver::class);
    }
}
