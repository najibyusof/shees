<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Scope queries to only included records the user has explicit access to.
 *
 * Usage in Model:
 *   use HasResourceScoping;
 *
 *   public static function scopeAccessibleTo(Builder $query, User $user): Builder
 *   {
 *       return match (true) {
 *           $user->hasRole('Admin') => $query,
 *           $user->hasRole('Manager') => $query->where('team_id', $user->team_id),
 *           default => $query->where('assigned_to', $user->id),
 *       };
 *   }
 *
 * Then use in policy:
 *   public function view(User $user, Incident $incident): bool
 *   {
 *       return Incident::accessibleTo($user)->where('id', $incident->id)->exists();
 *   }
 */
trait HasResourceScoping
{
    /**
     * Filter query to only resources accessible to the given user.
     */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        // Override in model to define scoping rules
        return $query;
    }

    /**
     * Check if a resource is accessible to the given user.
     */
    public function isAccessibleTo(User $user): bool
    {
        return static::accessibleTo($user)
            ->where($this->getKeyName(), $this->getKey())
            ->exists();
    }
}
