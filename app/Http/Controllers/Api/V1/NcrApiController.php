<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ncr\StoreNcrApiRequest;
use App\Http\Requests\Api\Ncr\UpdateNcrApiRequest;
use App\Http\Resources\Api\NcrReportResource;
use App\Models\NcrReport;
use App\Services\SiteAuditService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NcrApiController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly SiteAuditService $siteAuditService)
    {
    }

    /**
     * GET /api/v1/ncr
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasAnyRole(['Admin', 'Manager', 'Safety Officer'])) {
            return $this->forbidden();
        }

        $query = NcrReport::withTrashed()
            ->with(['reporter'])
            ->when($request->filled('site_audit_id'), fn ($q) => $q->where('site_audit_id', $request->query('site_audit_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('severity'), fn ($q) => $q->where('severity', $request->query('severity')));

        $sort = in_array($request->get('sort'), ['created_at', 'updated_at', 'due_date', 'severity'], true)
            ? $request->get('sort')
            : 'created_at';
        $dir  = $request->get('direction') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $dir);

        $paginator = $query->paginate($request->integer('per_page', 15));

        return $this->paginated($paginator->through(fn ($n) => new NcrReportResource($n)));
    }

    /**
     * POST /api/v1/ncr
     */
    public function store(StoreNcrApiRequest $request): JsonResponse
    {
        $data      = $request->validated();
        $siteAudit = \App\Models\SiteAudit::findOrFail($data['site_audit_id']);

        $ncr = $this->siteAuditService->createNcr($siteAudit, $data, $request->user());

        return $this->created(new NcrReportResource($ncr->load('reporter')));
    }

    /**
     * GET /api/v1/ncr/{ncrReport}
     */
    public function show(NcrReport $ncrReport): JsonResponse
    {
        $ncrReport->load(['reporter', 'siteAudit', 'correctiveActions.assignee']);

        return $this->success(new NcrReportResource($ncrReport));
    }

    /**
     * PUT /api/v1/ncr/{ncrReport}
     */
    public function update(UpdateNcrApiRequest $request, NcrReport $ncrReport): JsonResponse
    {
        $ncr = $this->siteAuditService->updateNcr($ncrReport, $request->validated(), $request->user());

        return $this->success(new NcrReportResource($ncr->fresh('reporter')));
    }

    /**
     * DELETE /api/v1/ncr/{ncrReport}
     */
    public function destroy(Request $request, NcrReport $ncrReport): JsonResponse
    {
        if (! $request->user()->hasAnyRole(['Admin', 'Manager'])) {
            return $this->forbidden();
        }

        $ncrReport->delete();

        return $this->noContent('NCR report deleted.');
    }
}
