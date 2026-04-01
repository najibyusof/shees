<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MobileAccessToken;
use App\Models\User;
use App\Services\MobileTokenService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly MobileTokenService $tokenService)
    {
    }

    /**
     * POST /api/v1/auth/login
     *
     * Returns a mobile access token alongside the user's roles and permissions.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'       => ['required', 'email'],
            'password'    => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
            'ttl_minutes' => ['nullable', 'integer', 'min:10', 'max:43200'],
        ]);

        /** @var User|null $user */
        $user = User::query()
            ->where('email', $validated['email'])
            ->with('roles.permissions')
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return $this->error('Invalid credentials.', null, 401);
        }

        $issued = $this->tokenService->issueToken(
            $user,
            $validated['device_name'],
            (int) ($validated['ttl_minutes'] ?? 10080)
        );

        $formattedUser = $this->formatUser($user);
        $dashboardToken = $user->createToken(
            $validated['device_name'].'-dashboard',
            collect($formattedUser['permissions'])->values()->all()
        )->plainTextToken;

        return $this->success(
            data: [
                'token'       => $issued['token'],
                'token_type'  => 'Bearer',
                'dashboard_token' => $dashboardToken,
                'session_id'  => $issued['record']->id,
                'device_name' => $issued['record']->name,
                'expires_at'  => optional($issued['record']->expires_at)->toIso8601String(),
                'user'        => $formattedUser,
                'roles'       => collect($formattedUser['roles'])->pluck('name')->values(),
                'permissions' => $formattedUser['permissions'],
            ],
            message: 'Login successful.'
        );
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $tokenId = $request->attributes->get('mobile_token_id');

        if (! $tokenId) {
            return $this->error('No active mobile token context.', null, 400);
        }

        $token = MobileAccessToken::query()->find($tokenId);

        if (! $token) {
            return $this->error('Token not found.', null, 404);
        }

        $this->tokenService->revoke($token);

        return $this->success(null, 'Logged out successfully.');
    }

    /**
     * GET /api/v1/user
     */
    public function user(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->load('roles.permissions');

        return $this->success($this->formatUser($user));
    }

    // -------------------------------------------------------------------------

    private function formatUser(User $user): array
    {
        $roles = $user->roles->map(fn ($role) => [
            'id'          => $role->id,
            'name'        => $role->name,
            'slug'        => $role->slug,
            'permissions' => $role->permissions->pluck('name')->values(),
        ]);

        $permissions = $user->roles
            ->flatMap(fn ($role) => $role->permissions->pluck('name'))
            ->unique()
            ->values();

        return [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'roles'       => $roles,
            'permissions' => $permissions,
        ];
    }
}
