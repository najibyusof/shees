<?php

namespace App\Http\Controllers\Api\V1;

use OpenAPI\Annotations as OA;
use App\Http\Controllers\Controller;
use App\Models\MobileAccessToken;
use App\Models\User;
use App\Services\MobileTokenService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @OA\Tag(
 *     name="Auth",
 *     description="Authentication endpoints"
 * )
 */
class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly MobileTokenService $tokenService)
    {
    }

    /**
     * Login
     *
     * Authenticate user and issue a mobile access token.
     *
     * @OA\Post(
     *     path="/api/auth/login",
     *     operationId="login",
     *     tags={"Auth"},
     *     summary="User login",
     *     description="Authenticate user with email and password, returns Bearer token",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Login credentials",
     *         @OA\JsonContent(
     *             required={"email", "password", "device_name"},
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 example="user@example.com",
     *                 description="User email address"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 format="password",
     *                 example="password123",
     *                 description="User password"
     *             ),
     *             @OA\Property(
     *                 property="device_name",
     *                 type="string",
     *                 example="Mobile App iOS",
     *                 description="Device identifier for multi-device support"
     *             ),
     *             @OA\Property(
     *                 property="ttl_minutes",
     *                 type="integer",
     *                 example=10080,
     *                 description="Token time to live in minutes (default: 7 days)"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Login successful."
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="token",
     *                     type="string",
     *                     example="token_value_here",
     *                     description="Bearer token for API requests"
     *                 ),
     *                 @OA\Property(
     *                     property="token_type",
     *                     type="string",
     *                     example="Bearer"
     *                 ),
     *                 @OA\Property(
     *                     property="session_id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="device_name",
     *                     type="string",
     *                     example="Mobile App iOS"
     *                 ),
     *                 @OA\Property(
     *                     property="expires_at",
     *                     type="string",
     *                     format="date-time",
     *                     example="2026-04-04T10:30:00Z"
     *                 ),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     ref="#/components/schemas/User"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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

        return $this->success(
            data: [
                'token'       => $issued['token'],
                'token_type'  => 'Bearer',
                'session_id'  => $issued['record']->id,
                'device_name' => $issued['record']->name,
                'expires_at'  => optional($issued['record']->expires_at)->toIso8601String(),
                'user'        => $this->formatUser($user),
            ],
            message: 'Login successful.'
        );
    }

    /**
     * Logout
     *
     * Revoke current mobile access token.
     *
     * @OA\Post(
     *     path="/api/auth/logout",
     *     operationId="logout",
     *     tags={"Auth"},
     *     summary="User logout",
     *     description="Revoke the current user's mobile access token",
     *     security={{"bearer_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logged out successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Logged out successfully."
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="null"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="No active token",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * Get Current User
     *
     * Retrieve authenticated user profile.
     *
     * @OA\Get(
     *     path="/api/auth/user",
     *     operationId="getUser",
     *     tags={"Auth"},
     *     summary="Get current user",
     *     description="Retrieve the profile of the authenticated user",
     *     security={{"bearer_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User profile retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="User profile retrieved"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/User"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
        return [
            'id'       => $user->id,
            'name'     => $user->name,
            'email'    => $user->email,
            'roles'    => $user->roles->pluck('name'),
            'permissions' => $user->roles->flatMap(fn ($role) => $role->permissions->pluck('name'))->unique(),
        ];
    }
}
