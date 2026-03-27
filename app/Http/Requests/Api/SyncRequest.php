<?php

namespace App\Http\Requests\Api;

class SyncRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id'                               => ['required', 'string', 'max:255'],
            'last_synced_at'                          => ['nullable', 'date'],
            'conflict_strategy'                       => ['nullable', 'string', 'in:last_updated_wins,manual_review'],
            'data'                                    => ['nullable', 'array'],

            // Incidents
            'data.incidents'                          => ['nullable', 'array'],
            'data.incidents.*.temporary_id'           => ['nullable', 'uuid'],
            'data.incidents.*.id'                     => ['nullable', 'integer'],
            'data.incidents.*.title'                  => ['nullable', 'string', 'max:255'],
            'data.incidents.*.description'            => ['nullable', 'string'],
            'data.incidents.*.location'               => ['nullable', 'string', 'max:255'],
            'data.incidents.*.datetime'               => ['nullable', 'date'],
            'data.incidents.*.classification'         => ['nullable', 'string', 'in:Minor,Moderate,Major,Critical'],
            'data.incidents.*.local_created_at'       => ['nullable', 'date'],
            'data.incidents.*.updated_at'             => ['nullable', 'date'],
            'data.incidents.*.deleted_at'             => ['nullable', 'date'],

            // Attendance logs
            'data.attendance_logs'                    => ['nullable', 'array'],
            'data.attendance_logs.*.temporary_id'     => ['nullable', 'uuid'],
            'data.attendance_logs.*.id'               => ['nullable', 'integer'],
            'data.attendance_logs.*.worker_id'        => ['nullable', 'integer'],
            'data.attendance_logs.*.event_type'       => ['nullable', 'string', 'in:check_in,check_out,ping,break'],
            'data.attendance_logs.*.logged_at'        => ['nullable', 'date'],
            'data.attendance_logs.*.latitude'         => ['nullable', 'numeric', 'between:-90,90'],
            'data.attendance_logs.*.longitude'        => ['nullable', 'numeric', 'between:-180,180'],
            'data.attendance_logs.*.accuracy_meters'  => ['nullable', 'numeric', 'min:0'],
            'data.attendance_logs.*.local_created_at' => ['nullable', 'date'],
            'data.attendance_logs.*.updated_at'       => ['nullable', 'date'],
            'data.attendance_logs.*.deleted_at'       => ['nullable', 'date'],

            // Workers
            'data.workers'                            => ['nullable', 'array'],
            'data.workers.*.id'                       => ['nullable', 'integer'],
            'data.workers.*.user_id'                  => ['nullable', 'integer'],
            'data.workers.*.employee_code'            => ['nullable', 'string', 'max:100'],
            'data.workers.*.full_name'                => ['nullable', 'string', 'max:255'],
            'data.workers.*.phone'                    => ['nullable', 'string', 'max:30'],
            'data.workers.*.department'               => ['nullable', 'string', 'max:100'],
            'data.workers.*.position'                 => ['nullable', 'string', 'max:100'],
            'data.workers.*.status'                   => ['nullable', 'string'],
            'data.workers.*.geofence_center_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'data.workers.*.geofence_center_longitude'=> ['nullable', 'numeric', 'between:-180,180'],
            'data.workers.*.geofence_radius_meters'   => ['nullable', 'integer', 'min:0'],
            'data.workers.*.last_latitude'            => ['nullable', 'numeric', 'between:-90,90'],
            'data.workers.*.last_longitude'           => ['nullable', 'numeric', 'between:-180,180'],
            'data.workers.*.last_seen_at'             => ['nullable', 'date'],
            'data.workers.*.updated_at'               => ['nullable', 'date'],
            'data.workers.*.deleted_at'               => ['nullable', 'date'],

            // Site audits
            'data.site_audits'                        => ['nullable', 'array'],
            'data.site_audits.*.id'                   => ['nullable', 'integer'],
            'data.site_audits.*.reference_no'         => ['nullable', 'string', 'max:255'],
            'data.site_audits.*.site_name'            => ['nullable', 'string', 'max:255'],
            'data.site_audits.*.area'                 => ['nullable', 'string', 'max:255'],
            'data.site_audits.*.audit_type'           => ['nullable', 'string'],
            'data.site_audits.*.scheduled_for'        => ['nullable', 'date'],
            'data.site_audits.*.conducted_at'         => ['nullable', 'date'],
            'data.site_audits.*.status'               => ['nullable', 'string'],
            'data.site_audits.*.scope'                => ['nullable', 'string'],
            'data.site_audits.*.summary'              => ['nullable', 'string'],
            'data.site_audits.*.updated_at'           => ['nullable', 'date'],
            'data.site_audits.*.deleted_at'           => ['nullable', 'date'],

            // NCR reports
            'data.ncr_reports'                        => ['nullable', 'array'],
            'data.ncr_reports.*.id'                   => ['nullable', 'integer'],
            'data.ncr_reports.*.site_audit_id'        => ['nullable', 'integer'],
            'data.ncr_reports.*.owner_id'             => ['nullable', 'integer'],
            'data.ncr_reports.*.reference_no'         => ['nullable', 'string', 'max:255'],
            'data.ncr_reports.*.title'                => ['nullable', 'string', 'max:255'],
            'data.ncr_reports.*.description'          => ['nullable', 'string'],
            'data.ncr_reports.*.severity'             => ['nullable', 'string'],
            'data.ncr_reports.*.status'               => ['nullable', 'string'],
            'data.ncr_reports.*.root_cause'           => ['nullable', 'string'],
            'data.ncr_reports.*.containment_action'   => ['nullable', 'string'],
            'data.ncr_reports.*.corrective_action_plan' => ['nullable', 'string'],
            'data.ncr_reports.*.due_date'             => ['nullable', 'date'],
            'data.ncr_reports.*.updated_at'           => ['nullable', 'date'],
            'data.ncr_reports.*.deleted_at'           => ['nullable', 'date'],

            // Inspections (reference existing offline sync fields)
            'data.inspections'                        => ['nullable', 'array'],
            'data.inspections.*.offline_uuid'         => ['nullable', 'uuid'],
            'data.inspections.*.id'                   => ['nullable', 'integer'],
            'data.inspections.*.inspection_checklist_id' => ['nullable', 'integer'],
            'data.inspections.*.status'               => ['nullable', 'string'],
            'data.inspections.*.location'             => ['nullable', 'string', 'max:255'],
            'data.inspections.*.performed_at'         => ['nullable', 'date'],
            'data.inspections.*.submitted_at'         => ['nullable', 'date'],
            'data.inspections.*.notes'                => ['nullable', 'string'],
            'data.inspections.*.device_identifier'    => ['nullable', 'string', 'max:255'],
            'data.inspections.*.offline_reference'    => ['nullable', 'string', 'max:255'],
            'data.inspections.*.sync_status'          => ['nullable', 'string'],
            'data.inspections.*.updated_at'           => ['nullable', 'date'],
            'data.inspections.*.deleted_at'           => ['nullable', 'date'],
        ];
    }
}
