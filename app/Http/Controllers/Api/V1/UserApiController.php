<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserApiController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/users
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasAnyRole(['Admin', 'Manager'])) {
            return $this->forbidden();
        }

        $query = User::with('roles')
            ->when($request->filled('search'), fn ($q) => $q->search($request->query('search')))
            ->when($request->filled('role'), fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('slug', $request->query('role'))));

        $sort = $request->get('sort', 'name');
        $dir  = in_array($request->get('direction'), ['asc', 'desc'], true) ? $request->get('direction') : 'asc';
        if (in_array($sort, ['name', 'email', 'created_at'], true)) {
            $query->orderBy($sort, $dir);
        }

        $paginator = $query->paginate($request->integer('per_page', 15));

        return $this->paginated($paginator->through(fn ($u) => new UserResource($u)));
    }

    /**
     * POST /api/v1/users
     */
    public function store(Request $request): JsonResponse
    {
        if (! $request->user()->hasRole('Admin')) {
            return $this->forbidden();
        }

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*'=> ['integer', 'exists:roles,id'],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        if (! empty($validated['role_ids'])) {
            $user->roles()->sync($validated['role_ids']);
        }

        return $this->created(new UserResource($user->load('roles.permissions')));
    }

    /**
     * GET /api/v1/users/{user}
     */
    public function show(Request $request, User $user): JsonResponse
    {
        if (! $request->user()->hasAnyRole(['Admin', 'Manager'])) {
            return $this->forbidden();
        }

        $user->load('roles.permissions');

        return $this->success(new UserResource($user));
    }

    /**
     * PUT /api/v1/users/{user}
     */
    public function update(Request $request, User $user): JsonResponse
    {
        if (! $request->user()->hasRole('Admin')) {
            return $this->forbidden();
        }

        $validated = $request->validate([
            'name'      => ['sometimes', 'required', 'string', 'max:255'],
            'email'     => ['sometimes', 'required', 'email', 'unique:users,email,'.$user->id],
            'password'  => ['nullable', Password::defaults()],
            'role_ids'  => ['nullable', 'array'],
            'role_ids.*'=> ['integer', 'exists:roles,id'],
        ]);

        $user->update(array_filter([
            'name'     => $validated['name'] ?? null,
            'email'    => $validated['email'] ?? null,
            'password' => isset($validated['password']) ? Hash::make($validated['password']) : null,
        ]));

        if (array_key_exists('role_ids', $validated)) {
            $user->roles()->sync($validated['role_ids'] ?? []);
        }

        return $this->success(new UserResource($user->fresh('roles.permissions')));
    }

    /**
     * DELETE /api/v1/users/{user}
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();

        if (! $actor->hasRole('Admin')) {
            return $this->forbidden();
        }

        if ($user->isProtectedAccount()) {
            return $this->error('This account cannot be deleted.', null, 403);
        }

        if ($actor->id === $user->id) {
            return $this->error('You cannot delete your own account.', null, 403);
        }

        $user->delete();

        return $this->noContent('User deleted.');
    }
}
