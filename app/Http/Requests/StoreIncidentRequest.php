<?php

namespace App\Http\Requests;

use App\Models\Incident;
use App\Support\IncidentRules;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreIncidentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Incident::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return IncidentRules::strictCreatePayload();
    }

    public function messages(): array
    {
        return [
            'incident_date.required' => 'Incident date is required.',
            'incident_time.required' => 'Incident time is required.',
            'incident_type_id.required' => 'Incident type is required.',
            'work_package_id.required' => 'Work package is required.',
            'classification_id.required' => 'Classification is required.',
            'location_type_id.required' => 'Location type is required.',
            'location_id.required_without' => 'Please choose a location or provide Other Location.',
            'other_location.required_without' => 'Other Location is required when no location is selected.',
            'incident_description.required' => 'Incident description is required.',
            'immediate_response.required' => 'Immediate response is required.',
            'work_activity_id.required' => 'Work activity is required.',
            'subcontractor_id.required_without_all' => 'Please select a subcontractor or provide manual subcontractor details.',
            'person_in_charge.required_without' => 'Person in charge is required when subcontractor is not selected.',
            'subcontractor_contact_number.required_without' => 'Subcontractor contact number is required when subcontractor is not selected.',
            'attachments.required' => 'At least one attachment is required.',
            'attachments.min' => 'At least one attachment is required.',
            'attachments.*.file.required_without' => 'Attachment file is required.',
            'attachments.*.file.mimes' => 'Attachment must be a JPG, PNG, or PDF file.',
            'attachments.*.file.max' => 'Attachment size must not exceed 10 MB.',
        ];
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
            'incident_reference_number' => null,
            'incident_description' => $this->input('incident_description', $this->input('description')),
            'other_location' => $this->input('other_location', $this->input('location')),
            'incident_date' => $date,
            'incident_time' => $time,
            'work_activity_ids' => $this->normalizeWorkActivityIds(),
            'work_activity_id' => $this->input('work_activity_id', $this->input('work_activity_ids.0')),
        ]);
    }

    private function normalizeWorkActivityIds(): array
    {
        $ids = $this->input('work_activity_ids', []);

        if (! is_array($ids)) {
            $ids = $ids !== null && $ids !== '' ? [$ids] : [];
        }

        $single = $this->input('work_activity_id');
        if ($single !== null && $single !== '') {
            $ids[] = $single;
        }

        return collect($ids)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
