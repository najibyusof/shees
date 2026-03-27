<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class IncidentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'temporary_id'     => $this->temporary_id,
            'title'            => $this->title,
            'description'      => $this->description,
            'location'         => $this->location,
            'datetime'         => $this->datetime?->toIso8601String(),
            'classification'   => $this->classification,
            'status'           => $this->status,
            'reported_by'      => $this->reported_by,
            'reporter'         => new UserResource($this->whenLoaded('reporter')),
            'attachments'      => $this->whenLoaded('attachments', fn () => $this->attachments->map(fn ($a) => [
                'id'            => $a->id,
                'url'           => $a->url,
                'original_name' => $a->original_name,
                'mime_type'     => $a->mime_type,
                'size'          => $a->size,
            ])),
            'comments_count'   => $this->whenLoaded('comments', fn () => $this->comments->count()),
            'rejection_reason' => $this->rejection_reason,
            'submitted_at'     => $this->submitted_at?->toIso8601String(),
            'approved_at'      => $this->approved_at?->toIso8601String(),
            'rejected_at'      => $this->rejected_at?->toIso8601String(),
            'local_created_at' => $this->local_created_at?->toIso8601String(),
            'deleted_at'       => $this->deleted_at?->toIso8601String(),
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
