<?php

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;

class IncidentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('incidents.submit') || $user->hasPermissionTo('reports.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Incident $incident): bool
    {
        return $this->viewAny($user) || $incident->reported_by === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('incidents.submit');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Incident $incident): bool
    {
        if (! $user->hasPermissionTo('incidents.submit')) {
            return false;
        }

        if (! in_array($incident->status, ['draft', 'rejected'], true)) {
            return false;
        }

        if ($incident->reported_by === $user->id) {
            return true;
        }

        return $user->hasAnyRole(['Admin', 'Manager', 'Safety Officer']);
    }

    public function submit(User $user, Incident $incident): bool
    {
        return $user->hasPermissionTo('incidents.submit')
            && $incident->reported_by === $user->id
            && in_array($incident->status, ['draft', 'rejected'], true);
    }

    public function approve(User $user, Incident $incident): bool
    {
        return $user->hasPermissionTo('incidents.approve')
            && $user->hasAnyRole(Incident::APPROVAL_REQUIRED_ROLES)
            && in_array($incident->status, ['submitted', 'under_review'], true)
            && $user->id !== $incident->reported_by;
    }

    public function reject(User $user, Incident $incident): bool
    {
        return $this->approve($user, $incident);
    }

    public function comment(User $user, Incident $incident): bool
    {
        return $this->view($user, $incident) && $user->hasPermissionTo('incidents.comment');
    }
}
