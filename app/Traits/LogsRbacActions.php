<?php

namespace App\Traits;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait to log RBAC actions and authorization checks.
 *
 * Usage in Controller:
 *   use LogsRbacActions;
 *
 *   public function store(Request $request)
 *   {
 *       $this->authorize('create', Incident::class);
 *       $this->auditLog(auth()->user(), 'create_incident', 'Incident', 'allowed');
 *       // ... rest of logic
 *   }
 */
trait LogsRbacActions
{
    protected User $auditUser;

    /**
     * Log an RBAC action with optional model context.
     */
    protected function auditLog(
        User $user,
        string $action,
        string $module,
        string $result = 'allowed',
        ?Model $auditable = null,
        array $metadata = []
    ): void {
        AuditService::log($user, $action, $module, $result, $auditable, $metadata);
    }

    /**
     * Log a denied authorization attempt.
     */
    protected function auditLogDenied(
        User $user,
        string $action,
        string $module,
        ?Model $auditable = null,
        array $metadata = []
    ): void {
        AuditService::logDenied($user, $action, $module, $auditable, $metadata);
    }
}
