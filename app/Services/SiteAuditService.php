<?php

namespace App\Services;

use App\Models\CorrectiveAction;
use App\Models\NcrReport;
use App\Models\SiteAudit;
use App\Models\SiteAuditApproval;
use App\Models\SiteAuditKpi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SiteAuditService
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    public function create(array $data, User $user): SiteAudit
    {
        return DB::transaction(function () use ($data, $user) {
            $audit = SiteAudit::query()->create([
                'created_by' => $user->id,
                'reference_no' => $this->generateAuditReference(),
                'site_name' => $data['site_name'],
                'area' => $data['area'] ?? null,
                'audit_type' => $data['audit_type'] ?? 'internal',
                'scheduled_for' => $data['scheduled_for'] ?? null,
                'conducted_at' => $data['conducted_at'] ?? null,
                'scope' => $data['scope'] ?? null,
                'summary' => $data['summary'] ?? null,
                'status' => $data['status'] ?? 'draft',
            ]);

            return $audit->fresh(['creator', 'kpis', 'ncrReports.correctiveActions', 'approvals.approver']);
        });
    }

    public function update(SiteAudit $siteAudit, array $data): SiteAudit
    {
        return DB::transaction(function () use ($siteAudit, $data) {
            $siteAudit->update([
                'site_name' => $data['site_name'],
                'area' => $data['area'] ?? null,
                'audit_type' => $data['audit_type'] ?? $siteAudit->audit_type,
                'scheduled_for' => $data['scheduled_for'] ?? null,
                'conducted_at' => $data['conducted_at'] ?? null,
                'scope' => $data['scope'] ?? null,
                'summary' => $data['summary'] ?? null,
                'status' => $data['status'] ?? $siteAudit->status,
            ]);

            return $this->recomputeKpiScore($siteAudit);
        });
    }

    public function addKpi(SiteAudit $siteAudit, array $data): SiteAuditKpi
    {
        $kpi = SiteAuditKpi::query()->create([
            'site_audit_id' => $siteAudit->id,
            'name' => $data['name'],
            'target_value' => $data['target_value'] ?? null,
            'actual_value' => $data['actual_value'] ?? null,
            'unit' => $data['unit'] ?? null,
            'weight' => $data['weight'] ?? 1,
            'status' => $data['status'] ?? 'pending',
            'notes' => $data['notes'] ?? null,
        ]);

        $this->recomputeKpiScore($siteAudit);

        return $kpi->fresh();
    }

    public function createNcr(SiteAudit $siteAudit, array $data, User $user): NcrReport
    {
        return NcrReport::query()->create([
            'site_audit_id' => $siteAudit->id,
            'reported_by' => $user->id,
            'owner_id' => $data['owner_id'] ?? null,
            'reference_no' => $this->generateNcrReference(),
            'title' => $data['title'],
            'description' => $data['description'],
            'severity' => $data['severity'] ?? 'minor',
            'status' => $data['status'] ?? 'open',
            'root_cause' => $data['root_cause'] ?? null,
            'containment_action' => $data['containment_action'] ?? null,
            'corrective_action_plan' => $data['corrective_action_plan'] ?? null,
            'due_date' => $data['due_date'] ?? null,
        ]);
    }

    public function updateNcr(NcrReport $ncrReport, array $data, ?User $verifier = null): NcrReport
    {
        $update = [
            'owner_id' => $data['owner_id'] ?? $ncrReport->owner_id,
            'title' => $data['title'] ?? $ncrReport->title,
            'description' => $data['description'] ?? $ncrReport->description,
            'severity' => $data['severity'] ?? $ncrReport->severity,
            'status' => $data['status'] ?? $ncrReport->status,
            'root_cause' => $data['root_cause'] ?? $ncrReport->root_cause,
            'containment_action' => $data['containment_action'] ?? $ncrReport->containment_action,
            'corrective_action_plan' => $data['corrective_action_plan'] ?? $ncrReport->corrective_action_plan,
            'due_date' => $data['due_date'] ?? $ncrReport->due_date,
        ];

        if (($data['status'] ?? null) === 'closed') {
            $update['closed_at'] = now();
            $update['verified_by'] = $verifier?->id;
            $update['verified_at'] = now();
        }

        $ncrReport->update($update);

        return $ncrReport->fresh(['owner', 'reporter', 'correctiveActions.assignee']);
    }

    public function addCorrectiveAction(NcrReport $ncrReport, array $data): CorrectiveAction
    {
        return CorrectiveAction::query()->create([
            'ncr_report_id' => $ncrReport->id,
            'assigned_to' => $data['assigned_to'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => $data['status'] ?? 'open',
            'due_date' => $data['due_date'] ?? null,
            'completion_notes' => $data['completion_notes'] ?? null,
        ]);
    }

    public function updateCorrectiveAction(CorrectiveAction $correctiveAction, array $data, ?User $verifier = null): CorrectiveAction
    {
        $update = [
            'assigned_to' => $data['assigned_to'] ?? $correctiveAction->assigned_to,
            'title' => $data['title'] ?? $correctiveAction->title,
            'description' => $data['description'] ?? $correctiveAction->description,
            'status' => $data['status'] ?? $correctiveAction->status,
            'due_date' => $data['due_date'] ?? $correctiveAction->due_date,
            'completion_notes' => $data['completion_notes'] ?? $correctiveAction->completion_notes,
        ];

        if (($data['status'] ?? null) === 'completed') {
            $update['completed_at'] = now();
        }

        if (($data['status'] ?? null) === 'verified') {
            $update['verified_at'] = now();
            $update['verified_by'] = $verifier?->id;
        }

        $correctiveAction->update($update);

        return $correctiveAction->fresh(['assignee']);
    }

    public function submitForApproval(SiteAudit $siteAudit, User $user): SiteAudit
    {
        if (! in_array($siteAudit->status, ['draft', 'scheduled', 'in_progress', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'status' => 'This audit cannot be submitted from its current status.',
            ]);
        }

        $siteAudit->update([
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

        return $siteAudit->fresh(['creator', 'kpis', 'ncrReports.correctiveActions', 'approvals.approver']);
    }

    public function approve(SiteAudit $siteAudit, User $approver, ?string $remarks = null): SiteAudit
    {
        if (! in_array($siteAudit->status, ['submitted', 'under_review'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Audit must be submitted or under_review before approval.',
            ]);
        }

        $this->guardApproverDecisionConflict($siteAudit, $approver, 'approve');

        $role = $this->approverRole($approver);
        SiteAuditApproval::query()->create([
            'site_audit_id' => $siteAudit->id,
            'approver_id' => $approver->id,
            'approver_role' => $role,
            'decision' => 'approved',
            'remarks' => $remarks,
            'decided_at' => now(),
        ]);

        $approvedRoleCount = $siteAudit->approvals()
            ->where('decision', 'approved')
            ->whereIn('approver_role', SiteAudit::APPROVAL_REQUIRED_ROLES)
            ->distinct('approver_role')
            ->count('approver_role');

        if ($approvedRoleCount >= count(SiteAudit::APPROVAL_REQUIRED_ROLES)) {
            $siteAudit->update([
                'status' => 'approved',
                'reviewed_by' => $approver->id,
                'reviewed_at' => $siteAudit->reviewed_at ?? now(),
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);
        } else {
            $siteAudit->update([
                'status' => 'under_review',
                'reviewed_by' => $approver->id,
                'reviewed_at' => $siteAudit->reviewed_at ?? now(),
            ]);
        }

        $this->auditLogService->log(
            $approver->id,
            'approve',
            'audits',
            $siteAudit,
            [
                'approved_roles' => $approvedRoleCount,
                'required_roles' => count(SiteAudit::APPROVAL_REQUIRED_ROLES),
                'status_after' => $siteAudit->status,
            ]
        );

        return $siteAudit->fresh(['creator', 'kpis', 'ncrReports.correctiveActions', 'approvals.approver']);
    }

    public function reject(SiteAudit $siteAudit, User $approver, string $reason): SiteAudit
    {
        if (! in_array($siteAudit->status, ['submitted', 'under_review'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Audit must be submitted or under_review before rejection.',
            ]);
        }

        $this->guardApproverDecisionConflict($siteAudit, $approver, 'reject');

        SiteAuditApproval::query()->create([
            'site_audit_id' => $siteAudit->id,
            'approver_id' => $approver->id,
            'approver_role' => $this->approverRole($approver),
            'decision' => 'rejected',
            'remarks' => $reason,
            'decided_at' => now(),
        ]);

        $siteAudit->update([
            'status' => 'rejected',
            'reviewed_by' => $approver->id,
            'reviewed_at' => $siteAudit->reviewed_at ?? now(),
            'rejected_by' => $approver->id,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return $siteAudit->fresh(['creator', 'kpis', 'ncrReports.correctiveActions', 'approvals.approver']);
    }

    public function dashboardSummary(): array
    {
        return [
            'scheduled' => SiteAudit::query()->whereIn('status', ['scheduled', 'draft'])->count(),
            'in_review' => SiteAudit::query()->whereIn('status', ['submitted', 'under_review'])->count(),
            'approved' => SiteAudit::query()->where('status', 'approved')->count(),
            'open_ncr' => NcrReport::query()->whereIn('status', ['open', 'in_progress', 'pending_verification'])->count(),
            'overdue_actions' => CorrectiveAction::query()
                ->whereIn('status', ['open', 'in_progress'])
                ->whereDate('due_date', '<', now()->toDateString())
                ->count(),
            'pending_actions' => CorrectiveAction::query()->whereIn('status', ['open', 'in_progress'])->count(),
            'closed_ncr' => NcrReport::query()->where('status', 'closed')->count(),
        ];
    }

    private function recomputeKpiScore(SiteAudit $siteAudit): SiteAudit
    {
        $kpis = $siteAudit->kpis()->get();
        $totalWeight = (int) $kpis->sum('weight');

        if ($totalWeight === 0) {
            $siteAudit->update(['kpi_score' => null]);

            return $siteAudit->fresh(['kpis']);
        }

        $weightedSum = 0.0;
        foreach ($kpis as $kpi) {
            $target = $kpi->target_value;
            $actual = $kpi->actual_value;

            if ($target === null || $target <= 0 || $actual === null) {
                continue;
            }

            $ratio = min(1, max(0, $actual / $target));
            $weightedSum += ($ratio * 100) * (int) $kpi->weight;
        }

        $siteAudit->update([
            'kpi_score' => round($weightedSum / $totalWeight, 2),
        ]);

        return $siteAudit->fresh(['kpis']);
    }

    private function guardApproverDecisionConflict(SiteAudit $siteAudit, User $approver, string $decision): void
    {
        $existingDecision = $siteAudit->approvals()
            ->where('approver_id', $approver->id)
            ->latest('decided_at')
            ->value('decision');

        if (! $existingDecision) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => 'You already recorded a '.$existingDecision.' decision for this audit and cannot '.$decision.' it again.',
        ]);
    }

    private function approverRole(User $approver): string
    {
        return $approver->roles()
            ->whereIn('name', SiteAudit::APPROVAL_REQUIRED_ROLES)
            ->pluck('name')
            ->first() ?? 'Unknown';
    }

    private function generateAuditReference(): string
    {
        return 'AUD-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    private function generateNcrReference(): string
    {
        return 'NCR-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}
