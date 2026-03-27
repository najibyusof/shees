<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                        => $this->id,
            'employee_code'             => $this->employee_code,
            'full_name'                 => $this->full_name,
            'phone'                     => $this->phone,
            'department'                => $this->department,
            'position'                  => $this->position,
            'status'                    => $this->status,
            'geofence_center_latitude'  => $this->geofence_center_latitude,
            'geofence_center_longitude' => $this->geofence_center_longitude,
            'geofence_radius_meters'    => $this->geofence_radius_meters,
            'last_latitude'             => $this->last_latitude,
            'last_longitude'            => $this->last_longitude,
            'last_seen_at'              => $this->last_seen_at?->toIso8601String(),
            'user'                      => new UserResource($this->whenLoaded('user')),
            'deleted_at'                => $this->deleted_at?->toIso8601String(),
            'created_at'                => $this->created_at?->toIso8601String(),
            'updated_at'                => $this->updated_at?->toIso8601String(),
        ];
    }
}
