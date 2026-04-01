<?php

namespace Tests\Feature;

use App\Models\CauseType;
use App\Models\Incident;
use App\Models\IncidentClassification;
use App\Models\IncidentLocation;
use App\Models\IncidentStatus;
use App\Models\IncidentType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkActivity;
use App\Models\WorkPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Verifies per-action permission middleware granularity on all V1 API resources.
 *
 * Strategy:
 *  - Grant the MINIMUM permission for a given set of actions.
 *  - Assert those actions are allowed (2xx) and all OTHER actions are blocked (403).
 */
class ApiResourceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLookupData();
    }

    private function seedLookupData(): void
    {
        if (! WorkPackage::exists()) {
            WorkPackage::create(['name' => 'Test', 'code' => 'T001', 'description' => 'Test']);
        }
        if (! WorkActivity::exists()) {
            WorkActivity::create(['name' => 'Test', 'code' => 'T001', 'description' => 'Test']);
        }
        if (! IncidentLocation::exists()) {
            IncidentLocation::create(['name' => 'Test', 'code' => 'T001', 'location_type_id' => 1]);
        }
        if (! IncidentType::exists()) {
            IncidentType::create(['name' => 'Test', 'code' => 'T001']);
        }
        if (! IncidentStatus::exists()) {
            IncidentStatus::create(['name' => 'Draft', 'code' => 'draft']);
        }
        if (! IncidentClassification::exists()) {
            IncidentClassification::create(['name' => 'Test', 'code' => 'T001']);
        }
        if (! CauseType::exists()) {
            CauseType::create(['name' => 'Test', 'code' => 'T001']);
        }
    }

    private function userWithPermissions(string ...$permissions): User
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);
        $role = Role::factory()->create();
        $permissionModels = collect($permissions)
            ->map(fn (string $name) => Permission::firstOrCreate(['name' => $name]));
        $role->permissions()->sync($permissionModels->pluck('id'));
        $user->roles()->sync([$role->id]);

        return $user;
    }

    private function mobileHeaders(User $user): array
    {
        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'test',
        ]);
        $response->assertOk();

        return ['Authorization' => 'Bearer '.$response->json('data.token')];
    }

    public function test_view_incident_allows_index(): void
    {
        $user = $this->userWithPermissions('view_incident');
        $this->withHeaders($this->mobileHeaders($user))
            ->getJson(route('api.v1.incidents.index'))
            ->assertOk();
    }

    public function test_no_view_incident_blocks_index(): void
    {
        $user = $this->userWithPermissions();
        $this->withHeaders($this->mobileHeaders($user))
            ->getJson(route('api.v1.incidents.index'))
            ->assertForbidden();
    }

    public function test_create_incident_allows_store(): void
    {
        $user = $this->userWithPermissions('view_incident', 'create_incident');
        $response = $this->withHeaders($this->mobileHeaders($user))
            ->postJson(route('api.v1.incidents.store'), []);

        // Should NOT be 403 (middleware passed)
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_no_create_incident_blocks_store(): void
    {
        $user = $this->userWithPermissions('view_incident');
        $this->withHeaders($this->mobileHeaders($user))
            ->postJson(route('api.v1.incidents.store'), [])
            ->assertForbidden();
    }

    public function test_edit_incident_allows_update(): void
    {
        $user = $this->userWithPermissions('view_incident', 'edit_incident');
        $incident = Incident::factory()->create(['reported_by' => $user->id]);
        $response = $this->withHeaders($this->mobileHeaders($user))
            ->putJson(route('api.v1.incidents.update', $incident), []);

        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_unauthenticated_request_blocked(): void
    {
        $this->getJson(route('api.v1.incidents.index'))->assertUnauthorized();
    }
}
