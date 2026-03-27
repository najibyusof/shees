<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class NcrReportResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                     => $this->id,
            'reference_no'           => $this->reference_no,
            'site_audit_id'          => $this->site_audit_id,
            'title'                  => $this->title,
            'description'            => $this->description,
            'severity'               => $this->severity,
            'status'                 => $this->status,
            'root_cause'             => $this->root_cause,
            'containment_action'     => $this->containment_action,
            'corrective_action_plan' => $this->corrective_action_plan,
            'due_date'               => $this->due_date?->toIso8601String(),
            'verified_at'            => $this->verified_at?->toIso8601String(),
            'closed_at'              => $this->closed_at?->toIso8601String(),
            'reported_by'            => $this->reported_by,
            'reporter'               => new UserResource($this->whenLoaded('reporter')),
            'corrective_actions'     => $this->whenLoaded('correctiveActions', fn () => $this->correctiveActions->map(fn ($ca) => [
                'id'               => $ca->id,
                'title'            => $ca->title,
                'description'      => $ca->description,
                'status'           => $ca->status,
                'due_date'         => $ca->due_date?->toIso8601String(),
                'completed_at'     => $ca->completed_at?->toIso8601String(),
                'assigned_to'      => $ca->assigned_to,
            ])),
            'deleted_at'             => $this->deleted_at?->toIso8601String(),
            'created_at'             => $this->created_at?->toIso8601String(),
            'updated_at'             => $this->updated_at?->toIso8601String(),
        ];
    }
}
