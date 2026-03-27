<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:viewAny,' . User::class)->only('index');
        $this->middleware('can:viewAny,' . User::class)->only('bulkAction');
        $this->middleware('can:create,' . User::class)->only(['create', 'store']);
        $this->middleware('can:view,user')->only('show');
        $this->middleware('can:update,user')->only(['edit', 'update']);
        $this->middleware('can:delete,user')->only('destroy');
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'in:verified,unverified'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort' => ['nullable', 'string', 'in:name,email,created_at'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50'],
        ]);

        $sort = (string) ($filters['sort'] ?? 'created_at');
        $direction = (string) ($filters['direction'] ?? 'desc');
        $perPage = (int) ($filters['per_page'] ?? 10);

        $users = User::query()
            ->select(['id', 'name', 'email', 'email_verified_at', 'created_at'])
            ->with('roles:id,name')
            ->withCount(['reportedIncidents', 'trainings', 'inspections', 'siteAuditsCreated'])
            ->search($filters['search'] ?? null)
            ->verificationStatus($filters['status'] ?? null)
            ->roleIds($filters['role_ids'] ?? [])
            ->dateBetween($filters['date_from'] ?? null, $filters['date_to'] ?? null)
            ->sortByField($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        $roles = Role::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => [
                'search' => (string) ($filters['search'] ?? ''),
                'status' => (string) ($filters['status'] ?? ''),
                'role_ids' => array_map('intval', $filters['role_ids'] ?? []),
                'date_from' => (string) ($filters['date_from'] ?? ''),
                'date_to' => (string) ($filters['date_to'] ?? ''),
                'per_page' => (string) ($filters['per_page'] ?? '10'),
            ],
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['integer', 'exists:users,id'],
            'action' => ['required', 'string', 'in:delete,update_status'],
            'status' => ['nullable', 'string', 'in:verified,unverified'],
        ]);

        $selectedIds = array_values(array_unique(array_map('intval', $validated['selected'])));
        $totalSelected = count($selectedIds);
        $selectedUsers = User::query()->whereIn('id', $selectedIds)->get();

        if ($selectedUsers->isEmpty()) {
            return $this->redirectToIndexWithQuery($request)->with('toast', [
                'type' => 'warning',
                'title' => 'No Users Selected',
                'message' => 'No valid users were found for this action.',
            ]);
        }

        if ($validated['action'] === 'delete') {
            $deletableCandidates = $selectedUsers
                ->filter(fn (User $selectedUser) => $request->user()->can('delete', $selectedUser));

            $deletableIds = $deletableCandidates
                ->filter(fn (User $selectedUser) => ! $this->hasOperationalData($selectedUser))
                ->pluck('id')
                ->all();

            $unauthorizedCount = $totalSelected - $deletableCandidates->count();
            $skippedCount = $deletableCandidates->count() - count($deletableIds);

            if ($deletableIds === []) {
                return $this->redirectToIndexWithQuery($request)->with('toast', [
                    'type' => 'warning',
                    'title' => 'Delete Blocked',
                    'message' => "Deleted: 0, Skipped: {$skippedCount}, Unauthorized: {$unauthorizedCount}.",
                ]);
            }

            $deletedCount = User::query()->whereIn('id', $deletableIds)->delete();

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

        $updatableIds = $selectedUsers
            ->filter(fn (User $selectedUser) => $request->user()->can('update', $selectedUser))
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

        $updatedCount = User::query()
            ->whereIn('id', $updatableIds)
            ->update([
                'email_verified_at' => $status === 'verified' ? now() : null,
            ]);

        $skippedCount = max(0, count($updatableIds) - $updatedCount);

        return $this->redirectToIndexWithQuery($request)->with('toast', [
            'type' => 'success',
            'title' => 'Bulk Update Complete',
            'message' => "Updated: {$updatedCount}, Skipped: {$skippedCount}, Unauthorized: {$unauthorizedCount}.",
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated) {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            $user->roles()->sync($validated['role_ids']);

            return $user;
        });

        return redirect()
            ->route('admin.users.show', $user)
            ->with('toast', [
                'type' => 'success',
                'title' => 'User Created',
                'message' => 'The user account was created successfully.',
            ]);
    }

    public function show(User $user): View
    {
        $user->load([
            'roles:id,name',
            'reportedIncidents' => fn ($query) => $query->latest('datetime')->limit(5),
        ]);

        $recentTrainings = $user->trainings()
            ->orderByPivot('assigned_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.users.show', [
            'managedUser' => $user,
            'recentTrainings' => $recentTrainings,
        ]);
    }

    public function edit(User $user): View
    {
        $user->load('roles:id,name');

        return view('admin.users.edit', [
            'managedUser' => $user,
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $user) {
            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            if (! empty($validated['password'])) {
                $user->password = $validated['password'];
            }

            $user->save();
            $user->roles()->sync($validated['role_ids']);
        });

        return redirect()
            ->route('admin.users.show', $user)
            ->with('toast', [
                'type' => 'success',
                'title' => 'User Updated',
                'message' => 'The user account details were updated successfully.',
            ]);
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($this->hasOperationalData($user)) {
            return redirect()
                ->route('admin.users.show', $user)
                ->with('toast', [
                    'type' => 'error',
                    'title' => 'Delete Blocked',
                    'message' => 'This user cannot be deleted because operational records are still linked to the account.',
                ]);
        }

        $user->delete();

        return redirect()
            ->route('admin.users')
            ->with('toast', [
                'type' => 'success',
                'title' => 'User Deleted',
                'message' => 'The user account was deleted successfully.',
            ]);
    }

    private function hasOperationalData(User $user): bool
    {
        return $user->reportedIncidents()->exists()
            || $user->trainings()->exists()
            || $user->inspections()->exists()
            || $user->siteAuditsCreated()->exists()
            || $user->certificates()->exists()
            || $user->ncrReportsCreated()->exists();
    }

    private function redirectToIndexWithQuery(Request $request): RedirectResponse
    {
        $query = $request->query();
        $url = route('admin.users');

        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        return redirect()->to($url);
    }
}
