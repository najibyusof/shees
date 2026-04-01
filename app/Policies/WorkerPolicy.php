<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Worker;

class WorkerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_worker');
    }

    public function view(User $user, Worker $worker): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return $user->hasPermissionTo('edit_worker')
            || (int) $worker->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_worker');
    }

    public function update(User $user, Worker $worker): bool
    {
        return $user->hasPermissionTo('edit_worker');
    }

    public function delete(User $user, Worker $worker): bool
    {
        return $user->hasPermissionTo('approve_worker');
    }

    public function logAttendance(User $user, Worker $worker): bool
    {
        return $user->hasPermissionTo('edit_worker') || $user->hasPermissionTo('workers.track');
    }
}
