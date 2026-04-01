<?php

namespace App\Policies;

use App\Models\NcrReport;
use App\Models\User;

class NcrReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_audit');
    }

    public function view(User $user, NcrReport $ncrReport): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return $user->hasPermissionTo('edit_audit')
            || $user->hasPermissionTo('approve_audit')
            || (int) $ncrReport->reported_by === (int) $user->id
            || (int) ($ncrReport->owner_id ?? 0) === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_audit') || $user->hasPermissionTo('edit_audit');
    }

    public function update(User $user, NcrReport $ncrReport): bool
    {
        return $user->hasPermissionTo('edit_audit')
            || ((int) $ncrReport->reported_by === (int) $user->id && $user->hasPermissionTo('create_audit'));
    }

    public function delete(User $user, NcrReport $ncrReport): bool
    {
        return $user->hasPermissionTo('approve_audit');
    }
}
