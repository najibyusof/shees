<?php

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;
use App\Services\IncidentWorkflowService;

class IncidentPolicy
{
    // ── Private helpers ───────────────────────────────────────────────────

    private function isCreator(User $user): bool
    {
        return $user->hasPermissionTo("incidents.submit")
            || $user->hasPermissionTo("create_incident");
    }

    private function isWorkflowParticipant(User $user): bool
    {
        return $this->isCreator($user)
            || $user->hasAnyRole(Incident::WORKFLOW_ROLES)
            || $user->hasPermissionTo("reports.view");
    }

    // ── Basic CRUD ────────────────────────────────────────────────────────

    public function viewAny(User $user): bool
    {
        return $this->isWorkflowParticipant($user);
    }

    public function view(User $user, Incident $incident): bool
    {
        return $this->viewAny($user) || $incident->reported_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->isCreator($user);
    }

    public function update(User $user, Incident $incident): bool
    {
        if (! $this->isWorkflowParticipant($user)) {
            return false;
        }

        if ($user->hasRole("Admin")) {
            return true;
        }

        if ($incident->status === "draft" && $incident->reported_by === $user->id) {
            return true;
        }

        if ($user->hasRole(IncidentWorkflowService::ROLE_MANAGER)
            && $incident->status === "draft") {
            return true;
        }

        if ($user->hasAnyRole([IncidentWorkflowService::ROLE_HOD_HSSE, IncidentWorkflowService::ROLE_APSB_PD])
            && in_array($incident->status, [
                "draft_submitted", "draft_reviewed",
                "final_submitted", "final_reviewed", "pending_closure",
            ], true)) {
            return true;
        }

        if ($user->hasRole(IncidentWorkflowService::ROLE_MRTS)
            && in_array($incident->status, [
                "final_submitted", "final_reviewed", "pending_closure",
            ], true)) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Incident $incident): bool
    {
        return $user->hasRole("Admin")
            || ($incident->reported_by === $user->id && $incident->status === "draft");
    }

    public function transition(User $user, Incident $incident): bool
    {
        if ($incident->status === "closed") {
            return false;
        }

        return app(IncidentWorkflowService::class)
            ->allowedTransitionsFor($user, $incident) !== [];
    }

    public function comment(User $user, Incident $incident): bool
    {
        return $this->view($user, $incident);
    }
}
