<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class AuditResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'reference_no' => $this->reference_no,
            'site_name'    => $this->site_name,
            'area'         => $this->area,
            'audit_type'   => $this->audit_type,
            'status'       => $this->status,
            'kpi_score'    => $this->kpi_score,
            'scope'        => $this->scope,
            'summary'      => $this->summary,
            'scheduled_for'=> $this->scheduled_for?->toIso8601String(),
            'conducted_at' => $this->conducted_at?->toIso8601String(),
            'created_by'   => $this->created_by,
            'creator'      => new UserResource($this->whenLoaded('creator')),
            'kpis'         => $this->whenLoaded('kpis', fn () => $this->kpis->map(fn ($k) => [
                'id'           => $k->id,
                'name'         => $k->name,
                'target_value' => $k->target_value,
                'actual_value' => $k->actual_value,
                'unit'         => $k->unit,
                'weight'       => $k->weight,
                'status'       => $k->status,
                'notes'        => $k->notes,
            ])),
            'ncr_count'    => $this->whenLoaded('ncrReports', fn () => $this->ncrReports->count()),
            'deleted_at'   => $this->deleted_at?->toIso8601String(),
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }
}
