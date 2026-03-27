<?php

namespace App\Http\Requests\Api\Workers;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkerApiRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['Admin', 'Manager', 'Safety Officer']) ?? false;
    }

    public function rules(): array
    {
        $workerId = $this->route('worker')?->id;

        return [
            'user_id'                    => ['nullable', 'integer', 'exists:users,id'],
            'employee_code'              => ['sometimes', 'required', 'string', 'max:100', Rule::unique('workers', 'employee_code')->ignore($workerId)],
            'full_name'                  => ['sometimes', 'required', 'string', 'max:255'],
            'phone'                      => ['nullable', 'string', 'max:30'],
            'department'                 => ['nullable', 'string', 'max:100'],
            'position'                   => ['nullable', 'string', 'max:100'],
            'status'                     => ['nullable', 'string', 'in:active,inactive,suspended'],
            'geofence_center_latitude'   => ['nullable', 'numeric', 'between:-90,90'],
            'geofence_center_longitude'  => ['nullable', 'numeric', 'between:-180,180'],
            'geofence_radius_meters'     => ['nullable', 'integer', 'min:10', 'max:50000'],
        ];
    }
}
