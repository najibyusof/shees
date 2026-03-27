<?php

namespace Tests\Feature;

use App\Console\Commands\CleanupReportExportsCommand;
use App\Console\Commands\RunScheduledReportExportsCommand;
use App\Jobs\GenerateReportExportJob;
use App\Models\ReportExport;
use App\Models\ReportPreset;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportingIterationThreeTest extends TestCase
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

    public function test_user_can_store_scheduled_preset_fields(): void
    {
        $manager = $this->manager();

        $response = $this->actingAs($manager)->post(route('reports.presets.store'), [
            'name' => 'Weekly PDF Preset',
            'module' => 'audits',
            'export_format' => 'pdf',
            'schedule_enabled' => 1,
            'schedule_frequency' => 'weekly',
            'schedule_time' => '08:30',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('report_presets', [
            'user_id' => $manager->id,
            'name' => 'Weekly PDF Preset',
            'module' => 'audits',
            'export_format' => 'pdf',
            'schedule_enabled' => 1,
            'schedule_frequency' => 'weekly',
            'schedule_time' => '08:30',
        ]);
    }

    public function test_run_now_on_preset_queues_export_job(): void
    {
        Queue::fake();
        $manager = $this->manager();

        $preset = ReportPreset::query()->create([
            'user_id' => $manager->id,
            'name' => 'RunNow Preset',
            'module' => 'incidents',
            'export_format' => 'csv',
            'filters' => ['module' => 'incidents'],
            'schedule_enabled' => false,
        ]);

        $response = $this->actingAs($manager)->post(route('reports.presets.run', $preset));

        $response->assertRedirect();
        $this->assertDatabaseHas('report_exports', [
            'user_id' => $manager->id,
            'module' => 'incidents',
            'format' => 'csv',
            'status' => 'queued',
        ]);
        Queue::assertPushed(GenerateReportExportJob::class);
    }

    public function test_scheduled_exports_command_queues_due_presets(): void
    {
        Queue::fake();
        $manager = $this->manager();

        ReportPreset::query()->create([
            'user_id' => $manager->id,
            'name' => 'Due Preset',
            'module' => 'trainings',
            'export_format' => 'pdf',
            'filters' => ['module' => 'trainings'],
            'schedule_enabled' => true,
            'schedule_frequency' => 'daily',
            'schedule_time' => '07:00',
            'next_run_at' => now()->subMinutes(5),
        ]);

        $exit = Artisan::call(RunScheduledReportExportsCommand::class, ['--limit' => 10]);

        $this->assertSame(0, $exit);
        $this->assertDatabaseHas('report_exports', [
            'user_id' => $manager->id,
            'module' => 'trainings',
            'format' => 'pdf',
            'status' => 'queued',
        ]);
        Queue::assertPushed(GenerateReportExportJob::class);

        $this->assertNotNull(ReportPreset::query()->first()?->fresh()->next_run_at);
        $this->assertNotNull(ReportPreset::query()->first()?->fresh()->last_run_at);
    }

    public function test_cleanup_command_removes_old_exports_and_files(): void
    {
        Storage::fake('local');
        $manager = $this->manager();

        $oldExport = ReportExport::query()->create([
            'user_id' => $manager->id,
            'module' => 'incidents',
            'format' => 'csv',
            'filters' => ['module' => 'incidents'],
            'status' => 'completed',
            'file_path' => 'reports/exports/user_'.$manager->id.'/old.csv',
            'completed_at' => now()->subDays(40),
        ]);

        $oldExport->forceFill([
            'created_at' => now()->subDays(40),
            'updated_at' => now()->subDays(40),
        ])->save();

        Storage::disk('local')->put($oldExport->file_path, "id,title\n1,Old\n");

        $exit = Artisan::call(CleanupReportExportsCommand::class, ['--days' => 30]);

        $this->assertSame(0, $exit);
        $this->assertDatabaseMissing('report_exports', ['id' => $oldExport->id]);
        Storage::disk('local')->assertMissing('reports/exports/user_'.$manager->id.'/old.csv');
    }
}
