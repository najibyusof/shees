<?php

namespace App\Http\Requests\Api\Incidents;

use App\Http\Requests\Api\ApiFormRequest;
use App\Support\IncidentRules;
use Carbon\Carbon;

class StoreIncidentApiRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user ? ($user->hasPermissionTo('incidents.submit') || $user->hasPermissionTo('create_incident')) : false;
    }

    public function rules(): array
    {
        return IncidentRules::strictCreatePayload();
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
