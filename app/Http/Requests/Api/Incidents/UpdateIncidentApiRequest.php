<?php

namespace App\Http\Requests\Api\Incidents;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateIncidentApiRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true; // Controller checks policy.
    }

    public function rules(): array
    {
        return [
            'title'          => ['sometimes', 'required', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'location'       => ['sometimes', 'required', 'string', 'max:255'],
            'datetime'       => ['sometimes', 'required', 'date'],
            'classification' => ['sometimes', 'required', 'string', 'in:Minor,Moderate,Major,Critical'],
        ];
    }
}
