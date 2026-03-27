<?php

namespace App\Http\Requests\Api\Ncr;

use App\Http\Requests\Api\ApiFormRequest;

class StoreNcrApiRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['Admin', 'Manager', 'Safety Officer']) ?? false;
    }

    public function rules(): array
    {
        return [
            'site_audit_id'          => ['required', 'integer', 'exists:site_audits,id'],
            'title'                  => ['required', 'string', 'max:255'],
            'description'            => ['nullable', 'string'],
            'severity'               => ['required', 'string', 'in:low,medium,high,critical'],
            'root_cause'             => ['nullable', 'string'],
            'containment_action'     => ['nullable', 'string'],
            'corrective_action_plan' => ['nullable', 'string'],
            'due_date'               => ['nullable', 'date'],
        ];
    }
}
