<?php

namespace App\Http\Requests\Api\Trainings;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateTrainingApiRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['Admin', 'Manager', 'Safety Officer']) ?? false;
    }

    public function rules(): array
    {
        return [
            'title'                     => ['sometimes', 'required', 'string', 'max:255'],
            'description'               => ['nullable', 'string'],
            'starts_at'                 => ['nullable', 'date'],
            'ends_at'                   => ['nullable', 'date', 'after_or_equal:starts_at'],
            'certificate_validity_days' => ['nullable', 'integer', 'min:1'],
            'is_active'                 => ['nullable', 'boolean'],
        ];
    }
}
