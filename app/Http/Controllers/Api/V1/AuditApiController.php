<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Audits\StoreAuditApiRequest;
use App\Http\Requests\Api\Audits\UpdateAuditApiRequest;
use App\Http\Resources\Api\AuditResource;
use App\Models\SiteAudit;
use App\Services\SiteAuditService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditApiController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly SiteAuditService $siteAuditService)
    {
    }

    /**
     * GET /api/v1/audits
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasAnyRole(['Admin', 'Manager', 'Safety Officer'])) {
            return $this->forbidden();
        }

        $query = SiteAudit::withTrashed()
            ->with(['creator'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('audit_type'), fn ($q) => $q->where('audit_type', $request->query('audit_type')))
            ->when($request->filled('site_name'), fn ($q) => $q->where('site_name', 'like', '%'.$request->query('site_name').'%'));

        $sort = in_array($request->get('sort'), ['created_at', 'updated_at', 'conducted_at', 'scheduled_for', 'kpi_score'], true)
            ? $request->get('sort')
            : 'created_at';
        $dir  = $request->get('direction') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $dir);

        $paginator = $query->paginate($request->integer('per_page', 15));

        return $this->paginated($paginator->through(fn ($a) => new AuditResource($a)));
    }

    /**
     * POST /api/v1/audits
     */
    public function store(StoreAuditApiRequest $request): JsonResponse
    {
        $audit = $this->siteAuditService->create($request->validated(), $request->user());

        return $this->created(new AuditResource($audit->load(['creator', 'kpis'])));
    }

    /**
     * GET /api/v1/audits/{audit}
     */
    public function show(SiteAudit $audit): JsonResponse
    {
        $audit->load(['creator', 'kpis', 'ncrReports.correctiveActions', 'approvals.approver']);

        return $this->success(new AuditResource($audit));
    }

    /**
     * PUT /api/v1/audits/{audit}
     */
    public function update(UpdateAuditApiRequest $request, SiteAudit $audit): JsonResponse
    {
        if (! in_array($audit->status, ['draft', 'scheduled', 'in_progress'], true)) {
            return $this->error('Audit cannot be modified in its current status.', null, 422);
        }

        $audit = $this->siteAuditService->update($audit, $request->validated());

        return $this->success(new AuditResource($audit->load(['creator', 'kpis'])));
    }

    /**
     * DELETE /api/v1/audits/{audit}
     */
    public function destroy(Request $request, SiteAudit $audit): JsonResponse
    {
        if (! $request->user()->hasAnyRole(['Admin', 'Manager'])) {
            return $this->forbidden();
        }

        $audit->delete();

        return $this->noContent('Audit deleted.');
    }
}
