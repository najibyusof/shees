<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    /**
     * Log an RBAC action (authorization check, permission grant/deny, authorization failure).
     *
     * @param  string  $action  (e.g., 'view_incident', 'create_training', 'approve_final')
     * @param  string  $module  (e.g., 'Incident', 'Training', 'Dashboard')
     * @param  string  $result  ('allowed', 'denied', 'escalated', 'attempted')
     */
    public static function log(
        User $user,
        string $action,
        string $module,
        string $result = 'allowed',
        ?Model $auditable = null,
        array $metadata = []
    ): AuditLog {
        $metadata['result'] = $result;
        $metadata['ip_address'] = request()->ip();
        $metadata['user_agent'] = request()->header('User-Agent');
        $metadata['timestamp'] = now()->toIso8601String();

        if ($auditable) {
            $metadata['record_id'] = $auditable->getKey();
            $metadata['record_type'] = class_basename($auditable);
        }

        return AuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'module' => $module,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable?->getKey(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log a denied authorization attempt (security event).
     */
    public static function logDenied(
        User $user,
        string $action,
        string $module,
        ?Model $auditable = null,
        array $metadata = []
    ): AuditLog {
        $metadata['denial_reason'] = $metadata['denial_reason'] ?? 'Permission check failed';

        return self::log($user, $action, $module, 'denied', $auditable, $metadata);
    }

    /**
     * Log an escalated action (e.g., user performed action with higher privilege than routine).
     */
    public static function logEscalated(
        User $user,
        string $action,
        string $module,
        ?Model $auditable = null,
        array $metadata = []
    ): AuditLog {
        $metadata['escalation_context'] = $metadata['escalation_context'] ?? 'Admin or elevated permission used';

        return self::log($user, $action, $module, 'escalated', $auditable, $metadata);
    }

    /**
     * Get audit log query for a user, filtering by module/action.
     *
     * @return Builder
     */
    public static function queryUserActivity(User $user, ?string $module = null, ?string $action = null)
    {
        $query = AuditLog::where('user_id', $user->id);

        if ($module) {
            $query->where('module', $module);
        }

        if ($action) {
            $query->where('action', $action);
        }

        return $query->orderByDesc('created_at');
    }

    /**
     * Get audit logs for denied/attempted actions (security audit).
     *
     * @return Builder
     */
    public static function querySecurityEvents()
    {
        return AuditLog::whereIn('metadata->result', ['denied', 'attempted'])
            ->orderByDesc('created_at');
    }
}
