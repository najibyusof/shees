<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionGroupHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:viewAny,' . Role::class)->only('index');
        $this->middleware('can:create,' . Role::class)->only(['create', 'store', 'clone']);
        $this->middleware('can:view,role')->only(['show', 'export']);
        $this->middleware('can:update,role')->only(['edit', 'update']);
        $this->middleware('can:delete,role')->only('destroy');
    }

    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $totalPermissions = Permission::query()->count();

        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.roles.index', [
            'roles' => $roles,
            'search' => $search,
            'totalPermissions' => $totalPermissions,
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.create', $this->formViewData());
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $role = DB::transaction(function () use ($validated) {
            $role = Role::query()->create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['slug']),
            ]);

            $role->permissions()->sync($validated['permission_ids'] ?? []);

            return $role;
        });

        return redirect()
            ->route('admin.roles.show', $role)
            ->with('toast', [
                'type' => 'success',
                'title' => 'Role Created',
                'message' => 'The role and permission assignments were saved successfully.',
            ]);
    }

    public function show(Role $role): View
    {
        $role->loadCount(['users', 'permissions']);
        $role->load(['permissions:id,name,description']);

        $assignedUsers = $role->users()
            ->orderBy('name')
            ->limit(8)
            ->get(['users.id', 'users.name', 'users.email']);

        return view('admin.roles.show', [
            'role' => $role,
            'assignedUsers' => $assignedUsers,
            'permissionGroups' => PermissionGroupHelper::group($role->permissions),
            'totalPermissions' => Permission::query()->count(),
        ]);
    }

    public function edit(Role $role): View
    {
        $role->load('permissions:id,name');

        return view('admin.roles.edit', $this->formViewData($role));
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $role) {
            $role->update([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['slug']),
            ]);

            $role->permissions()->sync($validated['permission_ids'] ?? []);
        });

        return redirect()
            ->route('admin.roles.show', $role)
            ->with('toast', [
                'type' => 'success',
                'title' => 'Role Updated',
                'message' => 'The role details and permission assignments were updated successfully.',
            ]);
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->users()->exists()) {
            return redirect()
                ->route('admin.roles.show', $role)
                ->with('toast', [
                    'type' => 'error',
                    'title' => 'Delete Blocked',
                    'message' => 'This role is still assigned to users and cannot be deleted yet.',
                ]);
        }

        $role->permissions()->detach();
        $role->delete();

        return redirect()
            ->route('admin.roles')
            ->with('toast', [
                'type' => 'success',
                'title' => 'Role Deleted',
                'message' => 'The role was deleted successfully.',
            ]);
    }

    public function clone(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $clonedRole = DB::transaction(function () use ($role) {
            $baseName = $role->name . ' (Clone)';
            $name = $baseName;
            $counter = 1;
            while (Role::query()->where('name', $name)->exists()) {
                $name = $baseName . ' ' . ++$counter;
            }

            $baseSlug = $role->slug . '-clone';
            $slug = $baseSlug;
            $counter = 1;
            while (Role::query()->where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . ++$counter;
            }

            $cloned = Role::query()->create([
                'name' => $name,
                'slug' => $slug,
                'description' => $role->description ? $role->description . ' (cloned)' : null,
            ]);

            $cloned->permissions()->sync($role->permissions->pluck('id')->all());

            return $cloned;
        });

        return redirect()
            ->route('admin.roles.show', $clonedRole)
            ->with('toast', [
                'type' => 'success',
                'title' => 'Role Cloned',
                'message' => "The role '{$role->name}' was successfully cloned as '{$clonedRole->name}'.",
            ]);
    }

    public function export(Request $request, Role $role, string $format): StreamedResponse|Response
    {
        $this->authorize('view', $role);

        if (!in_array($format, ['csv', 'pdf'], true)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $role->load(['permissions:id,name,description']);
        $permissionGroups = PermissionGroupHelper::group($role->permissions);

        if ($format === 'csv') {
            return $this->exportCsv($role, $permissionGroups);
        }

        return $this->exportPdf($role, $permissionGroups);
    }

    private function exportCsv(Role $role, $permissionGroups): StreamedResponse
    {
        $filename = 'role_' . Str::slug($role->name) . '_permissions_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($permissionGroups) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Module', 'Permission Name', 'Action', 'Description']);

            foreach ($permissionGroups as $group) {
                foreach ($group['permissions'] as $permission) {
                    fputcsv($handle, [
                        (string) $group['label'],
                        (string) $permission['name'],
                        (string) $permission['action_label'],
                        (string) ($permission['description'] ?? ''),
                    ]);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function exportPdf(Role $role, $permissionGroups): Response
    {
        $pdf = Pdf::loadView('admin.roles.export-pdf', [
            'role' => $role,
            'permissionGroups' => $permissionGroups,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('role_' . Str::slug($role->name) . '_permissions_' . now()->format('Ymd_His') . '.pdf');
    }

    private function formViewData(?Role $role = null): array
    {
        $permissions = Permission::query()
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        return [
            'role' => $role,
            'permissionGroups' => PermissionGroupHelper::group($permissions),
            'selectedPermissionIds' => $role
                ? $role->permissions->pluck('id')->map(fn ($id) => (string) $id)->values()->all()
                : [],
            'totalPermissions' => $permissions->count(),
        ];
    }
}
