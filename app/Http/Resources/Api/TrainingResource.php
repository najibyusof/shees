<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class TrainingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                        => $this->id,
            'title'                     => $this->title,
            'description'               => $this->description,
            'starts_at'                 => $this->starts_at?->toIso8601String(),
            'ends_at'                   => $this->ends_at?->toIso8601String(),
            'certificate_validity_days' => $this->certificate_validity_days,
            'is_active'                 => $this->is_active,
            'enrolled_users_count'      => $this->whenLoaded('users', fn () => $this->users->count()),
            'my_pivot'                  => $this->when(
                $this->relationLoaded('users') && $this->pivot !== null,
                fn () => [
                    'assigned_at'       => $this->pivot?->assigned_at,
                    'completed_at'      => $this->pivot?->completed_at,
                    'completion_status' => $this->pivot?->completion_status,
                ]
            ),
            'deleted_at'                => $this->deleted_at?->toIso8601String(),
            'created_at'                => $this->created_at?->toIso8601String(),
            'updated_at'                => $this->updated_at?->toIso8601String(),
        ];
    }
}
