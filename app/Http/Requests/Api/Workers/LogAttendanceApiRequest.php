<?php

namespace App\Http\Requests\Api\Workers;

use App\Http\Requests\Api\ApiFormRequest;

class LogAttendanceApiRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_type'       => ['required', 'string', 'in:check_in,check_out,ping,break'],
            'latitude'         => ['required', 'numeric', 'between:-90,90'],
            'longitude'        => ['required', 'numeric', 'between:-180,180'],
            'accuracy_meters'  => ['nullable', 'numeric', 'min:0'],
            'speed_mps'        => ['nullable', 'numeric', 'min:0'],
            'heading_degrees'  => ['nullable', 'numeric', 'between:0,360'],
            'logged_at'        => ['nullable', 'date'],
            'device_identifier'=> ['nullable', 'string', 'max:255'],
            'temporary_id'     => ['nullable', 'uuid'],
            'local_created_at' => ['nullable', 'date'],
        ];
    }
}
