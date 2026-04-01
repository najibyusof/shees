<?php

namespace App\Policies;

use App\Models\Training;
use App\Models\User;

class TrainingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_training');
    }

    public function view(User $user, Training $training): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return $user->hasPermissionTo('edit_training')
            || $user->hasPermissionTo('approve_training')
            || $training->users()->where('users.id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_training');
    }

    public function update(User $user, Training $training): bool
    {
        return $user->hasPermissionTo('edit_training');
    }

    public function delete(User $user, Training $training): bool
    {
        return $user->hasPermissionTo('approve_training');
    }

    public function assignUsers(User $user, Training $training): bool
    {
        return $this->update($user, $training);
    }

    public function markCompletion(User $user, Training $training): bool
    {
        return $this->update($user, $training);
    }

    public function uploadCertificate(User $user, Training $training): bool
    {
        return $this->update($user, $training);
    }
}
