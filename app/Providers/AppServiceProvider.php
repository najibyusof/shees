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
use App\Models\Role;
use App\Models\SiteAudit;
use App\Models\Training;
use App\Models\User;
use App\Models\Worker;
use App\Policies\IncidentPolicy;
use App\Policies\InspectionPolicy;
use App\Policies\InspectionChecklistPolicy;
use App\Policies\NcrReportPolicy;
use App\Policies\RolePolicy;
use App\Policies\SiteAuditPolicy;
use App\Policies\TrainingPolicy;
use App\Policies\UserPolicy;
use App\Policies\WorkerPolicy;
use App\Listeners\SendApprovalRequiredNotification;
use App\Listeners\SendIncidentSubmittedNotification;
use App\Listeners\SendTrainingExpiryNotification;
use App\Observers\ActivityAuditObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('dashboard', function (Request $request) {
            return Limit::perMinute(60)->by((string) ($request->user()?->id ?: $request->ip()));
        });

        // Gate::before only grants when the permission name matches exactly —
        // keeps it scoped to explicit permission names, NOT wildcard policy methods.
        Gate::before(function ($user, string $ability) {
            if (! $user || ! method_exists($user, 'hasPermissionTo')) {
                return null;
            }
            // Only apply the shortcut for known flat-permission abilities
            // (e.g. view_dashboard, roles.manage). Policy method names like
            // 'viewAny', 'update', 'delete' are NOT matched here deliberately
            // so policies remain the single source of truth for model actions.
            if (str_contains($ability, '_') || str_contains($ability, '.')) {
                return $user->hasPermissionTo($ability) ? true : null;
            }
            return null;
        });

        Gate::policy(Training::class, TrainingPolicy::class);
        Gate::policy(Worker::class, WorkerPolicy::class);
        Gate::policy(Inspection::class, InspectionPolicy::class);
        Gate::policy(InspectionChecklist::class, InspectionChecklistPolicy::class);
        Gate::policy(NcrReport::class, NcrReportPolicy::class);
        Gate::policy(Incident::class, IncidentPolicy::class);
        Gate::policy(SiteAudit::class, SiteAuditPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

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
