<?php

namespace App\Policies;

use App\Models\Inspection;
use App\Models\User;

class InspectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_audit');
    }

    public function view(User $user, Inspection $inspection): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return $user->hasPermissionTo('edit_audit')
            || $user->hasPermissionTo('approve_audit')
            || (int) $inspection->inspector_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_audit');
    }

    public function update(User $user, Inspection $inspection): bool
    {
        return $user->hasPermissionTo('edit_audit')
            || (int) $inspection->inspector_id === (int) $user->id;
    }

    public function delete(User $user, Inspection $inspection): bool
    {
        return $user->hasPermissionTo('approve_audit');
    }
}
