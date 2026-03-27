<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInspectionRequest;
use App\Http\Requests\UpdateInspectionResponseRequest;
use App\Http\Requests\UploadInspectionImageRequest;
use App\Models\Inspection;
use App\Models\InspectionChecklist;
use App\Models\InspectionResponse;
use App\Services\InspectionExecutionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InspectionController extends Controller
{
    public function __construct(private readonly InspectionExecutionService $inspectionService) {}

    public function index(): View
    {
        $inspections = Inspection::query()
            ->with(['checklist', 'inspector'])
            ->latest()
            ->paginate(10);

        return view('inspections.index', compact('inspections'));
    }

    public function create(): View
    {
        $checklists = InspectionChecklist::query()->where('is_active', true)->orderBy('title')->get();

        return view('inspections.create', compact('checklists'));
    }

    public function store(StoreInspectionRequest $request): RedirectResponse
    {
        $inspection = $this->inspectionService->start($request->validated(), $request->user());

        return redirect()->route('inspections.show', $inspection)->with('toast', [
            'type' => 'success',
            'title' => 'Inspection Started',
            'message' => 'Inspection draft created from checklist.',
        ]);
    }

    public function show(Inspection $inspection): View
    {
        $inspection->load(['checklist.items', 'inspector', 'responses.checklistItem', 'responses.images']);

        return view('inspections.show', compact('inspection'));
    }

    public function updateResponses(UpdateInspectionResponseRequest $request, Inspection $inspection): RedirectResponse
    {
        $this->inspectionService->updateResponses(
            $inspection,
            $request->validated('responses'),
            (bool) $request->validated('mark_as_completed', false)
        );

        return redirect()->route('inspections.show', $inspection)->with('toast', [
            'type' => 'success',
            'title' => 'Responses Saved',
            'message' => 'Inspection responses were updated.',
        ]);
    }

    public function uploadImage(
        UploadInspectionImageRequest $request,
        Inspection $inspection,
        InspectionResponse $response
    ): RedirectResponse {
        $this->inspectionService->uploadImage(
            $inspection,
            $response,
            $request->file('image'),
            $request->user(),
            $request->validated('captured_at')
        );

        return redirect()->route('inspections.show', $inspection)->with('toast', [
            'type' => 'success',
            'title' => 'Image Uploaded',
            'message' => 'Inspection image uploaded successfully.',
        ]);
    }

    public function submit(Inspection $inspection): RedirectResponse
    {
        $this->inspectionService->submit($inspection);

        return redirect()->route('inspections.show', $inspection)->with('toast', [
            'type' => 'success',
            'title' => 'Inspection Submitted',
            'message' => 'Inspection marked as submitted and ready for sync.',
        ]);
    }
}
