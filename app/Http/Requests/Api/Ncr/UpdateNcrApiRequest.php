<?php

namespace App\Http\Requests\Api\Ncr;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateNcrApiRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['Admin', 'Manager', 'Safety Officer']) ?? false;
    }

    public function rules(): array
    {
        return [
            'title'                  => ['sometimes', 'required', 'string', 'max:255'],
            'description'            => ['nullable', 'string'],
            'severity'               => ['sometimes', 'required', 'string', 'in:low,medium,high,critical'],
            'root_cause'             => ['nullable', 'string'],
            'containment_action'     => ['nullable', 'string'],
            'corrective_action_plan' => ['nullable', 'string'],
            'due_date'               => ['nullable', 'date'],
            'status'                 => ['nullable', 'string', 'in:open,in_progress,verified,closed'],
        ];
    }
}
