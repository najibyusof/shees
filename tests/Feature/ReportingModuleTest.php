<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\IncidentClassification;
use App\Models\IncidentLocation;
use App\Models\IncidentType;
use App\Models\LocationType;
use App\Models\Role;
use App\Models\SiteAudit;
use App\Models\Subcontractor;
use App\Models\Training;
use App\Models\User;
use App\Models\WorkActivity;
use App\Models\WorkPackage;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportingModuleTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsManager(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $manager = User::factory()->create();
        $managerRole = Role::query()->where('name', 'Manager')->firstOrFail();
        $manager->roles()->attach($managerRole->id);

        $this->actingAs($manager);

        return $manager;
    }

    public function test_reports_page_filters_incidents_by_date_user_and_status(): void
    {
        $this->actingAsManager();

        $targetUser = User::factory()->create(['name' => 'Target Reporter']);
        $otherUser = User::factory()->create(['name' => 'Other Reporter']);

        Incident::query()->create($this->requiredIncidentAttributes($targetUser, [
            'reported_by' => $targetUser->id,
            'title' => 'Target Incident',
            'description' => 'Target match',
            'location' => 'Zone A',
            'datetime' => now()->subDay(),
            'classification' => 'Major',
            'status' => 'closed',
        ]));

        Incident::query()->create($this->requiredIncidentAttributes($otherUser, [
            'reported_by' => $otherUser->id,
            'title' => 'Noise Incident',
            'description' => 'Should be filtered out',
            'location' => 'Zone B',
            'datetime' => now()->subDays(15),
            'classification' => 'Minor',
            'status' => 'draft',
        ]));

        $response = $this->get(route('reports.index', [
            'module' => 'incidents',
            'date_from' => now()->subDays(7)->toDateString(),
            'date_to' => now()->toDateString(),
            'user_id' => $targetUser->id,
            'status' => 'closed',
        ]));

        $response->assertOk();
        $response->assertSee('Target Incident');
        $response->assertDontSee('Noise Incident');
    }

    public function test_reports_csv_export_for_trainings_returns_download_with_content(): void
    {
        $this->actingAsManager();

        $training = Training::query()->create([
            'title' => 'CSV Training',
            'starts_at' => now()->toDateString(),
            'is_active' => true,
        ]);

        $response = $this->get(route('reports.export', [
            'format' => 'csv',
            'module' => 'trainings',
            'status' => 'active',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader('content-disposition');

        $content = $response->streamedContent();
        $this->assertStringContainsString('CSV Training', $content);
        $this->assertStringContainsString('ID,Title,Status,"Start Date","End Date",Users,Certificates', $content);

        $this->assertDatabaseHas('trainings', [
            'id' => $training->id,
            'title' => 'CSV Training',
        ]);
    }

    public function test_reports_pdf_export_for_audits_returns_pdf_response(): void
    {
        $manager = $this->actingAsManager();

        SiteAudit::query()->create([
            'created_by' => $manager->id,
            'reference_no' => 'AUD-REP-001',
            'site_name' => 'North Plant',
            'audit_type' => 'internal',
            'scheduled_for' => now()->toDateString(),
            'status' => 'scheduled',
        ]);

        $response = $this->get(route('reports.export', [
            'format' => 'pdf',
            'module' => 'audits',
            'status' => 'scheduled',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition');

        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    public function test_reports_module_switch_shows_training_and_audit_rows(): void
    {
        $manager = $this->actingAsManager();

        Training::query()->create([
            'title' => 'Module Switch Training',
            'starts_at' => now()->toDateString(),
            'is_active' => true,
        ]);

        SiteAudit::query()->create([
            'created_by' => $manager->id,
            'reference_no' => 'AUD-REP-002',
            'site_name' => 'South Plant',
            'audit_type' => 'external',
            'scheduled_for' => now()->toDateString(),
            'status' => 'approved',
        ]);

        $trainingResponse = $this->get(route('reports.index', ['module' => 'trainings']));
        $trainingResponse->assertOk();
        $trainingResponse->assertSee('Module Switch Training');

        $auditResponse = $this->get(route('reports.index', ['module' => 'audits']));
        $auditResponse->assertOk();
        $auditResponse->assertSee('AUD-REP-002');
    }

    private function requiredIncidentAttributes(User $reporter, array $overrides = []): array
    {
        $locationType = LocationType::query()->firstOrCreate(
            ['code' => 'LT-TST'],
            ['name' => 'Test Location Type', 'is_active' => true]
        );

        $location = IncidentLocation::query()->firstOrCreate(
            ['code' => 'LOC-TST'],
            ['name' => 'Test Location', 'location_type_id' => $locationType->id, 'is_active' => true]
        );

        if ((int) $location->location_type_id !== (int) $locationType->id) {
            $location->update(['location_type_id' => $locationType->id]);
        }

        $incidentType = IncidentType::query()->firstOrCreate(
            ['code' => 'IT-TST'],
            ['name' => 'Test Incident Type', 'is_active' => true]
        );

        $classification = IncidentClassification::query()->firstOrCreate(
            ['code' => 'IC-TST'],
            ['name' => 'Test Classification', 'is_active' => true]
        );

        $workPackage = WorkPackage::query()->firstOrCreate(
            ['code' => 'WP-TST'],
            ['name' => 'Test Work Package', 'is_active' => true]
        );

        $workActivity = WorkActivity::query()->firstOrCreate(
            ['code' => 'WA-TST'],
            ['name' => 'Test Work Activity', 'is_active' => true]
        );

        $subcontractor = Subcontractor::query()->first();

        return array_merge([
            'reported_by' => $reporter->id,
            'incident_reference_number' => 'INC-TST-'.Str::upper(Str::random(8)),
            'title' => 'Test Incident',
            'description' => 'Test incident description.',
            'incident_description' => 'Test incident description.',
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
            'person_in_charge' => 'Test PIC',
            'subcontractor_contact_number' => '+60123456789',
        ], $overrides);
    }
}
