<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class InspectionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                      => $this->id,
            'offline_uuid'            => $this->offline_uuid,
            'inspection_checklist_id' => $this->inspection_checklist_id,
            'inspector_id'            => $this->inspector_id,
            'inspector'               => new UserResource($this->whenLoaded('inspector')),
            'status'                  => $this->status,
            'location'                => $this->location,
            'performed_at'            => $this->performed_at?->toIso8601String(),
            'submitted_at'            => $this->submitted_at?->toIso8601String(),
            'notes'                   => $this->notes,
            'device_identifier'       => $this->device_identifier,
            'sync_status'             => $this->sync_status,
            'last_synced_at'          => $this->last_synced_at?->toIso8601String(),
            'checklist'               => $this->whenLoaded('checklist', fn () => [
                'id'      => $this->checklist->id,
                'title'   => $this->checklist->title,
                'code'    => $this->checklist->code,
                'version' => $this->checklist->version,
            ]),
            'responses_count'         => $this->whenLoaded('responses', fn () => $this->responses->count()),
            'created_at'              => $this->created_at?->toIso8601String(),
            'updated_at'              => $this->updated_at?->toIso8601String(),
        ];
    }
}
