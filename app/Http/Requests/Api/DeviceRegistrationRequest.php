<?php

namespace App\Http\Requests\Api;

class DeviceRegistrationRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id'   => ['required', 'string', 'max:255'],
            'device_name' => ['required', 'string', 'max:255'],
            'platform'    => ['nullable', 'string', 'in:ios,android,web'],
            'app_version' => ['nullable', 'string', 'max:50'],
            'push_token'  => ['nullable', 'string', 'max:500'],
        ];
    }
}
