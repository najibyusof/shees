<?php

namespace App\Services\Api;

use App\Models\AttendanceLog;
use App\Models\ConflictLog;
use App\Models\Incident;
use App\Models\Inspection;
use App\Models\NcrReport;
use App\Models\SiteAudit;
use App\Models\Training;
use App\Models\User;
use App\Models\Worker;
use App\Services\AuditLogService;
use App\Services\IncidentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncService
{
    private const CONFLICT_LAST_UPDATED_WINS = 'last_updated_wins';

    private const CONFLICT_MANUAL_REVIEW = 'manual_review';

    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly IncidentService $incidentService,
    )
    {
    }

    /**
     * Process a device sync payload.  All writes happen inside one transaction.
     * Returns updated server-side data since the device's last sync point.
     */
    public function sync(
        int $userId,
        string $deviceId,
        ?string $lastSyncedAt,
        string $conflictStrategy,
        array $data
    ): array
    {
        $serverTime = now();
        $syncErrors = [];
        $conflicts = [];
        $lastSyncedAtCarbon = $lastSyncedAt ? Carbon::parse($lastSyncedAt) : null;

        DB::transaction(function () use ($userId, $deviceId, $data, $conflictStrategy, $lastSyncedAtCarbon, &$syncErrors, &$conflicts) {
            foreach ($data['incidents'] ?? [] as $item) {
                try {
                    $this->syncIncident($userId, $deviceId, $item, $lastSyncedAtCarbon, $conflictStrategy, $conflicts);
                } catch (\Throwable $e) {
                    $syncErrors[] = [
                        'entity'       => 'incident',
                        'temporary_id' => $item['temporary_id'] ?? null,
                        'error'        => $e->getMessage(),
                    ];
                    Log::warning('SyncService: incident sync failed', [
                        'error' => $e->getMessage(),
                        'item'  => $item,
                    ]);
                }
            }

            foreach ($data['workers'] ?? [] as $item) {
                try {
                    $this->syncWorker($userId, $deviceId, $item, $lastSyncedAtCarbon, $conflictStrategy, $conflicts);
                } catch (\Throwable $e) {
                    $syncErrors[] = [
                        'entity' => 'worker',
                        'id' => $item['id'] ?? null,
                        'error' => $e->getMessage(),
                    ];
                    Log::warning('SyncService: worker sync failed', [
                        'error' => $e->getMessage(),
                        'item' => $item,
                    ]);
                }
            }

            foreach ($data['site_audits'] ?? [] as $item) {
                try {
                    $this->syncSiteAudit($userId, $deviceId, $item, $lastSyncedAtCarbon, $conflictStrategy, $conflicts);
                } catch (\Throwable $e) {
                    $syncErrors[] = [
                        'entity' => 'site_audit',
                        'id' => $item['id'] ?? null,
                        'error' => $e->getMessage(),
                    ];
                    Log::warning('SyncService: site_audit sync failed', [
                        'error' => $e->getMessage(),
                        'item' => $item,
                    ]);
                }
            }

            foreach ($data['ncr_reports'] ?? [] as $item) {
                try {
                    $this->syncNcrReport($userId, $deviceId, $item, $lastSyncedAtCarbon, $conflictStrategy, $conflicts);
                } catch (\Throwable $e) {
                    $syncErrors[] = [
                        'entity' => 'ncr_report',
                        'id' => $item['id'] ?? null,
                        'error' => $e->getMessage(),
                    ];
                    Log::warning('SyncService: ncr_report sync failed', [
                        'error' => $e->getMessage(),
                        'item' => $item,
                    ]);
                }
            }

            foreach ($data['attendance_logs'] ?? [] as $item) {
                try {
                    $this->syncAttendanceLog($userId, $deviceId, $item, $lastSyncedAtCarbon, $conflictStrategy, $conflicts);
                } catch (\Throwable $e) {
                    $syncErrors[] = [
                        'entity'       => 'attendance_log',
                        'temporary_id' => $item['temporary_id'] ?? null,
                        'error'        => $e->getMessage(),
                    ];
                    Log::warning('SyncService: attendance_log sync failed', [
                        'error' => $e->getMessage(),
                        'item'  => $item,
                    ]);
                }
            }

            foreach ($data['inspections'] ?? [] as $item) {
                try {
                    $this->syncInspection($userId, $deviceId, $item, $lastSyncedAtCarbon, $conflictStrategy, $conflicts);
                } catch (\Throwable $e) {
                    $syncErrors[] = [
                        'entity' => 'inspection',
                        'offline_uuid' => $item['offline_uuid'] ?? null,
                        'error' => $e->getMessage(),
                    ];
                    Log::warning('SyncService: inspection sync failed', [
                        'error' => $e->getMessage(),
                        'item' => $item,
                    ]);
                }
            }
        });

        // Log the sync activity
        $this->auditLogService->log(
            userId: $userId,
            action: 'mobile_sync',
            module: 'sync',
            auditable: null,
            metadata: [
                'device_id'     => $deviceId,
                'entity_counts' => [
                    'incidents'      => count($data['incidents'] ?? []),
                    'workers'        => count($data['workers'] ?? []),
                    'site_audits'    => count($data['site_audits'] ?? []),
                    'ncr_reports'    => count($data['ncr_reports'] ?? []),
                    'attendance_logs'=> count($data['attendance_logs'] ?? []),
                    'inspections'    => count($data['inspections'] ?? []),
                ],
                'error_count'   => count($syncErrors),
                'conflict_count' => count($conflicts),
            ]
        );

        // Determine the pull-since window: default to 7 days when never synced
        $since = $lastSyncedAt
            ? Carbon::parse($lastSyncedAt)->subMinutes(5) // 5-min overlap for clock skew
            : now()->subDays(7);

        return [
            'server_time'  => $serverTime->toIso8601String(),
            'updated_data' => [
                'incidents'      => Incident::withTrashed()
                    ->where('updated_at', '>=', $since)
                    ->with([
                        'reporter',
                        'incidentType',
                        'incidentStatus',
                        'incidentClassification',
                        'reclassification',
                        'incidentLocation.locationType',
                        'workPackage',
                        'subcontractor',
                        'rootCause',
                        'attachments.attachmentType',
                        'attachments.attachmentCategory',
                        'chronologies',
                        'victims.victimType',
                        'witnesses',
                        'investigationTeamMembers',
                        'damages.damageType',
                        'immediateActions',
                        'plannedActions',
                        'comments.user',
                        'comments.replies.user',
                        'activities.user',
                        'approvals.approver',
                        'immediateCauses',
                        'contributingFactors',
                        'workActivities',
                        'externalParties',
                    ])
                    ->get()
                    ->map(fn ($i) => $this->incidentToArray($i))
                    ->values(),

                'trainings'      => Training::withTrashed()
                    ->where('updated_at', '>=', $since)
                    ->get()
                    ->map(fn ($t) => $this->trainingToArray($t))
                    ->values(),

                'users'          => User::query()
                    ->where('updated_at', '>=', $since)
                    ->with('roles.permissions')
                    ->get()
                    ->map(fn ($u) => $this->userToArray($u))
                    ->values(),

                'workers'        => Worker::withTrashed()
                    ->where('updated_at', '>=', $since)
                    ->get()
                    ->map(fn ($w) => $this->workerToArray($w))
                    ->values(),

                'site_audits'    => SiteAudit::withTrashed()
                    ->where('updated_at', '>=', $since)
                    ->get()
                    ->map(fn ($a) => $this->siteAuditToArray($a))
                    ->values(),

                'ncr_reports'    => NcrReport::withTrashed()
                    ->where('updated_at', '>=', $since)
                    ->get()
                    ->map(fn ($n) => $this->ncrReportToArray($n))
                    ->values(),

                'attendance_logs'=> AttendanceLog::withTrashed()
                    ->where('updated_at', '>=', $since)
                    ->get()
                    ->map(fn ($a) => $this->attendanceLogToArray($a))
                    ->values(),

                'inspections'    => Inspection::withTrashed()
                    ->where('updated_at', '>=', $since)
                    ->get()
                    ->map(fn ($i) => $this->inspectionToArray($i))
                    ->values(),
            ],
            'sync_errors'  => $syncErrors,
            'conflict_strategy' => $conflictStrategy,
            'conflicts' => $conflicts,
        ];
    }

    // -------------------------------------------------------------------------
    // Private entity sync handlers
    // -------------------------------------------------------------------------

    private function syncIncident(
        int $userId,
        string $deviceId,
        array $item,
        ?Carbon $lastSyncedAt,
        string $conflictStrategy,
        array &$conflicts
    ): void
    {
        $user = User::query()->findOrFail($userId);
        $incident = null;

        // 1. Try to find by client temporary_id
        if (! empty($item['temporary_id'])) {
            $incident = Incident::withTrashed()
                ->where('temporary_id', $item['temporary_id'])
                ->first();
        }

        // 2. Fallback to server id
        if (! $incident && ! empty($item['id'])) {
            $incident = Incident::withTrashed()->find($item['id']);
        }

        // Handle client-side soft delete
        if (! empty($item['deleted_at']) && $incident) {
            $incident->delete();
            return;
        }

        if ($incident && $this->detectConflict($incident->updated_at, $item, $lastSyncedAt)) {
            $resolution = $this->resolveConflict(
                userId: $userId,
                deviceId: $deviceId,
                module: 'incidents',
                recordId: (string) ($incident->temporary_id ?? $incident->id),
                serverUpdatedAt: $incident->updated_at,
                localUpdatedAt: Carbon::parse($item['updated_at']),
                conflictStrategy: $conflictStrategy,
                payload: $item,
                conflicts: $conflicts,
            );

            if ($resolution === self::CONFLICT_MANUAL_REVIEW || $resolution === 'server') {
                return;
            }
        }

        if ($incident === null) {
            $this->incidentService->create(
                $this->normalizeIncidentSyncPayload($item),
                $item['attachments'] ?? [],
                $user,
            );
        } else {
            if ($this->shouldApplyClientUpdate($item, $incident->updated_at)) {
                $this->incidentService->update(
                    $incident,
                    $this->normalizeIncidentSyncPayload($item),
                    $item['attachments'] ?? [],
                    $user,
                    $item['remove_attachment_ids'] ?? [],
                );
            }
        }
    }

    private function normalizeIncidentSyncPayload(array $item): array
    {
        return array_filter([
            'incident_reference_number' => $item['incident_reference_number'] ?? null,
            'title' => $item['title'] ?? 'Untitled (offline)',
            'incident_type_id' => $item['incident_type_id'] ?? null,
            'incident_date' => $item['incident_date'] ?? ($item['datetime'] ?? null),
            'incident_time' => $item['incident_time'] ?? ($item['datetime'] ?? null),
            'status_id' => $item['status_id'] ?? null,
            'status' => $item['status'] ?? null,
            'work_package_id' => $item['work_package_id'] ?? null,
            'location_id' => $item['location_id'] ?? null,
            'other_location' => $item['other_location'] ?? ($item['location'] ?? null),
            'classification_id' => $item['classification_id'] ?? null,
            'classification' => $item['classification'] ?? null,
            'reclassification_id' => $item['reclassification_id'] ?? null,
            'incident_description' => $item['incident_description'] ?? ($item['description'] ?? null),
            'immediate_response' => $item['immediate_response'] ?? null,
            'subcontractor_id' => $item['subcontractor_id'] ?? null,
            'person_in_charge' => $item['person_in_charge'] ?? null,
            'subcontractor_contact_number' => $item['subcontractor_contact_number'] ?? null,
            'gps_location' => $item['gps_location'] ?? null,
            'activity_during_incident' => $item['activity_during_incident'] ?? null,
            'type_of_accident' => $item['type_of_accident'] ?? null,
            'basic_effect' => $item['basic_effect'] ?? null,
            'conclusion' => $item['conclusion'] ?? null,
            'close_remark' => $item['close_remark'] ?? null,
            'rootcause_id' => $item['rootcause_id'] ?? null,
            'other_rootcause' => $item['other_rootcause'] ?? null,
            'chronologies' => $item['chronologies'] ?? null,
            'victims' => $item['victims'] ?? null,
            'witnesses' => $item['witnesses'] ?? null,
            'investigation_team_members' => $item['investigation_team_members'] ?? null,
            'damages' => $item['damages'] ?? null,
            'immediate_actions' => $item['immediate_actions'] ?? null,
            'planned_actions' => $item['planned_actions'] ?? null,
            'immediate_cause_ids' => $item['immediate_cause_ids'] ?? null,
            'contributing_factor_ids' => $item['contributing_factor_ids'] ?? null,
            'work_activity_ids' => $item['work_activity_ids'] ?? null,
            'external_party_ids' => $item['external_party_ids'] ?? null,
            'temporary_id' => $item['temporary_id'] ?? null,
            'local_created_at' => isset($item['local_created_at']) ? Carbon::parse($item['local_created_at']) : null,
        ], static fn ($value) => $value !== null);
    }

    private function syncAttendanceLog(
        int $userId,
        string $deviceId,
        array $item,
        ?Carbon $lastSyncedAt,
        string $conflictStrategy,
        array &$conflicts
    ): void
    {
        $log = null;

        if (! empty($item['temporary_id'])) {
            $log = AttendanceLog::withTrashed()
                ->where('temporary_id', $item['temporary_id'])
                ->first();
        }

        if (! $log && ! empty($item['id'])) {
            $log = AttendanceLog::withTrashed()->find($item['id']);
        }

        if (! empty($item['deleted_at']) && $log) {
            $log->delete();
            return;
        }

        if ($log && $this->detectConflict($log->updated_at, $item, $lastSyncedAt)) {
            $resolution = $this->resolveConflict(
                userId: $userId,
                deviceId: $deviceId,
                module: 'attendance_logs',
                recordId: (string) ($log->temporary_id ?? $log->id),
                serverUpdatedAt: $log->updated_at,
                localUpdatedAt: Carbon::parse($item['updated_at']),
                conflictStrategy: $conflictStrategy,
                payload: $item,
                conflicts: $conflicts,
            );

            if ($resolution === self::CONFLICT_MANUAL_REVIEW || $resolution === 'server') {
                return;
            }
        }

        if ($log === null) {
            AttendanceLog::create([
                'worker_id'         => $item['worker_id'],
                'recorded_by'       => $userId,
                'event_type'        => $item['event_type'] ?? 'ping',
                'logged_at'         => isset($item['logged_at'])
                    ? Carbon::parse($item['logged_at'])
                    : now(),
                'latitude'          => $item['latitude'] ?? null,
                'longitude'         => $item['longitude'] ?? null,
                'accuracy_meters'   => $item['accuracy_meters'] ?? null,
                'source'            => 'offline_sync',
                'device_identifier' => $deviceId,
                'temporary_id'      => $item['temporary_id'] ?? null,
                'local_created_at'  => isset($item['local_created_at'])
                    ? Carbon::parse($item['local_created_at'])
                    : null,
            ]);
        } else {
            if ($this->shouldApplyClientUpdate($item, $log->updated_at)) {
                $log->update([
                    'event_type' => $item['event_type'] ?? $log->event_type,
                    'logged_at'  => isset($item['logged_at'])
                        ? Carbon::parse($item['logged_at'])
                        : $log->logged_at,
                    'latitude'   => $item['latitude'] ?? $log->latitude,
                    'longitude'  => $item['longitude'] ?? $log->longitude,
                ]);
            }
        }
    }

    private function syncWorker(
        int $userId,
        string $deviceId,
        array $item,
        ?Carbon $lastSyncedAt,
        string $conflictStrategy,
        array &$conflicts
    ): void
    {
        $worker = ! empty($item['id']) ? Worker::withTrashed()->find($item['id']) : null;

        if (! empty($item['deleted_at']) && $worker) {
            $worker->delete();
            return;
        }

        if ($worker && $this->detectConflict($worker->updated_at, $item, $lastSyncedAt)) {
            $resolution = $this->resolveConflict(
                userId: $userId,
                deviceId: $deviceId,
                module: 'workers',
                recordId: (string) $worker->id,
                serverUpdatedAt: $worker->updated_at,
                localUpdatedAt: Carbon::parse($item['updated_at']),
                conflictStrategy: $conflictStrategy,
                payload: $item,
                conflicts: $conflicts,
            );

            if ($resolution === self::CONFLICT_MANUAL_REVIEW || $resolution === 'server') {
                return;
            }
        }

        if ($worker === null) {
            Worker::create([
                'user_id' => $item['user_id'] ?? null,
                'employee_code' => $item['employee_code'],
                'full_name' => $item['full_name'],
                'phone' => $item['phone'] ?? null,
                'department' => $item['department'] ?? null,
                'position' => $item['position'] ?? null,
                'status' => $item['status'] ?? 'active',
                'geofence_center_latitude' => $item['geofence_center_latitude'] ?? null,
                'geofence_center_longitude' => $item['geofence_center_longitude'] ?? null,
                'geofence_radius_meters' => $item['geofence_radius_meters'] ?? 100,
            ]);

            return;
        }

        if ($this->shouldApplyClientUpdate($item, $worker->updated_at)) {
            $worker->update([
                'full_name' => $item['full_name'] ?? $worker->full_name,
                'phone' => $item['phone'] ?? $worker->phone,
                'department' => $item['department'] ?? $worker->department,
                'position' => $item['position'] ?? $worker->position,
                'status' => $item['status'] ?? $worker->status,
                'last_latitude' => $item['last_latitude'] ?? $worker->last_latitude,
                'last_longitude' => $item['last_longitude'] ?? $worker->last_longitude,
                'last_seen_at' => isset($item['last_seen_at']) ? Carbon::parse($item['last_seen_at']) : $worker->last_seen_at,
            ]);
        }
    }

    private function syncSiteAudit(
        int $userId,
        string $deviceId,
        array $item,
        ?Carbon $lastSyncedAt,
        string $conflictStrategy,
        array &$conflicts
    ): void
    {
        $audit = ! empty($item['id']) ? SiteAudit::withTrashed()->find($item['id']) : null;

        if (! empty($item['deleted_at']) && $audit) {
            $audit->delete();
            return;
        }

        if ($audit && $this->detectConflict($audit->updated_at, $item, $lastSyncedAt)) {
            $resolution = $this->resolveConflict(
                userId: $userId,
                deviceId: $deviceId,
                module: 'site_audits',
                recordId: (string) $audit->id,
                serverUpdatedAt: $audit->updated_at,
                localUpdatedAt: Carbon::parse($item['updated_at']),
                conflictStrategy: $conflictStrategy,
                payload: $item,
                conflicts: $conflicts,
            );

            if ($resolution === self::CONFLICT_MANUAL_REVIEW || $resolution === 'server') {
                return;
            }
        }

        if ($audit === null) {
            SiteAudit::create([
                'created_by' => $userId,
                'reference_no' => $item['reference_no'] ?? ('MOB-AUD-'.now()->format('YmdHis')),
                'site_name' => $item['site_name'] ?? 'Offline audit',
                'area' => $item['area'] ?? null,
                'audit_type' => $item['audit_type'] ?? 'internal',
                'scheduled_for' => isset($item['scheduled_for']) ? Carbon::parse($item['scheduled_for']) : null,
                'conducted_at' => isset($item['conducted_at']) ? Carbon::parse($item['conducted_at']) : null,
                'status' => $item['status'] ?? 'draft',
                'scope' => $item['scope'] ?? null,
                'summary' => $item['summary'] ?? null,
            ]);

            return;
        }

        if ($this->shouldApplyClientUpdate($item, $audit->updated_at)) {
            $audit->update([
                'site_name' => $item['site_name'] ?? $audit->site_name,
                'area' => $item['area'] ?? $audit->area,
                'audit_type' => $item['audit_type'] ?? $audit->audit_type,
                'scheduled_for' => isset($item['scheduled_for']) ? Carbon::parse($item['scheduled_for']) : $audit->scheduled_for,
                'conducted_at' => isset($item['conducted_at']) ? Carbon::parse($item['conducted_at']) : $audit->conducted_at,
                'status' => $item['status'] ?? $audit->status,
                'scope' => $item['scope'] ?? $audit->scope,
                'summary' => $item['summary'] ?? $audit->summary,
            ]);
        }
    }

    private function syncNcrReport(
        int $userId,
        string $deviceId,
        array $item,
        ?Carbon $lastSyncedAt,
        string $conflictStrategy,
        array &$conflicts
    ): void
    {
        $report = ! empty($item['id']) ? NcrReport::withTrashed()->find($item['id']) : null;

        if (! empty($item['deleted_at']) && $report) {
            $report->delete();
            return;
        }

        if ($report && $this->detectConflict($report->updated_at, $item, $lastSyncedAt)) {
            $resolution = $this->resolveConflict(
                userId: $userId,
                deviceId: $deviceId,
                module: 'ncr_reports',
                recordId: (string) $report->id,
                serverUpdatedAt: $report->updated_at,
                localUpdatedAt: Carbon::parse($item['updated_at']),
                conflictStrategy: $conflictStrategy,
                payload: $item,
                conflicts: $conflicts,
            );

            if ($resolution === self::CONFLICT_MANUAL_REVIEW || $resolution === 'server') {
                return;
            }
        }

        if ($report === null) {
            NcrReport::create([
                'site_audit_id' => $item['site_audit_id'],
                'reported_by' => $userId,
                'owner_id' => $item['owner_id'] ?? null,
                'reference_no' => $item['reference_no'] ?? ('MOB-NCR-'.now()->format('YmdHis')),
                'title' => $item['title'] ?? 'Offline NCR',
                'description' => $item['description'] ?? null,
                'severity' => $item['severity'] ?? 'medium',
                'status' => $item['status'] ?? 'open',
                'root_cause' => $item['root_cause'] ?? null,
                'containment_action' => $item['containment_action'] ?? null,
                'corrective_action_plan' => $item['corrective_action_plan'] ?? null,
                'due_date' => isset($item['due_date']) ? Carbon::parse($item['due_date']) : null,
            ]);

            return;
        }

        if ($this->shouldApplyClientUpdate($item, $report->updated_at)) {
            $report->update([
                'owner_id' => $item['owner_id'] ?? $report->owner_id,
                'title' => $item['title'] ?? $report->title,
                'description' => $item['description'] ?? $report->description,
                'severity' => $item['severity'] ?? $report->severity,
                'status' => $item['status'] ?? $report->status,
                'root_cause' => $item['root_cause'] ?? $report->root_cause,
                'containment_action' => $item['containment_action'] ?? $report->containment_action,
                'corrective_action_plan' => $item['corrective_action_plan'] ?? $report->corrective_action_plan,
                'due_date' => isset($item['due_date']) ? Carbon::parse($item['due_date']) : $report->due_date,
            ]);
        }
    }

    private function syncInspection(
        int $userId,
        string $deviceId,
        array $item,
        ?Carbon $lastSyncedAt,
        string $conflictStrategy,
        array &$conflicts
    ): void
    {
        $inspection = null;

        if (! empty($item['offline_uuid'])) {
            $inspection = Inspection::withTrashed()->where('offline_uuid', $item['offline_uuid'])->first();
        }

        if (! $inspection && ! empty($item['id'])) {
            $inspection = Inspection::withTrashed()->find($item['id']);
        }

        if (! empty($item['deleted_at']) && $inspection) {
            $inspection->delete();
            return;
        }

        if ($inspection && $this->detectConflict($inspection->updated_at, $item, $lastSyncedAt)) {
            $resolution = $this->resolveConflict(
                userId: $userId,
                deviceId: $deviceId,
                module: 'inspections',
                recordId: (string) ($inspection->offline_uuid ?? $inspection->id),
                serverUpdatedAt: $inspection->updated_at,
                localUpdatedAt: Carbon::parse($item['updated_at']),
                conflictStrategy: $conflictStrategy,
                payload: $item,
                conflicts: $conflicts,
            );

            if ($resolution === self::CONFLICT_MANUAL_REVIEW || $resolution === 'server') {
                return;
            }
        }

        if ($inspection === null) {
            Inspection::create([
                'offline_uuid' => $item['offline_uuid'],
                'inspection_checklist_id' => $item['inspection_checklist_id'],
                'inspector_id' => $userId,
                'status' => $item['status'] ?? 'draft',
                'location' => $item['location'] ?? null,
                'performed_at' => isset($item['performed_at']) ? Carbon::parse($item['performed_at']) : null,
                'submitted_at' => isset($item['submitted_at']) ? Carbon::parse($item['submitted_at']) : null,
                'notes' => $item['notes'] ?? null,
                'device_identifier' => $item['device_identifier'] ?? null,
                'offline_reference' => $item['offline_reference'] ?? null,
                'sync_status' => $item['sync_status'] ?? 'synced',
                'last_synced_at' => now(),
            ]);

            return;
        }

        if ($this->shouldApplyClientUpdate($item, $inspection->updated_at)) {
            $inspection->update([
                'status' => $item['status'] ?? $inspection->status,
                'location' => $item['location'] ?? $inspection->location,
                'performed_at' => isset($item['performed_at']) ? Carbon::parse($item['performed_at']) : $inspection->performed_at,
                'submitted_at' => isset($item['submitted_at']) ? Carbon::parse($item['submitted_at']) : $inspection->submitted_at,
                'notes' => $item['notes'] ?? $inspection->notes,
                'device_identifier' => $item['device_identifier'] ?? $inspection->device_identifier,
                'offline_reference' => $item['offline_reference'] ?? $inspection->offline_reference,
                'sync_status' => $item['sync_status'] ?? $inspection->sync_status,
                'last_synced_at' => now(),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Private array transformers (keep payload small)
    // -------------------------------------------------------------------------

    private function incidentToArray(Incident $incident): array
    {
        return [
            'id'               => $incident->id,
            'temporary_id'     => $incident->temporary_id,
            'incident_reference_number' => $incident->incident_reference_number,
            'title'            => $incident->title,
            'incident_type_id' => $incident->incident_type_id,
            'description'      => $incident->incident_description ?? $incident->description,
            'location'         => $incident->location,
            'location_id'      => $incident->location_id,
            'other_location'   => $incident->other_location,
            'datetime'         => $incident->datetime?->toIso8601String(),
            'incident_date'    => $incident->incident_date?->toDateString(),
            'incident_time'    => $incident->incident_time?->format('H:i:s'),
            'classification'   => $incident->classification,
            'classification_id' => $incident->classification_id,
            'status_id'        => $incident->status_id,
            'status'           => $incident->status,
            'work_package_id'  => $incident->work_package_id,
            'immediate_response' => $incident->immediate_response,
            'subcontractor_id' => $incident->subcontractor_id,
            'person_in_charge' => $incident->person_in_charge,
            'subcontractor_contact_number' => $incident->subcontractor_contact_number,
            'gps_location' => $incident->gps_location,
            'activity_during_incident' => $incident->activity_during_incident,
            'type_of_accident' => $incident->type_of_accident,
            'basic_effect' => $incident->basic_effect,
            'conclusion' => $incident->conclusion,
            'close_remark' => $incident->close_remark,
            'rootcause_id' => $incident->rootcause_id,
            'other_rootcause' => $incident->other_rootcause,
            'attachments' => $incident->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'attachment_type_id' => $attachment->attachment_type_id,
                'attachment_category_id' => $attachment->attachment_category_id,
                'filename' => $attachment->filename,
                'path' => $attachment->path,
                'description' => $attachment->description,
                'temporary_id' => $attachment->temporary_id,
                'local_created_at' => $attachment->local_created_at?->toIso8601String(),
                'deleted_at' => $attachment->deleted_at?->toIso8601String(),
                'updated_at' => $attachment->updated_at?->toIso8601String(),
            ])->values(),
            'chronologies' => $incident->chronologies->map(fn ($entry) => [
                'id' => $entry->id,
                'event_date' => $entry->event_date?->toDateString(),
                'event_time' => $entry->event_time?->format('H:i:s'),
                'events' => $entry->events,
                'sort_order' => $entry->sort_order,
                'temporary_id' => $entry->temporary_id,
                'local_created_at' => $entry->local_created_at?->toIso8601String(),
                'deleted_at' => $entry->deleted_at?->toIso8601String(),
                'updated_at' => $entry->updated_at?->toIso8601String(),
            ])->values(),
            'victims' => $incident->victims->map(fn ($victim) => [
                'id' => $victim->id,
                'victim_type_id' => $victim->victim_type_id,
                'name' => $victim->name,
                'identification' => $victim->identification,
                'occupation' => $victim->occupation,
                'age' => $victim->age,
                'nationality' => $victim->nationality,
                'working_experience' => $victim->working_experience,
                'nature_of_injury' => $victim->nature_of_injury,
                'body_injured' => $victim->body_injured,
                'treatment' => $victim->treatment,
                'temporary_id' => $victim->temporary_id,
                'local_created_at' => $victim->local_created_at?->toIso8601String(),
                'deleted_at' => $victim->deleted_at?->toIso8601String(),
                'updated_at' => $victim->updated_at?->toIso8601String(),
            ])->values(),
            'witnesses' => $incident->witnesses->map(fn ($witness) => [
                'id' => $witness->id,
                'name' => $witness->name,
                'designation' => $witness->designation,
                'identification' => $witness->identification,
                'temporary_id' => $witness->temporary_id,
                'local_created_at' => $witness->local_created_at?->toIso8601String(),
                'deleted_at' => $witness->deleted_at?->toIso8601String(),
                'updated_at' => $witness->updated_at?->toIso8601String(),
            ])->values(),
            'investigation_team_members' => $incident->investigationTeamMembers->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
                'designation' => $member->designation,
                'contact_number' => $member->contact_number,
                'company' => $member->company,
                'temporary_id' => $member->temporary_id,
                'local_created_at' => $member->local_created_at?->toIso8601String(),
                'deleted_at' => $member->deleted_at?->toIso8601String(),
                'updated_at' => $member->updated_at?->toIso8601String(),
            ])->values(),
            'damages' => $incident->damages->map(fn ($damage) => [
                'id' => $damage->id,
                'damage_type_id' => $damage->damage_type_id,
                'estimate_cost' => $damage->estimate_cost,
                'temporary_id' => $damage->temporary_id,
                'local_created_at' => $damage->local_created_at?->toIso8601String(),
                'deleted_at' => $damage->deleted_at?->toIso8601String(),
                'updated_at' => $damage->updated_at?->toIso8601String(),
            ])->values(),
            'immediate_actions' => $incident->immediateActions->map(fn ($action) => [
                'id' => $action->id,
                'action_taken' => $action->action_taken,
                'company' => $action->company,
                'temporary_id' => $action->temporary_id,
                'local_created_at' => $action->local_created_at?->toIso8601String(),
                'deleted_at' => $action->deleted_at?->toIso8601String(),
                'updated_at' => $action->updated_at?->toIso8601String(),
            ])->values(),
            'planned_actions' => $incident->plannedActions->map(fn ($action) => [
                'id' => $action->id,
                'action_taken' => $action->action_taken,
                'expected_date' => $action->expected_date?->toDateString(),
                'actual_date' => $action->actual_date?->toDateString(),
                'temporary_id' => $action->temporary_id,
                'local_created_at' => $action->local_created_at?->toIso8601String(),
                'deleted_at' => $action->deleted_at?->toIso8601String(),
                'updated_at' => $action->updated_at?->toIso8601String(),
            ])->values(),
            'immediate_cause_ids' => $incident->immediateCauses->pluck('id')->values(),
            'contributing_factor_ids' => $incident->contributingFactors->pluck('id')->values(),
            'work_activity_ids' => $incident->workActivities->pluck('id')->values(),
            'external_party_ids' => $incident->externalParties->pluck('id')->values(),
            'local_created_at' => $incident->local_created_at?->toIso8601String(),
            'deleted_at'       => $incident->deleted_at?->toIso8601String(),
            'created_at'       => $incident->created_at?->toIso8601String(),
            'updated_at'       => $incident->updated_at?->toIso8601String(),
        ];
    }

    private function trainingToArray(Training $training): array
    {
        return [
            'id'                        => $training->id,
            'title'                     => $training->title,
            'description'               => $training->description,
            'starts_at'                 => $training->starts_at?->toIso8601String(),
            'ends_at'                   => $training->ends_at?->toIso8601String(),
            'certificate_validity_days' => $training->certificate_validity_days,
            'is_active'                 => $training->is_active,
            'deleted_at'                => $training->deleted_at?->toIso8601String(),
            'created_at'                => $training->created_at?->toIso8601String(),
            'updated_at'                => $training->updated_at?->toIso8601String(),
        ];
    }

    private function userToArray(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->map(fn ($role) => $role->slug ?? $role->name)->values(),
            'permissions' => $user->roles
                ->flatMap(fn ($role) => $role->permissions->pluck('name'))
                ->unique()
                ->values(),
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ];
    }

    private function workerToArray(Worker $worker): array
    {
        return [
            'id' => $worker->id,
            'user_id' => $worker->user_id,
            'employee_code' => $worker->employee_code,
            'full_name' => $worker->full_name,
            'phone' => $worker->phone,
            'department' => $worker->department,
            'position' => $worker->position,
            'status' => $worker->status,
            'last_latitude' => $worker->last_latitude,
            'last_longitude' => $worker->last_longitude,
            'last_seen_at' => $worker->last_seen_at?->toIso8601String(),
            'deleted_at' => $worker->deleted_at?->toIso8601String(),
            'created_at' => $worker->created_at?->toIso8601String(),
            'updated_at' => $worker->updated_at?->toIso8601String(),
        ];
    }

    private function siteAuditToArray(SiteAudit $audit): array
    {
        return [
            'id' => $audit->id,
            'reference_no' => $audit->reference_no,
            'site_name' => $audit->site_name,
            'area' => $audit->area,
            'audit_type' => $audit->audit_type,
            'status' => $audit->status,
            'kpi_score' => $audit->kpi_score,
            'scope' => $audit->scope,
            'summary' => $audit->summary,
            'scheduled_for' => $audit->scheduled_for?->toDateString(),
            'conducted_at' => $audit->conducted_at?->toIso8601String(),
            'deleted_at' => $audit->deleted_at?->toIso8601String(),
            'created_at' => $audit->created_at?->toIso8601String(),
            'updated_at' => $audit->updated_at?->toIso8601String(),
        ];
    }

    private function ncrReportToArray(NcrReport $report): array
    {
        return [
            'id' => $report->id,
            'site_audit_id' => $report->site_audit_id,
            'reference_no' => $report->reference_no,
            'title' => $report->title,
            'description' => $report->description,
            'severity' => $report->severity,
            'status' => $report->status,
            'due_date' => $report->due_date?->toDateString(),
            'deleted_at' => $report->deleted_at?->toIso8601String(),
            'created_at' => $report->created_at?->toIso8601String(),
            'updated_at' => $report->updated_at?->toIso8601String(),
        ];
    }

    private function attendanceLogToArray(AttendanceLog $log): array
    {
        return [
            'id'                            => $log->id,
            'temporary_id'                  => $log->temporary_id,
            'worker_id'                     => $log->worker_id,
            'event_type'                    => $log->event_type,
            'logged_at'                     => $log->logged_at?->toIso8601String(),
            'latitude'                      => $log->latitude,
            'longitude'                     => $log->longitude,
            'inside_geofence'               => $log->inside_geofence,
            'distance_from_geofence_meters' => $log->distance_from_geofence_meters,
            'alert_level'                   => $log->alert_level,
            'local_created_at'              => $log->local_created_at?->toIso8601String(),
            'deleted_at'                    => $log->deleted_at?->toIso8601String(),
            'created_at'                    => $log->created_at?->toIso8601String(),
            'updated_at'                    => $log->updated_at?->toIso8601String(),
        ];
    }

    private function inspectionToArray(Inspection $inspection): array
    {
        return [
            'id' => $inspection->id,
            'offline_uuid' => $inspection->offline_uuid,
            'inspection_checklist_id' => $inspection->inspection_checklist_id,
            'inspector_id' => $inspection->inspector_id,
            'status' => $inspection->status,
            'location' => $inspection->location,
            'performed_at' => $inspection->performed_at?->toIso8601String(),
            'submitted_at' => $inspection->submitted_at?->toIso8601String(),
            'notes' => $inspection->notes,
            'sync_status' => $inspection->sync_status,
            'last_synced_at' => $inspection->last_synced_at?->toIso8601String(),
            'deleted_at' => $inspection->deleted_at?->toIso8601String(),
            'created_at' => $inspection->created_at?->toIso8601String(),
            'updated_at' => $inspection->updated_at?->toIso8601String(),
        ];
    }

    private function detectConflict(?Carbon $serverUpdatedAt, array $item, ?Carbon $lastSyncedAt): bool
    {
        if (! $serverUpdatedAt || ! $lastSyncedAt || empty($item['updated_at'])) {
            return false;
        }

        $localUpdatedAt = Carbon::parse($item['updated_at']);

        return $serverUpdatedAt->greaterThan($lastSyncedAt)
            && $localUpdatedAt->greaterThan($lastSyncedAt);
    }

    private function resolveConflict(
        int $userId,
        string $deviceId,
        string $module,
        string $recordId,
        Carbon $serverUpdatedAt,
        Carbon $localUpdatedAt,
        string $conflictStrategy,
        array $payload,
        array &$conflicts
    ): string {
        $winner = null;

        if ($conflictStrategy === self::CONFLICT_MANUAL_REVIEW) {
            $winner = self::CONFLICT_MANUAL_REVIEW;
        } else {
            $winner = $localUpdatedAt->greaterThan($serverUpdatedAt) ? 'local' : 'server';
        }

        $log = ConflictLog::query()->create([
            'user_id' => $userId,
            'device_id' => $deviceId,
            'record_id' => $recordId,
            'module' => $module,
            'local_version' => $localUpdatedAt,
            'server_version' => $serverUpdatedAt,
            'resolution_strategy' => $conflictStrategy,
            'winner' => $winner,
            'payload' => [
                'local' => $payload,
                'server_updated_at' => $serverUpdatedAt->toIso8601String(),
            ],
        ]);

        $conflicts[] = [
            'id' => $log->id,
            'record_id' => $recordId,
            'module' => $module,
            'local_version' => $localUpdatedAt->toIso8601String(),
            'server_version' => $serverUpdatedAt->toIso8601String(),
            'resolution_strategy' => $conflictStrategy,
            'winner' => $winner,
            'requires_manual_review' => $winner === self::CONFLICT_MANUAL_REVIEW,
        ];

        return $winner;
    }

    private function shouldApplyClientUpdate(array $item, Carbon $serverUpdatedAt): bool
    {
        if (empty($item['updated_at'])) {
            return false;
        }

        return Carbon::parse($item['updated_at'])->greaterThan($serverUpdatedAt);
    }
}
