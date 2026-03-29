<?php

namespace App\Http\Requests;

use App\Models\Incident;
use App\Support\IncidentRules;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateIncidentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $incident = $this->route('incident');

        return (bool) ($incident && $this->user()?->can('update', $incident));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return IncidentRules::fullPayload();
    }

    protected function prepareForValidation(): void
    {
        $date = $this->input('incident_date');
        $time = $this->input('incident_time');
        $dateTime = $this->input('datetime');

        if ((! $date || ! $time) && filled($dateTime)) {
            try {
                $parsed = Carbon::parse((string) $dateTime);
                $date ??= $parsed->toDateString();
                $time ??= $parsed->format('H:i');
            } catch (\Throwable) {
            }
        }

        $this->merge([
            'incident_description' => $this->input('incident_description', $this->input('description')),
            'other_location' => $this->input('other_location', $this->input('location')),
            'incident_date' => $date,
            'incident_time' => $time,
        ]);
    }
}
