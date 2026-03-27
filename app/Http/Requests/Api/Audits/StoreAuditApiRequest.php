<?php

namespace App\Http\Requests\Api\Audits;

use App\Http\Requests\Api\ApiFormRequest;

class StoreAuditApiRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['Admin', 'Manager', 'Safety Officer']) ?? false;
    }

    public function rules(): array
    {
        return [
            'site_name'     => ['required', 'string', 'max:255'],
            'area'          => ['nullable', 'string', 'max:255'],
            'audit_type'    => ['nullable', 'string', 'in:internal,external,regulatory,supplier'],
            'scheduled_for' => ['nullable', 'date'],
            'conducted_at'  => ['nullable', 'date'],
            'scope'         => ['nullable', 'string'],
            'summary'       => ['nullable', 'string'],
            'status'        => ['nullable', 'string', 'in:draft,scheduled,in_progress'],
        ];
    }
}
