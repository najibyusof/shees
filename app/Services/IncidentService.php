<?php

namespace App\Services;

use App\Events\ApprovalRequired;
use App\Events\IncidentSubmitted;
use App\Models\Incident;
use App\Models\IncidentApproval;
use App\Models\IncidentActivity;
use App\Models\IncidentAttachment;
use App\Models\IncidentComment;
use App\Models\User;
use App\Notifications\IncidentWorkflowNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class IncidentService
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    public function create(array $data, array $attachments, User $user): Incident
    {
        return DB::transaction(function () use ($data, $attachments, $user) {
            $incident = Incident::query()->create([
                'reported_by' => $user->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'location' => $data['location'],
                'datetime' => $data['datetime'],
                'classification' => $data['classification'],
                'status' => 'draft',
            ]);

            $this->storeAttachments($incident, $attachments);

            $this->logActivity(
                $incident,
                $user,
                'created',
                'Incident was created.',
                ['status' => $incident->status]
            );

            return $incident->load(['reporter', 'attachments']);
        });
    }

    public function update(Incident $incident, array $data, array $attachments, User $user, array $removeAttachmentIds = []): Incident
    {
        return DB::transaction(function () use ($incident, $data, $attachments, $user, $removeAttachmentIds) {
            $incident->update([
                'title' => $data['title'],
                'description' => $data['description'],
                'location' => $data['location'],
                'datetime' => $data['datetime'],
                'classification' => $data['classification'],
            ]);

            if (! empty($removeAttachmentIds)) {
                $attachmentsToRemove = $incident->attachments()
                    ->whereIn('id', $removeAttachmentIds)
                    ->get();

                foreach ($attachmentsToRemove as $attachment) {
                    Storage::disk('public')->delete($attachment->path);
                }

                $incident->attachments()->whereIn('id', $removeAttachmentIds)->delete();

                $this->logActivity(
                    $incident,
                    $user,
                    'attachments_removed',
                    'Attachment(s) removed from incident.',
                    ['count' => count($removeAttachmentIds)]
                );
            }

            $newAttachmentCount = count($attachments);
            $this->storeAttachments($incident, $attachments);

            if ($newAttachmentCount > 0) {
                $this->logActivity(
                    $incident,
                    $user,
                    'attachments_added',
                    'New attachment(s) uploaded.',
                    ['count' => $newAttachmentCount]
                );
            }

            $this->logActivity($incident, $user, 'updated', 'Incident details were updated.');

            return $incident->load(['reporter', 'attachments']);
        });
    }

    public function submitForApproval(Incident $incident, User $user): Incident
    {
        return DB::transaction(function () use ($incident, $user) {
            if (! in_array($incident->status, ['draft', 'rejected'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft or rejected incidents can be submitted.',
                ]);
            }

            $incident->update([
                'status' => 'submitted',
                'submitted_by' => $user->id,
                'submitted_at' => now(),
                'reviewed_by' => null,
                'reviewed_at' => null,
                'approved_by' => null,
                'approved_at' => null,
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            $this->logActivity($incident, $user, 'submitted', 'Incident submitted for approval.');
            $this->notifyApprovers($incident, 'submitted');

            return $incident->fresh(['reporter', 'attachments', 'activities.user', 'comments.user', 'approvals.approver']);
        });
    }

    public function approve(Incident $incident, User $approver, ?string $remarks = null): Incident
    {
        return DB::transaction(function () use ($incident, $approver, $remarks) {
            $this->guardDecisionStatus($incident, 'approve');
            $this->guardApproverDecisionConflict($incident, $approver, 'approve');

            if ($incident->approvals()->where('approver_id', $approver->id)->where('decision', 'approved')->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'You already approved this incident.',
                ]);
            }

            $fromStatus = $incident->status;
            $currentRole = $this->approverRole($approver);
            $this->recordApproval($incident, $approver, 'approved', $remarks, $currentRole);

            $approvedRoleCount = $incident->approvals()
                ->where('decision', 'approved')
                ->whereIn('approver_role', Incident::APPROVAL_REQUIRED_ROLES)
                ->distinct('approver_role')
                ->count('approver_role');

            if ($approvedRoleCount >= $this->requiredDistinctApproverRoles()) {
                $incident->update([
                    'status' => 'approved',
                    'reviewed_by' => $approver->id,
                    'reviewed_at' => $incident->reviewed_at ?? now(),
                    'approved_by' => $approver->id,
                    'approved_at' => now(),
                    'rejected_by' => null,
                    'rejected_at' => null,
                    'rejection_reason' => null,
                ]);

                $this->auditLogService->log(
                    $approver->id,
                    'approve',
                    'incidents',
                    $incident,
                    [
                        'outcome' => 'approved',
                    ]
                );

                $this->logActivity($incident, $approver, 'status_changed', 'Incident approved.', [
                    'from' => $fromStatus,
                    'to' => 'approved',
                    'remarks' => $remarks,
                    'approved_roles' => $approvedRoleCount,
                ]);
                $this->notifyReporter($incident, 'approved');
            } else {
                $incident->update([
                    'status' => 'under_review',
                    'reviewed_by' => $approver->id,
                    'reviewed_at' => $incident->reviewed_at ?? now(),
                ]);

                $this->auditLogService->log(
                    $approver->id,
                    'approve',
                    'incidents',
                    $incident,
                    [
                        'outcome' => 'under_review',
                    ]
                );

                $this->logActivity($incident, $approver, 'status_changed', 'Approval recorded. Additional approver role required.', [
                    'from' => $fromStatus,
                    'to' => 'under_review',
                    'remarks' => $remarks,
                    'approved_roles' => $approvedRoleCount,
                    'required_roles' => $this->requiredDistinctApproverRoles(),
                ]);
                $this->notifyReporter($incident, 'approval_pending');
            }

            return $incident->fresh(['reporter', 'attachments', 'activities.user', 'comments.user', 'approvals.approver']);
        });
    }

    public function reject(Incident $incident, User $approver, string $reason): Incident
    {
        return DB::transaction(function () use ($incident, $approver, $reason) {
            $this->guardDecisionStatus($incident, 'reject');
            $this->guardApproverDecisionConflict($incident, $approver, 'reject');

            $fromStatus = $incident->status;
            $incident->update([
                'status' => 'rejected',
                'reviewed_by' => $approver->id,
                'reviewed_at' => $incident->reviewed_at ?? now(),
                'rejected_by' => $approver->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
                'approved_by' => null,
                'approved_at' => null,
            ]);

            $this->recordApproval($incident, $approver, 'rejected', $reason);
            $this->logActivity($incident, $approver, 'status_changed', 'Incident rejected.', [
                'from' => $fromStatus,
                'to' => 'rejected',
                'remarks' => $reason,
            ]);
            $this->notifyReporter($incident, 'rejected');

            return $incident->fresh(['reporter', 'attachments', 'activities.user', 'comments.user', 'approvals.approver']);
        });
    }

    public function addComment(Incident $incident, User $user, string $comment): IncidentComment
    {
        return DB::transaction(function () use ($incident, $user, $comment) {
            if ($incident->status === 'submitted' && $user->hasPermissionTo('incidents.approve')) {
                $incident->update([
                    'status' => 'under_review',
                    'reviewed_by' => $user->id,
                    'reviewed_at' => now(),
                ]);

                $this->logActivity($incident, $user, 'status_changed', 'Incident moved to under review.', [
                    'from' => 'submitted',
                    'to' => 'under_review',
                ]);
            }

            $created = IncidentComment::query()->create([
                'incident_id' => $incident->id,
                'user_id' => $user->id,
                'comment' => $comment,
            ]);

            $this->logActivity($incident, $user, 'comment_added', 'Workflow comment added.');

            return $created;
        });
    }

    private function guardDecisionStatus(Incident $incident, string $decision): void
    {
        if (! in_array($incident->status, ['submitted', 'under_review'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Incident must be in submitted or under_review before decision: '.$decision.'.',
            ]);
        }
    }

    private function recordApproval(Incident $incident, User $approver, string $decision, ?string $remarks = null, ?string $approverRole = null): void
    {
        IncidentApproval::query()->create([
            'incident_id' => $incident->id,
            'approver_id' => $approver->id,
            'approver_role' => $approverRole ?? $this->approverRole($approver),
            'decision' => $decision,
            'remarks' => $remarks,
            'decided_at' => now(),
        ]);
    }

    private function approverRole(User $approver): string
    {
        return $approver->roles()
            ->whereIn('name', Incident::APPROVAL_REQUIRED_ROLES)
            ->pluck('name')
            ->first() ?? 'Unknown';
    }

    private function requiredDistinctApproverRoles(): int
    {
        return count(Incident::APPROVAL_REQUIRED_ROLES);
    }

    private function guardApproverDecisionConflict(Incident $incident, User $approver, string $decision): void
    {
        $existingDecision = $incident->approvals()
            ->where('approver_id', $approver->id)
            ->latest('decided_at')
            ->value('decision');

        if (! $existingDecision) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => 'You already recorded a '.$existingDecision.' decision for this incident and cannot '.$decision.' it again.',
        ]);
    }

    /**
     * @param  array<int, UploadedFile>  $attachments
     */
    private function storeAttachments(Incident $incident, array $attachments): void
    {
        foreach ($attachments as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('incidents', 'public');

            IncidentAttachment::query()->create([
                'incident_id' => $incident->id,
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize() ?? 0,
            ]);
        }
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

    private function notifyApprovers(Incident $incident, string $event): void
    {
        $approvers = $this->approvers();

        if ($approvers->isEmpty()) {
            return;
        }

        if ($event === 'submitted') {
            event(new IncidentSubmitted($incident, $approvers));

            return;
        }

        Notification::send($approvers, new IncidentWorkflowNotification($incident, $event));
    }

    private function notifyReporter(Incident $incident, string $event): void
    {
        if (! $incident->reporter) {
            return;
        }

        if ($event === 'approval_pending') {
            event(new ApprovalRequired($incident, $incident->reporter));

            return;
        }

        $incident->reporter->notify(new IncidentWorkflowNotification($incident, $event));
    }

    private function approvers(): Collection
    {
        return User::query()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', Incident::APPROVAL_REQUIRED_ROLES);
            })
            ->get();
    }
}
