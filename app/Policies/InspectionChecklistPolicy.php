<?php

namespace App\Policies;

use App\Models\InspectionChecklist;
use App\Models\User;

class InspectionChecklistPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_audit');
    }

    public function view(User $user, InspectionChecklist $inspectionChecklist): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_audit');
    }

    public function update(User $user, InspectionChecklist $inspectionChecklist): bool
    {
        return $user->hasPermissionTo('edit_audit');
    }

    public function delete(User $user, InspectionChecklist $inspectionChecklist): bool
    {
        return $user->hasPermissionTo('approve_audit');
    }
}
