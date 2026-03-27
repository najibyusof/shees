<?php

namespace App\Http\Requests\Api\Incidents;

use App\Http\Requests\Api\ApiFormRequest;

class StoreIncidentApiRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('incidents.submit') ?? false;
    }

    public function rules(): array
    {
        return [
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'location'         => ['required', 'string', 'max:255'],
            'datetime'         => ['required', 'date'],
            'classification'   => ['required', 'string', 'in:Minor,Moderate,Major,Critical'],
            'temporary_id'     => ['nullable', 'uuid'],
            'local_created_at' => ['nullable', 'date'],
        ];
    }
}
