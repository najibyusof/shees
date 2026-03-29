<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Incident;
use App\Models\IncidentActivity;
use App\Models\IncidentComment;
use App\Models\IncidentWorkflowLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * IncidentWorkflowService
 *
 * Single point of truth for all workflow state transitions.
 * No rejection state — feedback is handled through comments.
 * Only the designated role may advance the incident to the next stage.
 */
class IncidentWorkflowService
{
    // ── Role constants ────────────────────────────────────────────────────
    public const ROLE_MANAGER    = 'Manager';
    public const ROLE_HOD_HSSE   = 'HOD HSSE';
    public const ROLE_APSB_PD    = 'APSB PD';
    public const ROLE_MRTS       = 'MRTS';

    /**
     * Ordered transition map:
     *   from_status => [ to_status => [roles_allowed_to_trigger] ]
     *
     * Only designated roles may move the incident forward.
     * Status NEVER moves backward.
     */
    public const TRANSITIONS = [
        'draft' => [
            'draft_submitted' => [self::ROLE_MANAGER],
        ],
        'draft_submitted' => [
            'draft_reviewed' => [self::ROLE_HOD_HSSE],
        ],
        'draft_reviewed' => [
            'final_submitted' => [self::ROLE_HOD_HSSE, self::ROLE_APSB_PD],
        ],
        'final_submitted' => [
            'final_reviewed' => [self::ROLE_MRTS],
        ],
        'final_reviewed' => [
            'pending_closure' => [self::ROLE_HOD_HSSE],
        ],
        'pending_closure' => [
            'closed' => [self::ROLE_MRTS],
        ],
    ];

    /**
     * Human-readable action label per destination status.
     */
    public const ACTION_LABELS = [
        'draft_submitted' => 'Submit Draft',
        'draft_reviewed'  => 'Mark Draft as Reviewed',
        'final_submitted' => 'Submit Final Report',
        'final_reviewed'  => 'Mark Final as Reviewed',
        'pending_closure' => 'Request Closure',
        'closed'          => 'Close Incident',
    ];

    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    // ── Primary action ────────────────────────────────────────────────────

    /**
     * Transition the incident to a new status.
     *
     * @throws ValidationException if the user is not authorised or the transition is invalid.
     */
    public function transition(
        Incident $incident,
        User $user,
        string $toStatus,
        ?string $remarks = null,
    ): Incident {
        return DB::transaction(function () use ($incident, $user, $toStatus, $remarks) {
            if (! $this->canTransition($user, $incident, $toStatus)) {
                throw ValidationException::withMessages([
                    'status' => "You are not authorised to transition this incident to '".
                        ucwords(str_replace('_', ' ', $toStatus))."'.",
                ]);
            }

            if ($this->isTransitionBlockedByUnresolvedCriticalComments($user, $incident, $toStatus)) {
                throw ValidationException::withMessages([
                    'status' => [
                        "Cannot progress workflow while unresolved critical comments exist ({$this->unresolvedCriticalCommentCount($incident)} open).",
                    ],
                ]);
            }

            $fromStatus = $incident->status;
            $action     = self::ACTION_LABELS[$toStatus] ?? "Move to {$toStatus}";

            $incident->update(['status' => $toStatus]);

            // Workflow log (structured transition record)
            IncidentWorkflowLog::query()->create([
                'incident_id'  => $incident->id,
                'performed_by' => $user->id,
                'from_status'  => $fromStatus,
                'to_status'    => $toStatus,
                'action'       => $action,
                'remarks'      => $remarks,
            ]);

            // Activity log (timeline entry used by show view)
            IncidentActivity::query()->create([
                'incident_id' => $incident->id,
                'user_id'     => $user->id,
                'action'      => 'status_changed',
                'description' => $action,
                'metadata'    => [
                    'from'    => $fromStatus,
                    'to'      => $toStatus,
                    'remarks' => $remarks,
                ],
            ]);

            // Audit trail
            $this->auditLogService->log($user->id, 'transition', 'incidents', $incident, [
                'from'   => $fromStatus,
                'to'     => $toStatus,
                'action' => $action,
            ]);

            return $incident->fresh();
        });
    }

    // ── Authorisation helpers ─────────────────────────────────────────────

    /**
     * Return true if the user may transition the incident to $toStatus.
     */
    public function canTransition(User $user, Incident $incident, string $toStatus): bool
    {
        $allowed = self::TRANSITIONS[$incident->status] ?? [];

        if (! isset($allowed[$toStatus])) {
            return false;
        }

        return $user->hasAnyRole($allowed[$toStatus]);
    }

    /**
     * Return an array of all valid next-statuses the given user may trigger.
     *
     * @return string[]
     */
    public function allowedTransitionsFor(User $user, Incident $incident): array
    {
        $allowed = self::TRANSITIONS[$incident->status] ?? [];

        return collect($allowed)
            ->filter(fn ($roles) => $user->hasAnyRole($roles))
            ->keys()
            ->values()
            ->all();
    }

    /**
     * Return whether the user holds any workflow-progression role.
     */
    public function isWorkflowActor(User $user): bool
    {
        return $user->hasAnyRole(array_values(array_unique(
            array_merge(...array_values(self::TRANSITIONS))
        )));
    }

    /**
     * Merge the file-based config with any DB overrides stored via AppSetting.
     */
    private function getUrcConfig(): array
    {
        $base = (array) config('incident_workflow.unresolved_critical_comments', []);

        try {
            $db = AppSetting::get('incident_workflow.unresolved_critical_comments');
        } catch (\Exception) {
            $db = null;
        }

        return is_array($db) ? array_merge($base, $db) : $base;
    }

    public function unresolvedCriticalCommentCount(Incident $incident): int
    {
        $urcConfig     = $this->getUrcConfig();
        $criticalTypes = (array) ($urcConfig['critical_comment_types'] ?? []);

        return IncidentComment::query()
            ->where('incident_id', $incident->id)
            ->where('is_resolved', false)
            ->where(function ($query) use ($criticalTypes) {
                $query->where('is_critical', true);

                if ($criticalTypes !== []) {
                    $query->orWhereIn('comment_type', $criticalTypes);
                }
            })
            ->count();
    }

    public function isTransitionBlockedByUnresolvedCriticalComments(User $user, Incident $incident, string $toStatus): bool
    {
        $urcConfig = $this->getUrcConfig();

        if (! (bool) ($urcConfig['enabled'] ?? false)) {
            return false;
        }

        $allowedRoles = self::TRANSITIONS[$incident->status][$toStatus] ?? [];
        if ($allowedRoles === []) {
            return false;
        }

        $roleRules = (array) ($urcConfig['role_rules'] ?? []);

        $enforcedForActor = collect($allowedRoles)
            ->filter(fn (string $role): bool => $user->hasRole($role))
            ->contains(fn (string $role): bool => (bool) data_get($roleRules, $role.'.enforce', false));

        if (! $enforcedForActor) {
            return false;
        }

        return $this->unresolvedCriticalCommentCount($incident) > 0;
    }

    // ── Informational helpers ─────────────────────────────────────────────

    /**
     * Return the ordered index of $status in the workflow (0-based).
     */
    public function statusIndex(string $status): int
    {
        $keys = array_keys(Incident::WORKFLOW_STEPS);

        return (int) array_search($status, $keys, true);
    }

    /**
     * Return display label for a status.
     */
    public static function statusLabel(string $status): string
    {
        return Incident::WORKFLOW_STEPS[$status]['label'] ?? ucwords(str_replace('_', ' ', $status));
    }
}
