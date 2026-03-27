<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        $rolesLoaded = $this->relationLoaded('roles');

        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'email'          => $this->email,
            'email_verified' => ! is_null($this->email_verified_at),
            'roles'          => $this->when($rolesLoaded, fn () => $this->roles->map(fn ($role) => [
                'id'          => $role->id,
                'name'        => $role->name,
                'slug'        => $role->slug,
                'permissions' => $role->relationLoaded('permissions')
                    ? $role->permissions->pluck('name')
                    : [],
            ])),
            'permissions'    => $this->when(
                $rolesLoaded,
                fn () => $this->roles
                    ->filter(fn ($role) => $role->relationLoaded('permissions'))
                    ->flatMap(fn ($role) => $role->permissions->pluck('name'))
                    ->unique()
                    ->values()
            ),
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}
