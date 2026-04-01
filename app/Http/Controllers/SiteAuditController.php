<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApproveSiteAuditRequest;
use App\Http\Requests\RejectSiteAuditRequest;
use App\Http\Requests\StoreSiteAuditKpiRequest;
use App\Http\Requests\StoreSiteAuditRequest;
use App\Http\Requests\SubmitSiteAuditRequest;
use App\Http\Requests\UpdateSiteAuditRequest;
use App\Models\Role;
use App\Models\SiteAudit;
use App\Models\User;
use App\Services\SiteAuditService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SiteAuditController extends Controller
{
    public function __construct(private readonly SiteAuditService $siteAuditService)
    {
        $this->authorizeResource(SiteAudit::class, 'site_audit');
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'in:'.implode(',', SiteAudit::STATUSES)],
            'audit_type' => ['nullable', 'string', 'max:50'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort' => ['nullable', 'string', 'in:reference_no,site_name,scheduled_for,kpi_score,status,created_at'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50'],
        ]);

        $sort = (string) ($filters['sort'] ?? 'created_at');
        $direction = (string) ($filters['direction'] ?? 'desc');
        $perPage = (int) ($filters['per_page'] ?? 25);

        $siteAudits = SiteAudit::query()
            ->select([
                'id',
                'reference_no',
                'site_name',
                'audit_type',
                'scheduled_for',
                'kpi_score',
                'status',
                'created_by',
                'created_at',
            ])
            ->with(['creator:id,name,email'])
            ->withCount([
                'ncrReports as open_ncr_count' => fn ($query) => $query->whereIn('status', ['open', 'in_progress', 'pending_verification']),
            ])
            ->search($filters['search'] ?? null)
            ->status($filters['status'] ?? null)
            ->auditType($filters['audit_type'] ?? null)
            ->creator(isset($filters['assigned_to']) ? (int) $filters['assigned_to'] : null)
            ->creatorRoles($filters['role_ids'] ?? [])
            ->dateBetween($filters['date_from'] ?? null, $filters['date_to'] ?? null)
            ->sortByField($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        $roles = Role::query()->orderBy('name')->get(['id', 'name']);
        $assignees = User::query()->orderBy('name')->get(['id', 'name']);
        $auditTypes = SiteAudit::query()->select('audit_type')->distinct()->orderBy('audit_type')->pluck('audit_type');

        return view('site-audits.index', [
            'siteAudits' => $siteAudits,
            'roles' => $roles,
            'assignees' => $assignees,
            'auditTypes' => $auditTypes,
            'filters' => [
                'search' => (string) ($filters['search'] ?? ''),
                'status' => (string) ($filters['status'] ?? ''),
                'audit_type' => (string) ($filters['audit_type'] ?? ''),
                'assigned_to' => (string) ($filters['assigned_to'] ?? ''),
                'role_ids' => array_map('intval', $filters['role_ids'] ?? []),
                'date_from' => (string) ($filters['date_from'] ?? ''),
                'date_to' => (string) ($filters['date_to'] ?? ''),
                'per_page' => (string) ($filters['per_page'] ?? '25'),
            ],
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create(): View
    {
        return view('site-audits.create');
    }

    public function store(StoreSiteAuditRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->siteAuditService->create($validated, $request->user());

        return redirect()->route('site-audits.index')->with('toast', [
            'type' => 'success',
            'title' => 'Audit Created',
            'message' => 'Site audit schedule has been created successfully.',
        ]);
    }

    public function show(SiteAudit $siteAudit): View
    {
        $siteAudit->load([
            'creator',
            'kpis',
            'ncrReports.owner',
            'ncrReports.reporter',
            'ncrReports.correctiveActions.assignee',
            'approvals.approver',
        ]);

        $requiredApprovalRoles = SiteAudit::APPROVAL_REQUIRED_ROLES;
        $approvedRoles = $siteAudit->approvals
            ->where('decision', 'approved')
            ->pluck('approver_role')
            ->intersect($requiredApprovalRoles)
            ->unique()
            ->values()
            ->all();

        $missingApprovalRoles = array_values(array_diff($requiredApprovalRoles, $approvedRoles));

        return view('site-audits.show', compact('siteAudit', 'requiredApprovalRoles', 'approvedRoles', 'missingApprovalRoles'));
    }

    public function edit(SiteAudit $siteAudit): View
    {
        return view('site-audits.edit', compact('siteAudit'));
    }

    public function update(UpdateSiteAuditRequest $request, SiteAudit $siteAudit): RedirectResponse
    {
        $validated = $request->validated();

        $this->siteAuditService->update($siteAudit, $validated);

        return redirect()->route('site-audits.show', $siteAudit)->with('toast', [
            'type' => 'success',
            'title' => 'Audit Updated',
            'message' => 'Site audit details have been updated.',
        ]);
    }

    public function submit(SubmitSiteAuditRequest $request, SiteAudit $siteAudit): RedirectResponse
    {
        $this->authorize('submit', $siteAudit);

        $this->siteAuditService->submitForApproval($siteAudit, $request->user());

        return redirect()->route('site-audits.show', $siteAudit)->with('toast', [
            'type' => 'success',
            'title' => 'Submitted',
            'message' => 'Site audit submitted for approval workflow.',
        ]);
    }

    public function storeKpi(StoreSiteAuditKpiRequest $request, SiteAudit $siteAudit): RedirectResponse
    {
        $this->authorize('manageKpi', $siteAudit);

        $validated = $request->validated();

        $this->siteAuditService->addKpi($siteAudit, $validated);

        return redirect()->route('site-audits.show', $siteAudit)->with('toast', [
            'type' => 'success',
            'title' => 'KPI Added',
            'message' => 'KPI metric has been added and score recalculated.',
        ]);
    }

    public function approve(ApproveSiteAuditRequest $request, SiteAudit $siteAudit): RedirectResponse
    {
        $this->authorize('approve', $siteAudit);

        $validated = $request->validated();

        $this->siteAuditService->approve($siteAudit, $request->user(), $validated['remarks'] ?? null);

        return redirect()->route('site-audits.show', $siteAudit)->with('toast', [
            'type' => 'success',
            'title' => 'Approval Recorded',
            'message' => 'Audit approval step completed.',
        ]);
    }

    public function reject(RejectSiteAuditRequest $request, SiteAudit $siteAudit): RedirectResponse
    {
        $this->authorize('reject', $siteAudit);

        $validated = $request->validated();

        $this->siteAuditService->reject($siteAudit, $request->user(), $validated['reason']);

        return redirect()->route('site-audits.show', $siteAudit)->with('toast', [
            'type' => 'warning',
            'title' => 'Rejected',
            'message' => 'Audit has been rejected.',
        ]);
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', SiteAudit::class);

        $validated = $request->validate([
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['integer', 'exists:site_audits,id'],
            'action' => ['required', 'string', 'in:delete,update_status'],
            'status' => ['nullable', 'string', 'in:'.implode(',', SiteAudit::STATUSES)],
        ]);

        $selectedIds = array_values(array_unique(array_map('intval', $validated['selected'])));
        $totalSelected = count($selectedIds);
        $audits = SiteAudit::query()->whereIn('id', $selectedIds)->get();

        if ($audits->isEmpty()) {
            return $this->redirectToIndexWithQuery($request)->with('toast', [
                'type' => 'warning',
                'title' => 'No Audits Selected',
                'message' => 'No valid audits were found for this action.',
            ]);
        }

        $targetAbility = $validated['action'] === 'delete' ? 'delete' : 'update';

        $updatableIds = $audits
            ->filter(fn (SiteAudit $siteAudit) => $request->user()->can($targetAbility, $siteAudit))
            ->pluck('id')
            ->all();

        $unauthorizedCount = $totalSelected - count($updatableIds);

        if ($updatableIds === []) {
            return $this->redirectToIndexWithQuery($request)->with('toast', [
                'type' => 'warning',
                'title' => 'Action Not Allowed',
                'message' => "Updated: 0, Skipped: 0, Unauthorized: {$unauthorizedCount}.",
            ]);
        }

        if ($validated['action'] === 'delete') {
            $deletedCount = SiteAudit::query()->whereIn('id', $updatableIds)->delete();
            $skippedCount = max(0, count($updatableIds) - $deletedCount);

            return $this->redirectToIndexWithQuery($request)->with('toast', [
                'type' => 'success',
                'title' => 'Bulk Delete Complete',
                'message' => "Deleted: {$deletedCount}, Skipped: {$skippedCount}, Unauthorized: {$unauthorizedCount}.",
            ]);
        }

        $status = (string) ($validated['status'] ?? '');
        if ($status === '') {
            return $this->redirectToIndexWithQuery($request)->withErrors([
                'status' => 'Please choose a status for bulk update.',
            ]);
        }

        $updatedCount = SiteAudit::query()
            ->whereIn('id', $updatableIds)
            ->update(['status' => $status]);

        $skippedCount = max(0, count($updatableIds) - $updatedCount);

        return $this->redirectToIndexWithQuery($request)->with('toast', [
            'type' => 'success',
            'title' => 'Bulk Update Complete',
            'message' => "Updated: {$updatedCount}, Skipped: {$skippedCount}, Unauthorized: {$unauthorizedCount}.",
        ]);
    }

    private function redirectToIndexWithQuery(Request $request): RedirectResponse
    {
        $query = $request->query();
        $url = route('site-audits.index');

        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        return redirect()->to($url);
    }
}
