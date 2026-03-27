<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                            => $this->id,
            'temporary_id'                  => $this->temporary_id,
            'worker_id'                     => $this->worker_id,
            'event_type'                    => $this->event_type,
            'logged_at'                     => $this->logged_at?->toIso8601String(),
            'latitude'                      => $this->latitude,
            'longitude'                     => $this->longitude,
            'accuracy_meters'               => $this->accuracy_meters,
            'speed_mps'                     => $this->speed_mps,
            'heading_degrees'               => $this->heading_degrees,
            'source'                        => $this->source,
            'device_identifier'             => $this->device_identifier,
            'inside_geofence'               => $this->inside_geofence,
            'distance_from_geofence_meters' => $this->distance_from_geofence_meters,
            'alert_level'                   => $this->alert_level,
            'alert_message'                 => $this->alert_message,
            'local_created_at'              => $this->local_created_at?->toIso8601String(),
            'deleted_at'                    => $this->deleted_at?->toIso8601String(),
            'created_at'                    => $this->created_at?->toIso8601String(),
            'updated_at'                    => $this->updated_at?->toIso8601String(),
        ];
    }
}
