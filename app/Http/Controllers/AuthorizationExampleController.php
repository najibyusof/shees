<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserUiPreference;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AuthorizationExampleController extends Controller
{
    public function usersIndex(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $pageKey = 'admin.users';
        $defaultPreferences = [
            'density' => 'comfortable',
            'defaultSort' => 'created_at',
            'defaultDirection' => 'desc',
            'visibleColumns' => [
                'id' => true,
                'name' => true,
                'email' => true,
                'created_at' => true,
            ],
        ];

        $savedPreferences = UserUiPreference::query()
            ->where('user_id', $request->user()->id)
            ->where('page_key', $pageKey)
            ->value('preferences');

        $serverPrefs = array_replace_recursive($defaultPreferences, is_array($savedPreferences) ? $savedPreferences : []);

        $allowedSortColumns = ['id', 'name', 'email', 'created_at'];
        $sort = $request->string('sort')->toString();
        $direction = strtolower($request->string('direction')->toString());

        if (! in_array($sort, $allowedSortColumns, true)) {
            $sort = 'created_at';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $users = User::query()
            ->select(['id', 'name', 'email', 'created_at'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('from'), function ($query) use ($request) {
                $query->whereDate('created_at', '>=', $request->string('from')->toString());
            })
            ->when($request->filled('to'), function ($query) use ($request) {
                $query->whereDate('created_at', '<=', $request->string('to')->toString());
            })
            ->orderBy($sort, $direction)
            ->paginate(8)
            ->withQueryString();

        return view('authorization.users-index', compact('users', 'sort', 'direction', 'serverPrefs'));
    }

    public function updateUsersPreferences(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $validated = $request->validate([
            'density' => ['required', 'in:comfortable,compact'],
            'defaultSort' => ['required', 'in:id,name,email,created_at'],
            'defaultDirection' => ['required', 'in:asc,desc'],
            'visibleColumns' => ['required', 'array'],
            'visibleColumns.id' => ['required', 'boolean'],
            'visibleColumns.name' => ['required', 'boolean'],
            'visibleColumns.email' => ['required', 'boolean'],
            'visibleColumns.created_at' => ['required', 'boolean'],
        ]);

        UserUiPreference::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'page_key' => 'admin.users',
            ],
            ['preferences' => $validated]
        );

        return response()->json([
            'message' => 'Preferences saved.',
            'toast' => [
                'type' => 'success',
                'title' => 'Preferences Updated',
                'message' => 'Your table settings were saved to your account.',
            ],
        ]);
    }

    public function auditLogs(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermissionTo('audits.view')) {
            abort(403, 'You are not allowed to view audit logs.');
        }

        $validated = $request->validate([
            'action' => ['nullable', 'in:create,update,delete,approve'],
            'module' => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 25);

        $logs = AuditLog::query()
            ->with('user:id,name')
            ->when(isset($validated['action']), function ($query) use ($validated) {
                $query->where('action', $validated['action']);
            })
            ->when(isset($validated['module']), function ($query) use ($validated) {
                $query->where('module', $validated['module']);
            })
            ->when(isset($validated['user_id']), function ($query) use ($validated) {
                $query->where('user_id', (int) $validated['user_id']);
            })
            ->when(isset($validated['from']), function ($query) use ($validated) {
                $query->whereDate('created_at', '>=', $validated['from']);
            })
            ->when(isset($validated['to']), function ($query) use ($validated) {
                $query->whereDate('created_at', '<=', $validated['to']);
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Audit logs retrieved successfully.',
            'data' => $logs,
        ]);
    }
}
