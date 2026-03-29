<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\IncidentActivity;
use App\Models\IncidentAttachment;
use App\Models\IncidentClassification;
use App\Models\IncidentComment;
use App\Models\IncidentCommentReply;
use App\Models\IncidentLocation;
use App\Models\IncidentStatus;
use App\Models\Subcontractor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IncidentService
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    public function create(array $data, array $attachments, User $user): Incident
    {
        return DB::transaction(function () use ($data, $attachments, $user) {
            $incident = Incident::query()->create(
                $this->buildIncidentAttributes($data, $user)
            );

            $this->syncAggregate($incident, $data, $attachments, $user, []);

            $this->logActivity(
                $incident,
                $user,
                'created',
                'Incident was created.',
                [
                    'status' => $incident->status,
                    'reference_number' => $incident->incident_reference_number,
                ]
            );

            $this->auditLogService->log($user->id, 'create', 'incidents', $incident, [
                'status' => $incident->status,
                'reference_number' => $incident->incident_reference_number,
            ]);

            return $this->loadIncidentAggregate($incident);
        });
    }

    public function update(Incident $incident, array $data, array $attachments, User $user, array $removeAttachmentIds = []): Incident
    {
        return DB::transaction(function () use ($incident, $data, $attachments, $user, $removeAttachmentIds) {
            $incident->update($this->buildIncidentAttributes($data, $user, $incident));

            $this->syncAggregate($incident, $data, $attachments, $user, $removeAttachmentIds);

            $this->logActivity($incident, $user, 'updated', 'Incident details were updated.');

            $this->auditLogService->log($user->id, 'update', 'incidents', $incident, [
                'status' => $incident->status,
                'reference_number' => $incident->incident_reference_number,
            ]);

            return $this->loadIncidentAggregate($incident);
        });
    }

    public function addComment(
        Incident $incident,
        User $user,
        string $comment,
        string $commentType = 'general',
        ?bool $isCritical = null,
    ): IncidentComment {
        return DB::transaction(function () use ($incident, $user, $comment, $commentType, $isCritical) {
            $resolvedCriticalTypes = IncidentComment::criticalTypes();
            $critical = $isCritical ?? in_array($commentType, $resolvedCriticalTypes, true);

            $created = IncidentComment::query()->create([
                'incident_id' => $incident->id,
                'user_id' => $user->id,
                'comment_type' => $commentType,
                'comment' => $comment,
                'is_critical' => $critical,
                'is_resolved' => false,
            ]);

            $this->logActivity($incident, $user, 'comment_added', 'Workflow comment added.');

            $this->auditLogService->log($user->id, 'comment', 'incidents', $incident, [
                'comment_type' => $commentType,
                'is_critical' => $critical,
            ]);

            return $created;
        });
    }

    public function addCommentReply(Incident $incident, IncidentComment $comment, User $user, string $reply): IncidentCommentReply
    {
        return DB::transaction(function () use ($incident, $comment, $user, $reply) {
            $created = $comment->replies()->create([
                'user_id' => $user->id,
                'reply' => $reply,
            ]);

            $this->logActivity($incident, $user, 'comment_reply_added', 'Comment reply added.');

            $this->auditLogService->log($user->id, 'comment_reply', 'incidents', $incident, [
                'comment_id' => $comment->id,
            ]);

            return $created;
        });
    }

    public function setCommentResolution(
        Incident $incident,
        IncidentComment $comment,
        User $user,
        bool $resolved,
        ?string $resolutionNote = null,
    ): IncidentComment {
        return DB::transaction(function () use ($incident, $comment, $user, $resolved, $resolutionNote) {
            $comment->fill([
                'is_resolved' => $resolved,
                'resolved_by' => $resolved ? $user->id : null,
                'resolved_at' => $resolved ? now() : null,
                'resolution_note' => $resolved ? $resolutionNote : null,
            ])->save();

            $this->logActivity(
                $incident,
                $user,
                $resolved ? 'comment_resolved' : 'comment_reopened',
                $resolved ? 'Comment marked as resolved.' : 'Comment reopened.'
            );

            $this->auditLogService->log($user->id, $resolved ? 'comment_resolved' : 'comment_reopened', 'incidents', $incident, [
                'comment_id' => $comment->id,
            ]);

            return $comment->fresh(['user', 'resolver']);
        });
    }

    private function syncAggregate(Incident $incident, array $data, array $attachments, User $user, array $removeAttachmentIds): void
    {
        $this->syncAttachments($incident, $attachments, $user, $removeAttachmentIds);

        $this->syncHasManyRecords($incident, 'chronologies', $data['chronologies'] ?? [], [
            'event_date',
            'event_time',
            'events',
            'sort_order',
            'temporary_id',
            'local_created_at',
        ]);

        $this->syncHasManyRecords($incident, 'victims', $data['victims'] ?? [], [
            'victim_type_id',
            'name',
            'identification',
            'occupation',
            'age',
            'nationality',
            'working_experience',
            'nature_of_injury',
            'body_injured',
            'treatment',
            'temporary_id',
            'local_created_at',
        ]);

        $this->syncHasManyRecords($incident, 'witnesses', $data['witnesses'] ?? [], [
            'name',
            'designation',
            'identification',
            'temporary_id',
            'local_created_at',
        ]);

        $this->syncHasManyRecords($incident, 'investigationTeamMembers', $data['investigation_team_members'] ?? [], [
            'name',
            'designation',
            'contact_number',
            'company',
            'temporary_id',
            'local_created_at',
        ]);

        $this->syncHasManyRecords($incident, 'damages', $data['damages'] ?? [], [
            'damage_type_id',
            'estimate_cost',
            'temporary_id',
            'local_created_at',
        ]);

        $this->syncHasManyRecords($incident, 'immediateActions', $data['immediate_actions'] ?? [], [
            'action_taken',
            'company',
            'temporary_id',
            'local_created_at',
        ]);

        $this->syncHasManyRecords($incident, 'plannedActions', $data['planned_actions'] ?? [], [
            'action_taken',
            'expected_date',
            'actual_date',
            'temporary_id',
            'local_created_at',
        ]);

        $incident->immediateCauses()->sync($this->normalizeIds($data['immediate_cause_ids'] ?? []));
        $incident->contributingFactors()->sync($this->normalizeIds($data['contributing_factor_ids'] ?? []));
        $incident->workActivities()->sync($this->normalizeIds($data['work_activity_ids'] ?? []));
        $incident->externalParties()->sync($this->normalizeIds($data['external_party_ids'] ?? []));
    }

    private function buildIncidentAttributes(array $data, User $user, ?Incident $incident = null): array
    {
        $incidentDate = $this->resolveIncidentDate($data, $incident);
        $incidentTime = $this->resolveIncidentTime($data, $incident);
        $dateTime = $this->combineDateAndTime($incidentDate, $incidentTime, $data['datetime'] ?? $incident?->datetime);

        $status = $this->resolveStatusRecord($data['status_id'] ?? null, $data['status'] ?? null, $incident?->status, 'draft');
        $legacyClassification = is_string($data['classification'] ?? null) ? $data['classification'] : null;
        $classification = $this->resolveClassificationRecord($data['classification_id'] ?? null, $legacyClassification ?? $incident?->classification);
        $location = $this->resolveLocationRecord($data['location_id'] ?? null);
        $subcontractor = ! empty($data['subcontractor_id']) ? Subcontractor::query()->find($data['subcontractor_id']) : null;
        $description = trim((string) ($data['incident_description'] ?? $data['description'] ?? $incident?->incident_description ?? $incident?->description ?? ''));
        $otherLocation = trim((string) ($data['other_location'] ?? $incident?->other_location ?? ''));
        $legacyLocation = is_string($data['location'] ?? null) ? trim($data['location']) : '';
        $locationLabel = $otherLocation !== '' ? $otherLocation : ($location?->name ?? ($legacyLocation !== '' ? $legacyLocation : ($incident?->location ?? '')));
        $workActivityIds = $this->normalizeIds($data['work_activity_ids'] ?? []);
        $primaryWorkActivityId = $data['work_activity_id'] ?? $incident?->work_activity_id ?? ($workActivityIds[0] ?? null);
        $locationTypeId = $data['location_type_id'] ?? $incident?->location_type_id ?? $location?->location_type_id;
        $referenceNumber = $incident?->incident_reference_number ?: $this->generateReferenceNumber();

        return array_filter([
            'reported_by' => $incident?->reported_by ?? $user->id,
            'incident_reference_number' => $referenceNumber,
            'title' => $data['title'] ?? $incident?->title ?? 'Incident Report',
            'incident_type_id' => $data['incident_type_id'] ?? $incident?->incident_type_id,
            'description' => $description !== '' ? $description : null,
            'incident_description' => $description !== '' ? $description : null,
            'location_type_id' => $locationTypeId,
            'location_id' => $data['location_id'] ?? $incident?->location_id,
            'location' => $locationLabel !== '' ? $locationLabel : null,
            'other_location' => $otherLocation !== '' ? $otherLocation : ($location?->name ?? ($legacyLocation !== '' ? $legacyLocation : null)),
            'datetime' => $dateTime,
            'incident_date' => $incidentDate,
            'incident_time' => $incidentTime,
            'classification_id' => $data['classification_id'] ?? $incident?->classification_id ?? $classification?->id,
            'classification' => $classification?->name ?? $legacyClassification ?? $incident?->classification,
            'reclassification_id' => $data['reclassification_id'] ?? $incident?->reclassification_id,
            'status_id' => $data['status_id'] ?? $incident?->status_id ?? $status?->id,
            'status' => $status?->code ?? $incident?->status ?? 'draft',
            'work_package_id' => $data['work_package_id'] ?? $incident?->work_package_id,
            'work_activity_id' => $primaryWorkActivityId,
            'immediate_response' => $data['immediate_response'] ?? $incident?->immediate_response,
            'subcontractor_id' => $data['subcontractor_id'] ?? $incident?->subcontractor_id,
            'person_in_charge' => $data['person_in_charge'] ?? $incident?->person_in_charge,
            'subcontractor_contact_number' => $data['subcontractor_contact_number']
                ?? $incident?->subcontractor_contact_number
                ?? $subcontractor?->contact_number,
            'gps_location' => $data['gps_location'] ?? $incident?->gps_location,
            'activity_during_incident' => $data['activity_during_incident'] ?? $incident?->activity_during_incident,
            'type_of_accident' => $data['type_of_accident'] ?? $incident?->type_of_accident,
            'basic_effect' => $data['basic_effect'] ?? $incident?->basic_effect,
            'conclusion' => $data['conclusion'] ?? $incident?->conclusion,
            'close_remark' => $data['close_remark'] ?? $incident?->close_remark,
            'rootcause_id' => $data['rootcause_id'] ?? $incident?->rootcause_id,
            'other_rootcause' => $data['other_rootcause'] ?? $incident?->other_rootcause,
            'temporary_id' => $data['temporary_id'] ?? $incident?->temporary_id,
            'local_created_at' => $data['local_created_at'] ?? $incident?->local_created_at,
        ], static fn ($value) => $value !== null);
    }

    private function syncAttachments(Incident $incident, array $attachments, User $user, array $removeAttachmentIds): void
    {
        if ($removeAttachmentIds !== []) {
            $attachmentsToRemove = $incident->attachments()->whereIn('id', $removeAttachmentIds)->get();

            foreach ($attachmentsToRemove as $attachment) {
                Storage::disk('public')->delete($attachment->path);
                $attachment->delete();
            }

            $this->logActivity($incident, $user, 'attachments_removed', 'Attachment(s) removed from incident.', [
                'count' => count($removeAttachmentIds),
            ]);
        }

        $createdCount = 0;

        foreach ($attachments as $attachment) {
            if (! is_array($attachment)) {
                continue;
            }

            $payload = Arr::only($attachment, [
                'attachment_type_id',
                'attachment_category_id',
                'filename',
                'path',
                'description',
                'temporary_id',
                'local_created_at',
            ]);

            if (isset($attachment['id'])) {
                $existing = $incident->attachments()->find($attachment['id']);
                if ($existing) {
                    $existing->update(array_filter($payload, static fn ($value) => $value !== null && $value !== ''));
                }
                continue;
            }

            $file = $attachment['file'] ?? null;
            $path = $payload['path'] ?? null;
            $originalName = $payload['filename'] ?? null;
            $mimeType = null;
            $size = 0;

            if ($file instanceof UploadedFile) {
                $path = $file->store('incidents', 'public');
                $originalName = $file->getClientOriginalName();
                $mimeType = $file->getClientMimeType();
                $size = $file->getSize() ?? 0;
            }

            if (! $path && ! $originalName) {
                continue;
            }

            $incident->attachments()->create([
                'attachment_type_id' => $payload['attachment_type_id'] ?? null,
                'attachment_category_id' => $payload['attachment_category_id'] ?? null,
                'original_name' => $originalName ?? basename((string) $path),
                'filename' => $payload['filename'] ?? $originalName ?? basename((string) $path),
                'path' => $path ?? '',
                'description' => $payload['description'] ?? null,
                'mime_type' => $mimeType,
                'size' => $size,
                'temporary_id' => $payload['temporary_id'] ?? null,
                'local_created_at' => $payload['local_created_at'] ?? null,
            ]);
            $createdCount++;
        }

        if ($createdCount > 0) {
            $this->logActivity($incident, $user, 'attachments_added', 'New attachment(s) uploaded.', [
                'count' => $createdCount,
            ]);
        }
    }

    private function syncHasManyRecords(Incident $incident, string $relationName, array $rows, array $fillable): void
    {
        $relation = $incident->{$relationName}();
        $existingIds = collect($rows)
            ->pluck('id')
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $relation->when($existingIds !== [], fn ($query) => $query->whereNotIn('id', $existingIds), fn ($query) => $query)->delete();

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $attributes = Arr::only($row, $fillable);
            if (! $this->rowHasContent($attributes)) {
                continue;
            }

            if (! empty($row['id']) && ($model = $relation->find($row['id']))) {
                $model->update($attributes);
                continue;
            }

            $relation->create($attributes);
        }
    }

    private function rowHasContent(array $attributes): bool
    {
        foreach ($attributes as $value) {
            if (is_array($value) && $value !== []) {
                return true;
            }

            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    private function normalizeIds(array $ids): array
    {
        return collect($ids)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function resolveIncidentDate(array $data, ?Incident $incident): string
    {
        if (! empty($data['incident_date'])) {
            return Carbon::parse($data['incident_date'])->toDateString();
        }

        if (! empty($data['datetime'])) {
            return Carbon::parse($data['datetime'])->toDateString();
        }

        return $incident?->incident_date?->toDateString() ?? now()->toDateString();
    }

    private function resolveIncidentTime(array $data, ?Incident $incident): string
    {
        if (! empty($data['incident_time'])) {
            return Carbon::parse($data['incident_time'])->format('H:i:s');
        }

        if (! empty($data['datetime'])) {
            return Carbon::parse($data['datetime'])->format('H:i:s');
        }

        return $incident?->incident_time?->format('H:i:s') ?? now()->format('H:i:s');
    }

    private function combineDateAndTime(string $date, string $time, mixed $fallback): Carbon
    {
        if ($fallback) {
            try {
                return Carbon::parse($fallback);
            } catch (\Throwable) {
            }
        }

        return Carbon::parse($date.' '.$time);
    }

    private function resolveStatusRecord(?int $statusId, ?string $statusCode, ?string $existingStatus, string $fallback): ?IncidentStatus
    {
        if ($statusId) {
            return IncidentStatus::query()->find($statusId);
        }

        $code = $statusCode ?: $existingStatus ?: $fallback;

        return IncidentStatus::query()->where('code', Str::of($code)->lower()->replace(' ', '_')->value())->first();
    }

    private function resolveClassificationRecord(?int $classificationId, ?string $existingClassification): ?IncidentClassification
    {
        if ($classificationId) {
            return IncidentClassification::query()->find($classificationId);
        }

        if (! $existingClassification) {
            return null;
        }

        return IncidentClassification::query()->where('name', $existingClassification)->first();
    }

    private function resolveLocationRecord(?int $locationId): ?IncidentLocation
    {
        return $locationId ? IncidentLocation::query()->find($locationId) : null;
    }

    private function generateReferenceNumber(): string
    {
        $prefix = 'INC-'.now()->format('Ymd').'-';

        do {
            $reference = $prefix.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (Incident::query()->where('incident_reference_number', $reference)->exists());

        return $reference;
    }

    private function loadIncidentAggregate(Incident $incident): Incident
    {
        return $incident->load([
            'reporter',
            'incidentType',
            'incidentStatus',
            'incidentClassification',
            'reclassification',
            'locationType',
            'incidentLocation.locationType',
            'workPackage',
            'workActivity',
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
        ]);
    }

    private function logActivity(
        Incident $incident,
        ?User $user,
        string $action,
        ?string $description = null,
        ?array $metadata = null
    ): void {
        IncidentActivity::query()->create([
            'incident_id' => $incident->id,
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

}
