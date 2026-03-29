<?php

namespace App\Http\Requests\Api\Incidents;

use App\Http\Requests\Api\ApiFormRequest;
use App\Support\IncidentRules;

class UpdateIncidentApiRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true; // Controller checks policy.
    }

    public function rules(): array
    {
        return IncidentRules::partialPayload();
    }
}
