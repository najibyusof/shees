<?php

namespace Tests\Feature;

use App\Jobs\GenerateReportExportJob;
use App\Models\ReportExport;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportingIterationTwoTest extends TestCase
{
    use RefreshDatabase;

    private function manager(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $manager = User::factory()->create();
        $managerRole = Role::query()->where('name', 'Manager')->firstOrFail();
        $manager->roles()->attach($managerRole->id);

        return $manager;
    }

    public function test_user_can_save_and_view_report_preset(): void
    {
        $manager = $this->manager();

        $response = $this->actingAs($manager)->post(route('reports.presets.store'), [
            'name' => 'My Incident Preset',
            'module' => 'incidents',
            'date_from' => now()->subDays(7)->toDateString(),
            'date_to' => now()->toDateString(),
            'status' => 'closed',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('report_presets', [
            'user_id' => $manager->id,
            'name' => 'My Incident Preset',
            'module' => 'incidents',
        ]);

        $index = $this->actingAs($manager)->get(route('reports.index'));
        $index->assertOk();
        $index->assertSee('My Incident Preset');
    }

    public function test_async_export_queues_background_job_and_creates_export_record(): void
    {
        Queue::fake();
        $manager = $this->manager();

        $response = $this->actingAs($manager)->get(route('reports.export', [
            'format' => 'csv',
            'module' => 'incidents',
            'async' => 1,
        ]));

        $response->assertRedirect();

        $this->assertDatabaseHas('report_exports', [
            'user_id' => $manager->id,
            'module' => 'incidents',
            'format' => 'csv',
            'status' => 'queued',
        ]);

        Queue::assertPushed(GenerateReportExportJob::class);
    }

    public function test_report_page_shows_summary_sections(): void
    {
        $manager = $this->manager();

        $response = $this->actingAs($manager)->get(route('reports.index', [
            'module' => 'audits',
        ]));

        $response->assertOk();
        $response->assertSee('Report Summary');
        $response->assertSee('Status Breakdown');
        $response->assertSee('14-Day Trend');
    }

    public function test_completed_export_can_be_downloaded_by_owner(): void
    {
        Storage::fake('local');
        $manager = $this->manager();

        $export = ReportExport::query()->create([
            'user_id' => $manager->id,
            'module' => 'incidents',
            'format' => 'csv',
            'filters' => ['module' => 'incidents'],
            'status' => 'completed',
            'file_path' => 'reports/exports/user_'.$manager->id.'/sample.csv',
            'completed_at' => now(),
        ]);

        Storage::disk('local')->put($export->file_path, "id,title\n1,Sample\n");

        $response = $this->actingAs($manager)->get(route('reports.exports.download', $export));

        $response->assertOk();
        $response->assertHeader('content-disposition');
    }
}
