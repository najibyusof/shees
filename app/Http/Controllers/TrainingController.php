<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignTrainingUsersRequest;
use App\Http\Requests\MarkTrainingCompletionRequest;
use App\Http\Requests\StoreTrainingRequest;
use App\Http\Requests\UpdateTrainingRequest;
use App\Http\Requests\UploadCertificateRequest;
use App\Models\Training;
use App\Models\User;
use App\Services\TrainingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingController extends Controller
{
    public function __construct(private readonly TrainingService $trainingService)
    {
        $this->authorizeResource(Training::class, 'training');
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort' => ['nullable', 'string', 'in:title,is_active,starts_at,ends_at,created_at'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50'],
        ]);

        $sort = (string) ($filters['sort'] ?? 'created_at');
        $direction = (string) ($filters['direction'] ?? 'desc');
        $perPage = (int) ($filters['per_page'] ?? 10);

        $trainings = Training::query()
            ->select([
                'id',
                'title',
                'starts_at',
                'ends_at',
                'is_active',
                'created_at',
            ])
            ->withCount('users', 'certificates')
            ->search($filters['search'] ?? null)
            ->status($filters['status'] ?? null)
            ->dateBetween($filters['date_from'] ?? null, $filters['date_to'] ?? null)
            ->sortByField($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return view('trainings.index', [
            'trainings' => $trainings,
            'filters' => [
                'search' => (string) ($filters['search'] ?? ''),
                'status' => (string) ($filters['status'] ?? ''),
                'date_from' => (string) ($filters['date_from'] ?? ''),
                'date_to' => (string) ($filters['date_to'] ?? ''),
                'per_page' => (string) ($filters['per_page'] ?? '10'),
            ],
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create(): View
    {
        $users = User::query()->orderBy('name')->get();

        return view('trainings.create', compact('users'));
    }

    public function store(StoreTrainingRequest $request): RedirectResponse
    {
        $training = $this->trainingService->create($request->validated(), $request->user());

        return redirect()->route('trainings.show', $training)->with('toast', [
            'type' => 'success',
            'title' => 'Training Created',
            'message' => 'Training program created successfully.',
        ]);
    }

    public function show(Training $training): View
    {
        $training->load(['users.roles', 'certificates.user']);
        $users = User::query()->orderBy('name')->get();

        return view('trainings.show', compact('training', 'users'));
    }

    public function edit(Training $training): View
    {
        return view('trainings.edit', compact('training'));
    }

    public function update(UpdateTrainingRequest $request, Training $training): RedirectResponse
    {
        $this->trainingService->update($training, $request->validated());

        return redirect()->route('trainings.show', $training)->with('toast', [
            'type' => 'success',
            'title' => 'Training Updated',
            'message' => 'Training program updated successfully.',
        ]);
    }

    public function assignUsers(AssignTrainingUsersRequest $request, Training $training): RedirectResponse
    {
        $this->authorize('assignUsers', $training);

        $this->trainingService->assignUsers($training, $request->validated('user_ids'), $request->user());

        return redirect()->route('trainings.show', $training)->with('toast', [
            'type' => 'success',
            'title' => 'Users Assigned',
            'message' => 'Users assigned to training.',
        ]);
    }

    public function markCompletion(
        MarkTrainingCompletionRequest $request,
        Training $training,
        User $user
    ): RedirectResponse {
        $this->authorize('markCompletion', $training);

        $this->trainingService->markCompletion($training, $user->id, $request->validated('completion_status'));

        return redirect()->route('trainings.show', $training)->with('toast', [
            'type' => 'success',
            'title' => 'Completion Updated',
            'message' => 'Training completion status updated.',
        ]);
    }

    public function uploadCertificate(UploadCertificateRequest $request, Training $training): RedirectResponse
    {
        $this->authorize('uploadCertificate', $training);

        $validated = $request->validated();

        $this->trainingService->uploadCertificate(
            $training,
            (int) $validated['user_id'],
            $request->file('certificate'),
            $request->user(),
            $validated['issued_at'] ?? null,
            $validated['expires_at'] ?? null
        );

        return redirect()->route('trainings.show', $training)->with('toast', [
            'type' => 'success',
            'title' => 'Certificate Uploaded',
            'message' => 'Certificate uploaded successfully.',
        ]);
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Training::class);

        $validated = $request->validate([
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['integer', 'exists:trainings,id'],
            'action' => ['required', 'string', 'in:delete,update_status'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ]);

        $selectedIds = array_values(array_unique(array_map('intval', $validated['selected'])));
        $totalSelected = count($selectedIds);
        $selectedTrainings = Training::query()->whereIn('id', $selectedIds)->get();

        if ($selectedTrainings->isEmpty()) {
            return $this->redirectToIndexWithQuery($request)->with('toast', [
                'type' => 'warning',
                'title' => 'No Trainings Selected',
                'message' => 'No valid trainings were found for this action.',
            ]);
        }

        $targetAbility = $validated['action'] === 'delete' ? 'delete' : 'update';
        $authorizedIds = $selectedTrainings
            ->filter(fn (Training $training) => $request->user()->can($targetAbility, $training))
            ->pluck('id')
            ->all();

        $unauthorizedCount = $totalSelected - count($authorizedIds);

        if ($authorizedIds === []) {
            return $this->redirectToIndexWithQuery($request)->with('toast', [
                'type' => 'warning',
                'title' => 'Action Not Allowed',
                'message' => "Updated: 0, Skipped: 0, Unauthorized: {$unauthorizedCount}.",
            ]);
        }

        if ($validated['action'] === 'delete') {
            $deletedCount = Training::query()->whereIn('id', $authorizedIds)->delete();
            $skippedCount = max(0, count($authorizedIds) - $deletedCount);

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

        $updatedCount = Training::query()
            ->whereIn('id', $authorizedIds)
            ->update([
                'is_active' => $status === 'active',
            ]);

        $skippedCount = max(0, count($authorizedIds) - $updatedCount);

        return $this->redirectToIndexWithQuery($request)->with('toast', [
            'type' => 'success',
            'title' => 'Bulk Update Complete',
            'message' => "Updated: {$updatedCount}, Skipped: {$skippedCount}, Unauthorized: {$unauthorizedCount}.",
        ]);
    }

    private function redirectToIndexWithQuery(Request $request): RedirectResponse
    {
        $query = $request->query();
        $url = route('trainings.index');

        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        return redirect()->to($url);
    }
}
