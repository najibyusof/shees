<?php

namespace App\Support;

use App\Models\LocationType;
use Illuminate\Validation\Rule;

class IncidentRules
{
    public static function strictCreatePayload(): array
    {
        $otherLocationTypeId = LocationType::query()
            ->whereRaw('LOWER(name) = ?', ['other'])
            ->value('id');

        return [
            'incident_reference_number' => ['prohibited'],
            'title' => ['required', 'string', 'max:255'],
            'incident_type_id' => ['required', 'integer', Rule::exists('incident_types', 'id')],
            'incident_date' => ['required', 'date'],
            'incident_time' => ['required', 'date_format:H:i'],
            'status_id' => ['nullable', 'integer', Rule::exists('incident_statuses', 'id')],
            'work_package_id' => ['required', 'integer', Rule::exists('work_packages', 'id')],
            'location_type_id' => ['required', 'integer', Rule::exists('location_types', 'id')],
            'location_id' => ['required_without:other_location', 'nullable', 'integer', Rule::exists('incident_locations', 'id')],
            'other_location' => [
                'nullable',
                'string',
                'max:255',
                'required_without:location_id',
                Rule::requiredIf(static function () use ($otherLocationTypeId) {
                    if (! $otherLocationTypeId) {
                        return false;
                    }

                    $selected = request()->input('location_type_id');

                    return (string) $selected === (string) $otherLocationTypeId;
                }),
            ],
            'classification_id' => ['required', 'integer', Rule::exists('incident_classifications', 'id')],
            'reclassification_id' => ['nullable', 'integer', Rule::exists('incident_classifications', 'id')],
            'incident_description' => ['required', 'string'],
            'immediate_response' => ['required', 'string'],
            'subcontractor_id' => ['nullable', 'integer', Rule::exists('subcontractors', 'id'), 'required_without_all:person_in_charge,subcontractor_contact_number'],
            'person_in_charge' => ['nullable', 'string', 'max:255', 'required_without:subcontractor_id'],
            'subcontractor_contact_number' => ['nullable', 'string', 'max:50', 'required_without:subcontractor_id'],
            'gps_location' => ['nullable', 'string', 'max:120'],
            'activity_during_incident' => ['nullable', 'string'],
            'type_of_accident' => ['nullable', 'string', 'max:255'],
            'basic_effect' => ['nullable', 'string'],
            'conclusion' => ['nullable', 'string'],
            'close_remark' => ['nullable', 'string'],
            'rootcause_id' => ['nullable', 'integer', Rule::exists('cause_types', 'id')],
            'other_rootcause' => ['nullable', 'string', 'max:255'],
            'temporary_id' => ['nullable', 'uuid'],
            'local_created_at' => ['nullable', 'date'],

            'work_activity_id' => ['required', 'integer', Rule::exists('work_activities', 'id')],
            'work_activity_ids' => ['nullable', 'array'],
            'work_activity_ids.*' => ['integer', Rule::exists('work_activities', 'id')],

            'attachments' => ['required', 'array', 'min:1', 'max:10'],
            'attachments.*.id' => ['nullable', 'integer', Rule::exists('incident_attachments', 'id')],
            'attachments.*.attachment_type_id' => ['nullable', 'integer', Rule::exists('attachment_types', 'id')],
            'attachments.*.attachment_category_id' => ['nullable', 'integer', Rule::exists('attachment_categories', 'id')],
            'attachments.*.filename' => ['nullable', 'string', 'max:255'],
            'attachments.*.path' => ['nullable', 'string', 'max:2048'],
            'attachments.*.description' => ['nullable', 'string'],
            'attachments.*.file' => ['required_without:attachments.*.path,attachments.*.id', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf'],
            'attachments.*.temporary_id' => ['nullable', 'uuid'],
            'attachments.*.local_created_at' => ['nullable', 'date'],

            'chronologies' => ['nullable', 'array'],
            'chronologies.*.id' => ['nullable', 'integer', Rule::exists('incident_chronologies', 'id')],
            'chronologies.*.event_date' => ['nullable', 'date'],
            'chronologies.*.event_time' => ['nullable', 'date_format:H:i'],
            'chronologies.*.events' => ['nullable', 'string'],
            'chronologies.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'chronologies.*.temporary_id' => ['nullable', 'uuid'],
            'chronologies.*.local_created_at' => ['nullable', 'date'],

            'victims' => ['nullable', 'array'],
            'victims.*.id' => ['nullable', 'integer', Rule::exists('incident_victims', 'id')],
            'victims.*.victim_type_id' => ['nullable', 'integer', Rule::exists('victim_types', 'id')],
            'victims.*.name' => ['nullable', 'string', 'max:255'],
            'victims.*.identification' => ['nullable', 'string', 'max:255'],
            'victims.*.occupation' => ['nullable', 'string', 'max:255'],
            'victims.*.age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'victims.*.nationality' => ['nullable', 'string', 'max:255'],
            'victims.*.working_experience' => ['nullable', 'string', 'max:255'],
            'victims.*.nature_of_injury' => ['nullable', 'string', 'max:255'],
            'victims.*.body_injured' => ['nullable', 'string', 'max:255'],
            'victims.*.treatment' => ['nullable', 'string'],
            'victims.*.temporary_id' => ['nullable', 'uuid'],
            'victims.*.local_created_at' => ['nullable', 'date'],

            'witnesses' => ['nullable', 'array'],
            'witnesses.*.id' => ['nullable', 'integer', Rule::exists('incident_witnesses', 'id')],
            'witnesses.*.name' => ['nullable', 'string', 'max:255'],
            'witnesses.*.designation' => ['nullable', 'string', 'max:255'],
            'witnesses.*.identification' => ['nullable', 'string', 'max:255'],
            'witnesses.*.temporary_id' => ['nullable', 'uuid'],
            'witnesses.*.local_created_at' => ['nullable', 'date'],

            'investigation_team_members' => ['nullable', 'array'],
            'investigation_team_members.*.id' => ['nullable', 'integer', Rule::exists('incident_investigation_team_members', 'id')],
            'investigation_team_members.*.name' => ['nullable', 'string', 'max:255'],
            'investigation_team_members.*.designation' => ['nullable', 'string', 'max:255'],
            'investigation_team_members.*.contact_number' => ['nullable', 'string', 'max:50'],
            'investigation_team_members.*.company' => ['nullable', 'string', 'max:255'],
            'investigation_team_members.*.temporary_id' => ['nullable', 'uuid'],
            'investigation_team_members.*.local_created_at' => ['nullable', 'date'],

            'damages' => ['nullable', 'array'],
            'damages.*.id' => ['nullable', 'integer', Rule::exists('incident_damages', 'id')],
            'damages.*.damage_type_id' => ['nullable', 'integer', Rule::exists('damage_types', 'id')],
            'damages.*.estimate_cost' => ['nullable', 'numeric', 'min:0'],
            'damages.*.temporary_id' => ['nullable', 'uuid'],
            'damages.*.local_created_at' => ['nullable', 'date'],

            'immediate_actions' => ['nullable', 'array'],
            'immediate_actions.*.id' => ['nullable', 'integer', Rule::exists('incident_immediate_actions', 'id')],
            'immediate_actions.*.action_taken' => ['nullable', 'string'],
            'immediate_actions.*.company' => ['nullable', 'string', 'max:255'],
            'immediate_actions.*.temporary_id' => ['nullable', 'uuid'],
            'immediate_actions.*.local_created_at' => ['nullable', 'date'],

            'planned_actions' => ['nullable', 'array'],
            'planned_actions.*.id' => ['nullable', 'integer', Rule::exists('incident_planned_actions', 'id')],
            'planned_actions.*.action_taken' => ['nullable', 'string'],
            'planned_actions.*.expected_date' => ['nullable', 'date'],
            'planned_actions.*.actual_date' => ['nullable', 'date'],
            'planned_actions.*.temporary_id' => ['nullable', 'uuid'],
            'planned_actions.*.local_created_at' => ['nullable', 'date'],

            'remove_attachment_ids' => ['nullable', 'array'],
            'remove_attachment_ids.*' => ['integer', Rule::exists('incident_attachments', 'id')],
            'immediate_cause_ids' => ['nullable', 'array'],
            'immediate_cause_ids.*' => ['integer', Rule::exists('cause_types', 'id')],
            'contributing_factor_ids' => ['nullable', 'array'],
            'contributing_factor_ids.*' => ['integer', Rule::exists('factor_types', 'id')],
            'external_party_ids' => ['nullable', 'array'],
            'external_party_ids.*' => ['integer', Rule::exists('external_parties', 'id')],
        ];
    }

    public static function fullPayload(): array
    {
        return self::rules(partial: false);
    }

    public static function partialPayload(): array
    {
        return self::rules(partial: true);
    }

    public static function stepRules(int $step): array
    {
        return match ($step) {
            1 => [
                'title'                => ['required', 'string', 'max:255'],
                'incident_date'        => ['required', 'date'],
                'incident_time'        => ['required', 'date_format:H:i'],
                'incident_description' => ['required', 'string'],
            ],
            default => [],
        };
    }

    private static function rules(bool $partial): array
    {
        return [
            'incident_reference_number' => self::field($partial, false, ['string', 'max:50']),
            'title' => self::field($partial, ! $partial, ['string', 'max:255']),
            'incident_type_id' => self::field($partial, false, ['integer', Rule::exists('incident_types', 'id')]),
            'incident_date' => self::field($partial, ! $partial, ['date']),
            'incident_time' => self::field($partial, ! $partial, ['date_format:H:i']),
            'status_id' => self::field($partial, false, ['integer', Rule::exists('incident_statuses', 'id')]),
            'work_package_id' => self::field($partial, false, ['integer', Rule::exists('work_packages', 'id')]),
            'location_type_id' => self::field($partial, false, ['integer', Rule::exists('location_types', 'id')]),
            'location_id' => self::field($partial, false, ['integer', Rule::exists('incident_locations', 'id')]),
            'other_location' => self::field($partial, false, ['string', 'max:255']),
            'classification_id' => self::field($partial, false, ['integer', Rule::exists('incident_classifications', 'id')]),
            'reclassification_id' => self::field($partial, false, ['integer', Rule::exists('incident_classifications', 'id')]),
            'work_activity_id' => self::field($partial, false, ['integer', Rule::exists('work_activities', 'id')]),
            'incident_description' => self::field($partial, ! $partial, ['string']),
            'immediate_response' => self::field($partial, false, ['string']),
            'subcontractor_id' => self::field($partial, false, ['integer', Rule::exists('subcontractors', 'id')]),
            'person_in_charge' => self::field($partial, false, ['string', 'max:255']),
            'subcontractor_contact_number' => self::field($partial, false, ['string', 'max:50']),
            'gps_location' => self::field($partial, false, ['string', 'max:120']),
            'activity_during_incident' => self::field($partial, false, ['string']),
            'type_of_accident' => self::field($partial, false, ['string', 'max:255']),
            'basic_effect' => self::field($partial, false, ['string']),
            'conclusion' => self::field($partial, false, ['string']),
            'close_remark' => self::field($partial, false, ['string']),
            'rootcause_id' => self::field($partial, false, ['integer', Rule::exists('cause_types', 'id')]),
            'other_rootcause' => self::field($partial, false, ['string', 'max:255']),
            'temporary_id' => self::field($partial, false, ['uuid']),
            'local_created_at' => self::field($partial, false, ['date']),

            'chronologies' => ['nullable', 'array'],
            'chronologies.*.id' => ['nullable', 'integer', Rule::exists('incident_chronologies', 'id')],
            'chronologies.*.event_date' => ['nullable', 'date'],
            'chronologies.*.event_time' => ['nullable', 'date_format:H:i'],
            'chronologies.*.events' => ['nullable', 'string'],
            'chronologies.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'chronologies.*.temporary_id' => ['nullable', 'uuid'],
            'chronologies.*.local_created_at' => ['nullable', 'date'],

            'victims' => ['nullable', 'array'],
            'victims.*.id' => ['nullable', 'integer', Rule::exists('incident_victims', 'id')],
            'victims.*.victim_type_id' => ['nullable', 'integer', Rule::exists('victim_types', 'id')],
            'victims.*.name' => ['nullable', 'string', 'max:255'],
            'victims.*.identification' => ['nullable', 'string', 'max:255'],
            'victims.*.occupation' => ['nullable', 'string', 'max:255'],
            'victims.*.age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'victims.*.nationality' => ['nullable', 'string', 'max:255'],
            'victims.*.working_experience' => ['nullable', 'string', 'max:255'],
            'victims.*.nature_of_injury' => ['nullable', 'string', 'max:255'],
            'victims.*.body_injured' => ['nullable', 'string', 'max:255'],
            'victims.*.treatment' => ['nullable', 'string'],
            'victims.*.temporary_id' => ['nullable', 'uuid'],
            'victims.*.local_created_at' => ['nullable', 'date'],

            'witnesses' => ['nullable', 'array'],
            'witnesses.*.id' => ['nullable', 'integer', Rule::exists('incident_witnesses', 'id')],
            'witnesses.*.name' => ['nullable', 'string', 'max:255'],
            'witnesses.*.designation' => ['nullable', 'string', 'max:255'],
            'witnesses.*.identification' => ['nullable', 'string', 'max:255'],
            'witnesses.*.temporary_id' => ['nullable', 'uuid'],
            'witnesses.*.local_created_at' => ['nullable', 'date'],

            'investigation_team_members' => ['nullable', 'array'],
            'investigation_team_members.*.id' => ['nullable', 'integer', Rule::exists('incident_investigation_team_members', 'id')],
            'investigation_team_members.*.name' => ['nullable', 'string', 'max:255'],
            'investigation_team_members.*.designation' => ['nullable', 'string', 'max:255'],
            'investigation_team_members.*.contact_number' => ['nullable', 'string', 'max:50'],
            'investigation_team_members.*.company' => ['nullable', 'string', 'max:255'],
            'investigation_team_members.*.temporary_id' => ['nullable', 'uuid'],
            'investigation_team_members.*.local_created_at' => ['nullable', 'date'],

            'damages' => ['nullable', 'array'],
            'damages.*.id' => ['nullable', 'integer', Rule::exists('incident_damages', 'id')],
            'damages.*.damage_type_id' => ['nullable', 'integer', Rule::exists('damage_types', 'id')],
            'damages.*.estimate_cost' => ['nullable', 'numeric', 'min:0'],
            'damages.*.temporary_id' => ['nullable', 'uuid'],
            'damages.*.local_created_at' => ['nullable', 'date'],

            'immediate_actions' => ['nullable', 'array'],
            'immediate_actions.*.id' => ['nullable', 'integer', Rule::exists('incident_immediate_actions', 'id')],
            'immediate_actions.*.action_taken' => ['nullable', 'string'],
            'immediate_actions.*.company' => ['nullable', 'string', 'max:255'],
            'immediate_actions.*.temporary_id' => ['nullable', 'uuid'],
            'immediate_actions.*.local_created_at' => ['nullable', 'date'],

            'planned_actions' => ['nullable', 'array'],
            'planned_actions.*.id' => ['nullable', 'integer', Rule::exists('incident_planned_actions', 'id')],
            'planned_actions.*.action_taken' => ['nullable', 'string'],
            'planned_actions.*.expected_date' => ['nullable', 'date'],
            'planned_actions.*.actual_date' => ['nullable', 'date'],
            'planned_actions.*.temporary_id' => ['nullable', 'uuid'],
            'planned_actions.*.local_created_at' => ['nullable', 'date'],

            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*.id' => ['nullable', 'integer', Rule::exists('incident_attachments', 'id')],
            'attachments.*.attachment_type_id' => ['nullable', 'integer', Rule::exists('attachment_types', 'id')],
            'attachments.*.attachment_category_id' => ['nullable', 'integer', Rule::exists('attachment_categories', 'id')],
            'attachments.*.filename' => ['nullable', 'string', 'max:255'],
            'attachments.*.path' => ['nullable', 'string', 'max:2048'],
            'attachments.*.description' => ['nullable', 'string'],
            'attachments.*.file' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,mp4,mov'],
            'attachments.*.temporary_id' => ['nullable', 'uuid'],
            'attachments.*.local_created_at' => ['nullable', 'date'],
            'remove_attachment_ids' => ['nullable', 'array'],
            'remove_attachment_ids.*' => ['integer', Rule::exists('incident_attachments', 'id')],

            'immediate_cause_ids' => ['nullable', 'array'],
            'immediate_cause_ids.*' => ['integer', Rule::exists('cause_types', 'id')],
            'contributing_factor_ids' => ['nullable', 'array'],
            'contributing_factor_ids.*' => ['integer', Rule::exists('factor_types', 'id')],
            'work_activity_ids' => ['nullable', 'array'],
            'work_activity_ids.*' => ['integer', Rule::exists('work_activities', 'id')],
            'external_party_ids' => ['nullable', 'array'],
            'external_party_ids.*' => ['integer', Rule::exists('external_parties', 'id')],
        ];
    }

    private static function field(bool $partial, bool $required, array $rules): array
    {
        $prefix = [];

        if ($partial) {
            $prefix[] = 'sometimes';
        } else {
            $prefix[] = $required ? 'required' : 'nullable';
        }

        if (! $required) {
            $prefix[] = 'nullable';
        }

        return array_values(array_unique(array_merge($prefix, $rules)));
    }
}
