<?php

namespace Tests\Feature;

use App\Models\Inspection;
use App\Models\InspectionChecklist;
use App\Models\MobileAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Illuminate\Support\Str;
use Tests\TestCase;

class MobileApiAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function mobileAuthHeaders(User $user): array
    {
        $login = $this->mobileLogin($user, 'auth-device');

        return [
            'Authorization' => 'Bearer '.$login->json('data.token'),
            'Accept' => 'application/json',
        ];
    }

    private function mobileLogin(User $user, string $deviceName, ?string $ip = null): TestResponse
    {
        $plainPassword = 'password';
        $user->update(['password' => bcrypt($plainPassword)]);

        $requestIp = $ip ?: '10.0.0.'.random_int(2, 240);

        $login = $this->withServerVariables(['REMOTE_ADDR' => $requestIp])
            ->postJson(route('api.inspection.auth.login'), [
                'email' => $user->email,
                'password' => $plainPassword,
                'device_name' => $deviceName,
            ]);

        $login->assertCreated();

        return $login;
    }

    public function test_mobile_auth_required_for_protected_endpoints(): void
    {
        $this->getJson(route('api.inspection.checklists.index'))
            ->assertStatus(401)
            ->assertJsonPath('message', 'Missing mobile access token.');

        $this->getJson(route('api.inspection.sync.contract'))
            ->assertStatus(401)
            ->assertJsonPath('message', 'Missing mobile access token.');
    }

    public function test_sync_contract_endpoint_returns_supported_contract_for_authenticated_mobile_client(): void
    {
        $user = User::factory()->create();
        $headers = $this->mobileAuthHeaders($user);

        $this->withHeaders($headers)
            ->getJson(route('api.inspection.sync.contract'))
            ->assertOk()
            ->assertJsonPath('data.name', 'inspection-sync')
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.capabilities.upload', true)
            ->assertJsonPath('data.capabilities.download', true);
    }

    public function test_unsupported_sync_contract_is_rejected(): void
    {
        $user = User::factory()->create();
        $headers = $this->mobileAuthHeaders($user);

        $checklist = InspectionChecklist::query()->create([
            'offline_uuid' => (string) Str::uuid(),
            'title' => 'Auth Contract Checklist',
            'version' => 1,
            'is_active' => true,
            'sync_status' => 'synced',
            'last_synced_at' => now(),
        ]);

        $inspection = Inspection::query()->create([
            'offline_uuid' => (string) Str::uuid(),
            'inspection_checklist_id' => $checklist->id,
            'inspector_id' => $user->id,
            'status' => 'draft',
            'sync_status' => 'pending_sync',
        ]);

        $this->withHeaders($headers)
            ->postJson(route('api.inspection.sync.upload'), [
                'inspection_id' => $inspection->id,
                'user_id' => $user->id,
                'entity_type' => 'inspection',
                'entity_offline_uuid' => $inspection->offline_uuid,
                'contract_name' => 'inspection-sync',
                'contract_version' => 99,
                'payload' => ['inspection' => ['status' => 'draft']],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('contract_version');

        $this->withHeaders($headers)
            ->postJson(route('api.inspection.auth.logout'))
            ->assertOk();

        $this->getJson(route('api.inspection.checklists.index'))
            ->assertStatus(401);
    }

    public function test_authenticated_mobile_client_can_list_and_revoke_own_sessions(): void
    {
        $user = User::factory()->create();
        $firstLogin = $this->mobileLogin($user, 'auth-device-1');
        $secondLogin = $this->mobileLogin($user, 'auth-device-2');

        $firstHeaders = [
            'Authorization' => 'Bearer '.$firstLogin->json('data.token'),
            'Accept' => 'application/json',
        ];

        $secondToken = $secondLogin->json('data.token');
        $secondSessionId = $secondLogin->json('data.session_id');

        $this->withHeaders($firstHeaders)
            ->getJson(route('api.inspection.auth.sessions.index'))
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->withHeaders($firstHeaders)
            ->postJson(route('api.inspection.auth.sessions.revoke', $secondSessionId))
            ->assertOk()
            ->assertJsonPath('message', 'Session revoked.');

        $this->withHeaders([
            'Authorization' => 'Bearer '.$secondToken,
            'Accept' => 'application/json',
        ])->getJson(route('api.inspection.checklists.index'))
            ->assertStatus(401)
            ->assertJsonPath('message', 'Invalid or expired mobile access token.');
    }

    public function test_mobile_token_can_be_rotated_and_old_token_is_invalidated(): void
    {
        $user = User::factory()->create();
        $login = $this->mobileLogin($user, 'rotate-device');

        $oldToken = $login->json('data.token');
        $oldSessionId = $login->json('data.session_id');

        $rotateResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$oldToken,
            'Accept' => 'application/json',
        ])->postJson(route('api.inspection.auth.rotate'), [
            'device_name' => 'rotate-device',
        ]);

        $rotateResponse->assertCreated();
        $newToken = $rotateResponse->json('data.token');

        $this->withHeaders([
            'Authorization' => 'Bearer '.$oldToken,
            'Accept' => 'application/json',
        ])->getJson(route('api.inspection.checklists.index'))
            ->assertStatus(401);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$newToken,
            'Accept' => 'application/json',
        ])->getJson(route('api.inspection.checklists.index'))
            ->assertOk();

        $this->assertDatabaseHas('mobile_access_tokens', [
            'id' => $oldSessionId,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('mobile_access_tokens', [
            'token_hash' => hash('sha256', $newToken),
            'is_active' => true,
            'user_id' => $user->id,
        ]);

        $oldRecord = MobileAccessToken::query()->find($oldSessionId);
        $this->assertNotNull($oldRecord);
        $this->assertNotNull($oldRecord->replaced_by_token_id);
    }

    public function test_authenticated_mobile_client_can_rename_own_session(): void
    {
        $user = User::factory()->create();
        $login = $this->mobileLogin($user, 'old-device-name');

        $token = $login->json('data.token');
        $sessionId = $login->json('data.session_id');

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->patchJson(route('api.inspection.auth.sessions.rename', $sessionId), [
            'device_name' => 'renamed-device',
        ])
            ->assertOk()
            ->assertJsonPath('data.device_name', 'renamed-device');

        $this->assertDatabaseHas('mobile_access_tokens', [
            'id' => $sessionId,
            'name' => 'renamed-device',
            'user_id' => $user->id,
        ]);
    }

    public function test_mobile_client_cannot_rename_another_users_session(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        $ownerLogin = $this->mobileLogin($owner, 'owner-device');
        $attackerLogin = $this->mobileLogin($attacker, 'attacker-device');

        $this->withHeaders([
            'Authorization' => 'Bearer '.$attackerLogin->json('data.token'),
            'Accept' => 'application/json',
        ])->patchJson(route('api.inspection.auth.sessions.rename', $ownerLogin->json('data.session_id')), [
            'device_name' => 'stolen-name',
        ])
            ->assertStatus(403)
            ->assertJsonPath('message', 'You can only update your own sessions.');
    }

    public function test_mobile_login_is_rate_limited_after_too_many_attempts(): void
    {
        $user = User::factory()->create();
        $ip = '10.9.9.9';

        for ($i = 0; $i < 10; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->postJson(route('api.inspection.auth.login'), [
                    'email' => $user->email,
                    'password' => 'wrong-password',
                    'device_name' => 'throttle-device',
                ]);
        }

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson(route('api.inspection.auth.login'), [
                'email' => $user->email,
                'password' => 'wrong-password',
                'device_name' => 'throttle-device',
            ])->assertStatus(429);
    }
}
