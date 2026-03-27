<?php

namespace App\Services;

use App\Models\MobileAccessToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MobileTokenService
{
    public function issueToken(User $user, string $deviceName, int $ttlMinutes = 10080): array
    {
        $plainToken = Str::random(64);

        $record = MobileAccessToken::query()->create([
            'user_id' => $user->id,
            'name' => $deviceName,
            'token_hash' => hash('sha256', $plainToken),
            'is_active' => true,
            'last_used_at' => now(),
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);

        return [
            'token' => $plainToken,
            'record' => $record,
        ];
    }

    public function issueRotationToken(MobileAccessToken $currentToken, ?string $deviceName = null): array
    {
        return DB::transaction(function () use ($currentToken, $deviceName) {
            $currentToken->refresh();

            $remainingMinutes = $currentToken->expires_at
                ? max(10, now()->diffInMinutes($currentToken->expires_at, false))
                : 10080;

            $issued = $this->issueToken(
                $currentToken->user,
                $deviceName ?: $currentToken->name,
                $remainingMinutes
            );

            $this->revoke($currentToken, $issued['record']->id);

            return $issued;
        });
    }

    public function findValidToken(string $plainToken): ?MobileAccessToken
    {
        $hashed = hash('sha256', $plainToken);

        return MobileAccessToken::query()
            ->with('user')
            ->where('token_hash', $hashed)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    public function touch(MobileAccessToken $token): void
    {
        $token->update(['last_used_at' => now()]);
    }

    public function revoke(MobileAccessToken $token, ?int $replacedByTokenId = null): void
    {
        $token->update([
            'is_active' => false,
            'revoked_at' => now(),
            'rotated_at' => $replacedByTokenId ? now() : $token->rotated_at,
            'replaced_by_token_id' => $replacedByTokenId,
        ]);
    }

    public function activeSessionsForUser(User $user)
    {
        return MobileAccessToken::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->orderByDesc('last_used_at')
            ->orderByDesc('id')
            ->get();
    }

    public function renameSession(MobileAccessToken $token, string $deviceName): MobileAccessToken
    {
        $token->update([
            'name' => $deviceName,
        ]);

        return $token->refresh();
    }
}
