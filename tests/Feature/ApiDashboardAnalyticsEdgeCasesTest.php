<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\IncidentClassification;
use App\Models\IncidentLocation;
use App\Models\IncidentType;
use App\Models\LocationType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Subcontractor;
use App\Models\User;
use App\Models\WorkActivity;
use App\Models\WorkPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiDashboardAnalyticsEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_analytics_requires_sanctum_authentication(): void
    {
        $this->getJson(route('api.dashboard.analytics'))
            ->assertStatus(401);
    }

    public function test_dashboard_analytics_requires_view_dashboard_permission(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson(route('api.dashboard.analytics'))
            ->assertStatus(403);
    }

    public function test_dashboard_analytics_returns_empty_dataset_shape_when_no_records_exist(): void
    {
        $user = User::factory()->create();
        $this->attachAnalyticsPermissions($user, 'admin');

        Sanctum::actingAs($user);

        $response = $this->getJson(route('api.dashboard.analytics', [
            'module' => 'all',
            'from' => now()->subDays(30)->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.incident.total_incidents', 0)
            ->assertJsonPath('data.training.total_trainings', 0)
            ->assertJsonPath('data.inspection.total_inspections', 0)
            ->assertJsonPath('data.audit.total_audits', 0)
            ->assertJsonPath('data.worker.total_workers', 0)
            ->assertJsonPath('data.worker.active_vs_inactive.data.0', 0)
            ->assertJsonPath('data.worker.active_vs_inactive.data.1', 0);
    }

    public function test_dashboard_analytics_applies_date_range_filter_to_incident_status_aggregation(): void
    {
        $user = User::factory()->create();
        $this->attachAnalyticsPermissions($user, 'admin');

        $recentIncident = Incident::query()->create($this->requiredIncidentAttributes($user, [
            'status' => 'open',
        ]));

        $recentIncident->forceFill([
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ])->saveQuietly();

        $oldIncident = Incident::query()->create($this->requiredIncidentAttributes($user, [
            'status' => 'closed',
        ]));

        $oldIncident->forceFill([
            'created_at' => now()->subDays(90),
            'updated_at' => now()->subDays(90),
        ])->saveQuietly();

        Sanctum::actingAs($user);

        $response = $this->getJson(route('api.dashboard.analytics', [
            'module' => 'incident',
            'from' => now()->subDays(7)->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonMissingPath('data.training');

        $labels = collect($response->json('data.incident.by_status.labels'));
        $data = collect($response->json('data.incident.by_status.data'));

        $statusMap = $labels
            ->zip($data)
            ->mapWithKeys(fn (Collection $pair): array => [(string) $pair->get(0) => (int) $pair->get(1)]);

        $this->assertSame(1, (int) $statusMap->get('open', 0));
        $this->assertSame(0, (int) $statusMap->get('closed', 0));
    }

    public function test_dashboard_analytics_rejects_invalid_module_filter(): void
    {
        $user = User::factory()->create();
        $this->attachAnalyticsPermissions($user, 'admin');

        Sanctum::actingAs($user);

        $this->getJson(route('api.dashboard.analytics', [
            'module' => 'finance',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['module']);
    }

    public function test_dashboard_analytics_rejects_invalid_date_format_filters(): void
    {
        $user = User::factory()->create();
        $this->attachAnalyticsPermissions($user, 'admin');

        Sanctum::actingAs($user);

        $this->getJson(route('api.dashboard.analytics', [
            'from' => 'not-a-date',
            'to' => 'still-not-a-date',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['from', 'to']);
    }

    public function test_dashboard_analytics_rejects_when_to_date_is_before_from_date(): void
    {
        $user = User::factory()->create();
        $this->attachAnalyticsPermissions($user, 'admin');

        Sanctum::actingAs($user);

        $this->getJson(route('api.dashboard.analytics', [
            'from' => now()->toDateString(),
            'to' => now()->subDays(1)->toDateString(),
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['to']);
    }

    private function attachAnalyticsPermissions(User $user, string $roleSlug): void
    {
        $role = Role::query()->firstOrCreate(
            ['slug' => $roleSlug],
            ['name' => Str::of($roleSlug)->replace('-', ' ')->title()->toString()]
        );

        $permissionIds = collect([
            Permission::query()->firstOrCreate(['name' => 'view_dashboard'])->id,
            Permission::query()->firstOrCreate(['name' => 'view_incident'])->id,
            Permission::query()->firstOrCreate(['name' => 'view_training'])->id,
            Permission::query()->firstOrCreate(['name' => 'view_audit'])->id,
            Permission::query()->firstOrCreate(['name' => 'view_worker'])->id,
            Permission::query()->firstOrCreate(['name' => 'edit_audit'])->id,
            Permission::query()->firstOrCreate(['name' => 'edit_training'])->id,
        ]);

        $role->permissions()->syncWithoutDetaching($permissionIds->all());
        $user->roles()->syncWithoutDetaching([$role->id]);
    }

    private function requiredIncidentAttributes(User $reporter, array $overrides = []): array
    {
        $locationType = LocationType::query()->firstOrCreate(
            ['code' => 'LT-DASH-EDGE'],
            ['name' => 'Dashboard Edge Location Type', 'is_active' => true]
        );

        $location = IncidentLocation::query()->firstOrCreate(
            ['code' => 'LOC-DASH-EDGE'],
            ['name' => 'Dashboard Edge Location', 'location_type_id' => $locationType->id, 'is_active' => true]
        );

        if ((int) $location->location_type_id !== (int) $locationType->id) {
            $location->update(['location_type_id' => $locationType->id]);
        }

        $incidentType = IncidentType::query()->firstOrCreate(
            ['code' => 'IT-DASH-EDGE'],
            ['name' => 'Dashboard Edge Incident Type', 'is_active' => true]
        );

        $classification = IncidentClassification::query()->firstOrCreate(
            ['code' => 'IC-DASH-EDGE'],
            ['name' => 'Dashboard Edge Classification', 'is_active' => true]
        );

        $workPackage = WorkPackage::query()->firstOrCreate(
            ['code' => 'WP-DASH-EDGE'],
            ['name' => 'Dashboard Edge Work Package', 'is_active' => true]
        );

        $workActivity = WorkActivity::query()->firstOrCreate(
            ['code' => 'WA-DASH-EDGE'],
            ['name' => 'Dashboard Edge Work Activity', 'is_active' => true]
        );

        $subcontractor = Subcontractor::query()->first();

        return array_merge([
            'reported_by' => $reporter->id,
            'incident_reference_number' => 'INC-EDGE-'.Str::upper(Str::random(8)),
            'title' => 'Dashboard Edge Incident',
            'description' => 'Dashboard edge incident description.',
            'incident_description' => 'Dashboard edge incident description.',
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
            'person_in_charge' => 'Dashboard Edge PIC',
            'subcontractor_contact_number' => '+60123456789',
        ], $overrides);
    }
}
