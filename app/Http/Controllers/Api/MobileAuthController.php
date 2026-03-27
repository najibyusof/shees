<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobileAccessToken;
use App\Models\User;
use App\Services\MobileTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MobileAuthController extends Controller
{
    public function __construct(private readonly MobileTokenService $tokenService) {}

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
            'ttl_minutes' => ['nullable', 'integer', 'min:10', 'max:43200'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $issued = $this->tokenService->issueToken(
            $user,
            $validated['device_name'],
            (int) ($validated['ttl_minutes'] ?? 10080)
        );

        return response()->json([
            'data' => [
                'token' => $issued['token'],
                'token_type' => 'Bearer',
                'session_id' => $issued['record']->id,
                'device_name' => $issued['record']->name,
                'expires_at' => optional($issued['record']->expires_at)->toIso8601String(),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ],
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $tokenId = $request->attributes->get('mobile_token_id');

        if (! $tokenId) {
            return response()->json(['message' => 'No active mobile token context.'], 400);
        }

        $token = MobileAccessToken::query()->find($tokenId);

        if (! $token) {
            return response()->json(['message' => 'Token not found.'], 404);
        }

        $this->tokenService->revoke($token);

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function sessions(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => $this->tokenService
                ->activeSessionsForUser($user)
                ->map(fn (MobileAccessToken $token) => [
                    'id' => $token->id,
                    'device_name' => $token->name,
                    'last_used_at' => optional($token->last_used_at)->toIso8601String(),
                    'expires_at' => optional($token->expires_at)->toIso8601String(),
                    'is_current' => (int) $request->attributes->get('mobile_token_id') === $token->id,
                ])
                ->values(),
        ]);
    }

    public function revokeSession(Request $request, MobileAccessToken $mobileAccessToken): JsonResponse
    {
        if ($mobileAccessToken->user_id !== $request->user()?->id) {
            return response()->json(['message' => 'You can only revoke your own sessions.'], 403);
        }

        $this->tokenService->revoke($mobileAccessToken);

        return response()->json(['message' => 'Session revoked.']);
    }

    public function renameSession(Request $request, MobileAccessToken $mobileAccessToken): JsonResponse
    {
        if ($mobileAccessToken->user_id !== $request->user()?->id) {
            return response()->json(['message' => 'You can only update your own sessions.'], 403);
        }

        $validated = $request->validate([
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $renamed = $this->tokenService->renameSession($mobileAccessToken, $validated['device_name']);

        return response()->json([
            'data' => [
                'id' => $renamed->id,
                'device_name' => $renamed->name,
                'is_active' => $renamed->is_active,
                'last_used_at' => optional($renamed->last_used_at)->toIso8601String(),
                'expires_at' => optional($renamed->expires_at)->toIso8601String(),
            ],
        ]);
    }

    public function rotate(Request $request): JsonResponse
    {
        $tokenId = $request->attributes->get('mobile_token_id');

        if (! $tokenId) {
            return response()->json(['message' => 'No active mobile token context.'], 400);
        }

        $currentToken = MobileAccessToken::query()->with('user')->find($tokenId);

        if (! $currentToken || ! $currentToken->user) {
            return response()->json(['message' => 'Token not found.'], 404);
        }

        $validated = $request->validate([
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $issued = $this->tokenService->issueRotationToken(
            $currentToken,
            $validated['device_name'] ?? null
        );

        return response()->json([
            'data' => [
                'token' => $issued['token'],
                'token_type' => 'Bearer',
                'session_id' => $issued['record']->id,
                'device_name' => $issued['record']->name,
                'expires_at' => optional($issued['record']->expires_at)->toIso8601String(),
            ],
        ], 201);
    }
}
