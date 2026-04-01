<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCorrectiveActionRequest;
use App\Http\Requests\StoreNcrReportRequest;
use App\Http\Requests\UpdateCorrectiveActionRequest;
use App\Http\Requests\UpdateNcrReportRequest;
use App\Models\CorrectiveAction;
use App\Models\NcrReport;
use App\Models\SiteAudit;
use App\Services\SiteAuditService;
use Illuminate\Http\RedirectResponse;

class NcrReportController extends Controller
{
    public function __construct(private readonly SiteAuditService $siteAuditService) {}

    public function store(StoreNcrReportRequest $request, SiteAudit $siteAudit): RedirectResponse
    {
        $this->authorize('createNcr', $siteAudit);

        $validated = $request->validated();

        $this->siteAuditService->createNcr($siteAudit, $validated, $request->user());

        return redirect()->route('site-audits.show', $siteAudit)->with('toast', [
            'type' => 'success',
            'title' => 'NCR Added',
            'message' => 'Non-conformance report logged successfully.',
        ]);
    }

    public function update(UpdateNcrReportRequest $request, NcrReport $ncrReport): RedirectResponse
    {
        $this->authorize('updateNcr', $ncrReport->siteAudit);

        $validated = $request->validated();

        $this->siteAuditService->updateNcr($ncrReport, $validated, $request->user());

        return redirect()->route('site-audits.show', $ncrReport->siteAudit)->with('toast', [
            'type' => 'success',
            'title' => 'NCR Updated',
            'message' => 'NCR root cause and action plan updated.',
        ]);
    }

    public function storeCorrectiveAction(StoreCorrectiveActionRequest $request, NcrReport $ncrReport): RedirectResponse
    {
        $this->authorize('createCorrectiveAction', $ncrReport->siteAudit);

        $validated = $request->validated();

        $this->siteAuditService->addCorrectiveAction($ncrReport, $validated);

        return redirect()->route('site-audits.show', $ncrReport->siteAudit)->with('toast', [
            'type' => 'success',
            'title' => 'Corrective Action Added',
            'message' => 'Corrective action item has been added.',
        ]);
    }

    public function updateCorrectiveAction(UpdateCorrectiveActionRequest $request, CorrectiveAction $correctiveAction): RedirectResponse
    {
        $this->authorize('updateCorrectiveAction', $correctiveAction->ncrReport->siteAudit);

        $validated = $request->validated();

        $this->siteAuditService->updateCorrectiveAction($correctiveAction, $validated, $request->user());

        return redirect()->route('site-audits.show', $correctiveAction->ncrReport->siteAudit)->with('toast', [
            'type' => 'success',
            'title' => 'Corrective Action Updated',
            'message' => 'Corrective action progress has been updated.',
        ]);
    }
}
