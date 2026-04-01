<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Incident;
use App\Models\IncidentClassification;
use App\Models\IncidentLocation;
use App\Models\IncidentType;
use App\Models\LocationType;
use App\Models\Role;
use App\Models\Subcontractor;
use App\Models\User;
use App\Models\WorkActivity;
use App\Models\WorkPackage;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiDashboardFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function attachDashboardAccess(User $user): void
    {
        $permission = Permission::query()->firstOrCreate(['name' => 'view_dashboard']);
        $role = Role::query()->firstOrCreate(
            ['slug' => 'dashboard-test-role'],
            ['name' => 'Dashboard Test Role']
        );

        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $user->roles()->syncWithoutDetaching([$role->id]);
    }

    public function test_dashboard_endpoint_requires_sanctum_authentication(): void
    {
        $this->getJson(route('api.dashboard'))
            ->assertStatus(401);
    }

    public function test_dashboard_endpoint_returns_role_based_widgets_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $permissions = Permission::factory()->count(3)->sequence(
            ['name' => 'view_dashboard'],
            ['name' => 'review_incident'],
            ['name' => 'request_closure'],
        )->create();

        $role = Role::factory()->create([
            'name' => 'HOD HSSE',
            'slug' => 'hod-hsse',
        ]);
        $role->permissions()->sync($permissions->pluck('id')->all());
        $user->roles()->sync([$role->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson(route('api.dashboard'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.role', 'HOD HSSE')
            ->assertJsonStructure([
                'data' => [
                    'role',
                    'roles',
                    'widgets' => [
                        'pending_draft_review',
                        'final_report_submissions',
                        'closure_requests',
                        'incident_escalation_overview',
                    ],
                ],
            ]);
    }

    public function test_v1_login_response_contains_dashboard_token_for_sanctum_dashboard_access(): void
    {
        $user = User::factory()->create([
            'email' => 'dashboard@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->postJson(route('api.v1.auth.login'), [
            'email' => 'dashboard@example.com',
            'password' => 'password',
            'device_name' => 'api-test-device',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'dashboard_token',
                    'user',
                    'roles',
                    'permissions',
                ],
            ]);
    }

    public function test_dashboard_logout_revokes_current_sanctum_dashboard_token(): void
    {
        $user = User::factory()->create();
        $this->attachDashboardAccess($user);
        $plainToken = $user->createToken('dashboard-device', ['view_incident'])->plainTextToken;
        $tokenId = (int) explode('|', $plainToken)[0];

        $this->withHeaders([
            'Authorization' => 'Bearer '.$plainToken,
            'Accept' => 'application/json',
        ])->postJson(route('api.dashboard.logout'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Dashboard session revoked.');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenId,
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);
    }

    public function test_dashboard_endpoint_is_rate_limited_per_authenticated_user(): void
    {
        $user = User::factory()->create();
        $this->attachDashboardAccess($user);
        $plainToken = $user->createToken('dashboard-rate-limit', ['view_incident'])->plainTextToken;
        $headers = [
            'Authorization' => 'Bearer '.$plainToken,
            'Accept' => 'application/json',
        ];

        for ($attempt = 0; $attempt < 60; $attempt++) {
            $this->withHeaders($headers)
                ->getJson(route('api.dashboard'))
                ->assertOk();
        }

        $this->withHeaders($headers)
            ->getJson(route('api.dashboard'))
            ->assertStatus(429);
    }

    public function test_dashboard_analytics_worker_sees_only_own_incident_and_worker_data(): void
    {
        $workerUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $workerRole = Role::query()->firstOrCreate(
            ['slug' => 'worker'],
            ['name' => 'Worker']
        );

        $permissions = Permission::query()->whereIn('name', [
            'view_dashboard',
            'view_incident',
            'view_worker',
        ])->pluck('id');

        if ($permissions->count() !== 3) {
            $created = collect([
                Permission::query()->firstOrCreate(['name' => 'view_dashboard'])->id,
                Permission::query()->firstOrCreate(['name' => 'view_incident'])->id,
                Permission::query()->firstOrCreate(['name' => 'view_worker'])->id,
            ]);
            $permissions = $created;
        }

        $workerRole->permissions()->syncWithoutDetaching($permissions->all());
        $workerUser->roles()->syncWithoutDetaching([$workerRole->id]);

        Incident::query()->create($this->requiredIncidentAttributes($workerUser, [
            'status' => 'open',
            'classification' => 'Near Miss',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]));

        Incident::query()->create($this->requiredIncidentAttributes($otherUser, [
            'status' => 'closed',
            'classification' => 'Major',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]));

        Worker::factory()->create([
            'user_id' => $workerUser->id,
            'status' => 'active',
        ]);

        Worker::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'inactive',
        ]);

        Sanctum::actingAs($workerUser);

        $response = $this->getJson(route('api.dashboard.analytics', ['module' => 'all']));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.incident.total_incidents', 1)
            ->assertJsonPath('data.worker.total_workers', 1)
            ->assertJsonPath('data.worker.active_vs_inactive.data.0', 1)
            ->assertJsonPath('data.worker.active_vs_inactive.data.1', 0);
    }

    public function test_dashboard_analytics_admin_sees_all_incidents_with_incident_module_filter(): void
    {
        $adminUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $adminRole = Role::query()->firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin']
        );

        $permissions = collect([
            Permission::query()->firstOrCreate(['name' => 'view_dashboard'])->id,
            Permission::query()->firstOrCreate(['name' => 'view_incident'])->id,
            Permission::query()->firstOrCreate(['name' => 'view_worker'])->id,
            Permission::query()->firstOrCreate(['name' => 'view_training'])->id,
            Permission::query()->firstOrCreate(['name' => 'view_audit'])->id,
            Permission::query()->firstOrCreate(['name' => 'edit_audit'])->id,
            Permission::query()->firstOrCreate(['name' => 'edit_training'])->id,
        ]);

        $adminRole->permissions()->syncWithoutDetaching($permissions->all());
        $adminUser->roles()->syncWithoutDetaching([$adminRole->id]);

        Incident::query()->create($this->requiredIncidentAttributes($adminUser, [
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]));

        Incident::query()->create($this->requiredIncidentAttributes($adminUser, [
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]));

        Incident::query()->create($this->requiredIncidentAttributes($otherUser, [
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]));

        Incident::query()->create($this->requiredIncidentAttributes($otherUser, [
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]));

        Incident::query()->create($this->requiredIncidentAttributes($otherUser, [
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]));

        Sanctum::actingAs($adminUser);

        $response = $this->getJson(route('api.dashboard.analytics', ['module' => 'incident']));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonMissingPath('data.training')
            ->assertJsonPath('data.incident.total_incidents', 5);
    }

    private function requiredIncidentAttributes(User $reporter, array $overrides = []): array
    {
        $locationType = LocationType::query()->firstOrCreate(
            ['code' => 'LT-DASH-TST'],
            ['name' => 'Dashboard Test Location Type', 'is_active' => true]
        );

        $location = IncidentLocation::query()->firstOrCreate(
            ['code' => 'LOC-DASH-TST'],
            ['name' => 'Dashboard Test Location', 'location_type_id' => $locationType->id, 'is_active' => true]
        );

        if ((int) $location->location_type_id !== (int) $locationType->id) {
            $location->update(['location_type_id' => $locationType->id]);
        }

        $incidentType = IncidentType::query()->firstOrCreate(
            ['code' => 'IT-DASH-TST'],
            ['name' => 'Dashboard Test Incident Type', 'is_active' => true]
        );

        $classification = IncidentClassification::query()->firstOrCreate(
            ['code' => 'IC-DASH-TST'],
            ['name' => 'Dashboard Test Classification', 'is_active' => true]
        );

        $workPackage = WorkPackage::query()->firstOrCreate(
            ['code' => 'WP-DASH-TST'],
            ['name' => 'Dashboard Test Work Package', 'is_active' => true]
        );

        $workActivity = WorkActivity::query()->firstOrCreate(
            ['code' => 'WA-DASH-TST'],
            ['name' => 'Dashboard Test Work Activity', 'is_active' => true]
        );

        $subcontractor = Subcontractor::query()->first();

        return array_merge([
            'reported_by' => $reporter->id,
            'incident_reference_number' => 'INC-DASH-'.Str::upper(Str::random(8)),
            'title' => 'Dashboard Incident',
            'description' => 'Dashboard incident description.',
            'incident_description' => 'Dashboard incident description.',
            'incident_type_id' => $incidentType->id,
            'location_type_id' => $locationType->id,
            'location_id' => $location->id,
            'location' => $location->name,
            'other_location' => $location->name,
            'datetime' => now(),
            'incident_date' => now()->toDateString(),
            'incident_time' => now()->format('H:i:s'),
            'classification' => 'Major',
            'classification_id' => $classification->id,
            'status' => 'draft',
            'work_package_id' => $workPackage->id,
            'work_activity_id' => $workActivity->id,
            'immediate_response' => 'Immediate response executed.',
            'subcontractor_id' => $subcontractor?->id,
            'person_in_charge' => 'Dashboard Test PIC',
            'subcontractor_contact_number' => '+60123456789',
        ], $overrides);
    }
}
