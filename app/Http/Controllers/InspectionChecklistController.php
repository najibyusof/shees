<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInspectionChecklistRequest;
use App\Http\Requests\UpdateInspectionChecklistRequest;
use App\Models\InspectionChecklist;
use App\Services\InspectionChecklistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InspectionChecklistController extends Controller
{
    public function __construct(private readonly InspectionChecklistService $checklistService) {}

    public function index(): View
    {
        $checklists = InspectionChecklist::query()
            ->withCount(['items', 'inspections'])
            ->latest()
            ->paginate(10);

        return view('inspections.checklists.index', compact('checklists'));
    }

    public function create(): View
    {
        return view('inspections.checklists.create');
    }

    public function store(StoreInspectionChecklistRequest $request): RedirectResponse
    {
        $checklist = $this->checklistService->create($request->validated(), $request->user());

        return redirect()->route('inspection-checklists.show', $checklist)->with('toast', [
            'type' => 'success',
            'title' => 'Checklist Created',
            'message' => 'Inspection checklist created successfully.',
        ]);
    }

    public function show(InspectionChecklist $inspectionChecklist): View
    {
        $inspectionChecklist->load('items');

        return view('inspections.checklists.show', ['checklist' => $inspectionChecklist]);
    }

    public function edit(InspectionChecklist $inspectionChecklist): View
    {
        $inspectionChecklist->load('items');

        return view('inspections.checklists.edit', ['checklist' => $inspectionChecklist]);
    }

    public function update(UpdateInspectionChecklistRequest $request, InspectionChecklist $inspectionChecklist): RedirectResponse
    {
        $checklist = $this->checklistService->update($inspectionChecklist, $request->validated());

        return redirect()->route('inspection-checklists.show', $checklist)->with('toast', [
            'type' => 'success',
            'title' => 'Checklist Updated',
            'message' => 'Checklist updated and version incremented.',
        ]);
    }
}
