<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\InspectionSyncJob;
use App\Models\AttendanceLog;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardTelemetryTest extends TestCase
{
    use RefreshDatabase;

    private function createSyncJob(User $user, string $device, string $status, string $error = '', ?\Illuminate\Support\Carbon $createdAt = null): InspectionSyncJob
    {
        $job = InspectionSyncJob::query()->create([
            'user_id' => $user->id,
            'device_identifier' => $device,
            'direction' => 'upload',
            'entity_type' => 'inspection',
            'operation' => 'upsert',
            'contract_name' => 'inspection-sync',
            'contract_version' => 1,
            'payload' => [],
            'status' => $status,
            'error_message' => $error ?: null,
            'received_at' => now(),
        ]);

        if ($createdAt) {
            $job->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->save();
        }

        return $job;
    }

    public function test_dashboard_shows_top_failing_devices_panel_with_ranked_devices(): void
    {
        $user = User::factory()->create();

        $this->createSyncJob($user, 'device-alpha', 'failed', 'Timeout while syncing');
        $this->createSyncJob($user, 'device-alpha', 'conflict', 'Version mismatch');
        $this->createSyncJob($user, 'device-bravo', 'failed', 'Payload rejected');
        $this->createSyncJob($user, 'device-clean', 'acked');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('24 Hours');
        $response->assertSee('7 Days');
        $response->assertSee('30 Days');
        $response->assertSee('Top Failing Devices (7 Days)');
        $response->assertSee('device-alpha');
        $response->assertSee('device-bravo');
        $response->assertDontSee('device-clean');
    }

    public function test_dashboard_sync_window_filter_changes_top_failing_devices_scope(): void
    {
        $user = User::factory()->create();

        $this->createSyncJob($user, 'device-recent', 'failed', 'Recent failure', now()->subHours(2));
        $this->createSyncJob($user, 'device-older', 'failed', 'Older failure', now()->subDays(10));

        $response = $this->actingAs($user)->get(route('dashboard', ['sync_window' => '24h']));

        $response->assertOk();
        $response->assertSee('Top Failing Devices (24 Hours)');
        $response->assertSee('device-recent');
        $response->assertDontSee('device-older');
    }

    public function test_dashboard_includes_worker_tracking_live_counts(): void
    {
        $user = User::factory()->create();

        $activeInside = Worker::query()->create([
            'employee_code' => 'WK-2001',
            'full_name' => 'Active Inside',
            'status' => 'active',
        ]);

        $activeOutside = Worker::query()->create([
            'employee_code' => 'WK-2002',
            'full_name' => 'Active Outside',
            'status' => 'active',
        ]);

        $inactiveWorker = Worker::query()->create([
            'employee_code' => 'WK-2003',
            'full_name' => 'Inactive Worker',
            'status' => 'inactive',
        ]);

        AttendanceLog::query()->create([
            'worker_id' => $activeInside->id,
            'event_type' => 'ping',
            'logged_at' => now()->subMinutes(10),
            'latitude' => 14.5995,
            'longitude' => 120.9842,
            'source' => 'manual',
            'inside_geofence' => true,
        ]);

        AttendanceLog::query()->create([
            'worker_id' => $activeOutside->id,
            'event_type' => 'ping',
            'logged_at' => now()->subMinutes(20),
            'latitude' => 14.604,
            'longitude' => 120.99,
            'source' => 'manual',
            'inside_geofence' => false,
        ]);

        // Older than the default 30-minute live window, so it should not count as "on-site now".
        AttendanceLog::query()->create([
            'worker_id' => $inactiveWorker->id,
            'event_type' => 'ping',
            'logged_at' => now()->subHours(3),
            'latitude' => 14.61,
            'longitude' => 120.995,
            'source' => 'manual',
            'inside_geofence' => true,
        ]);

        AttendanceLog::query()->create([
            'worker_id' => $inactiveWorker->id,
            'event_type' => 'ping',
            'logged_at' => now()->subDays(2),
            'latitude' => 14.62,
            'longitude' => 121.0,
            'source' => 'manual',
            'inside_geofence' => false,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Worker Tracking');
        $response->assertSee('Active Workers');
        $response->assertSee('On-Site Now');
        $response->assertSee('Out-of-Geofence (24h)');
        $response->assertSee('Last updated');
        $response->assertViewHas('stats', function (array $stats): bool {
            $workerTracking = $stats['workerTracking'] ?? [];

            return ($workerTracking['active_workers'] ?? null) === 2
                && ($workerTracking['on_site_now'] ?? null) === 1
            && ($workerTracking['alerts_last_24h'] ?? null) === 1
            && ! empty($workerTracking['last_updated_at']);
        });
    }

    public function test_dashboard_worker_window_filter_changes_on_site_count(): void
    {
        $user = User::factory()->create();

        $workerFresh = Worker::query()->create([
            'employee_code' => 'WK-3001',
            'full_name' => 'Fresh Signal',
            'status' => 'active',
        ]);

        $workerOlder = Worker::query()->create([
            'employee_code' => 'WK-3002',
            'full_name' => 'Older Signal',
            'status' => 'active',
        ]);

        AttendanceLog::query()->create([
            'worker_id' => $workerFresh->id,
            'event_type' => 'ping',
            'logged_at' => now()->subMinutes(10),
            'latitude' => 14.61,
            'longitude' => 120.99,
            'source' => 'manual',
            'inside_geofence' => true,
        ]);

        AttendanceLog::query()->create([
            'worker_id' => $workerOlder->id,
            'event_type' => 'ping',
            'logged_at' => now()->subMinutes(45),
            'latitude' => 14.62,
            'longitude' => 121.0,
            'source' => 'manual',
            'inside_geofence' => true,
        ]);

        $defaultResponse = $this->actingAs($user)->get(route('dashboard'));
        $defaultResponse->assertOk();
        $defaultResponse->assertSee('On-Site Now (30 Min)');
        $defaultResponse->assertViewHas('stats', function (array $stats): bool {
            return ($stats['workerTracking']['on_site_now'] ?? null) === 1;
        });

        $sixtyMinuteResponse = $this->actingAs($user)->get(route('dashboard', ['worker_window' => '60m']));
        $sixtyMinuteResponse->assertOk();
        $sixtyMinuteResponse->assertSee('On-Site Now (60 Min)');
        $sixtyMinuteResponse->assertViewHas('stats', function (array $stats): bool {
            $workerTracking = $stats['workerTracking'] ?? [];
            $window = $workerTracking['window'] ?? [];

            return ($workerTracking['on_site_now'] ?? null) === 2
                && ($window['selected'] ?? null) === '60m';
        });
    }

    public function test_dashboard_shows_audit_log_health_tile_with_today_count_modules_and_cleanup_status(): void
    {
        $user = User::factory()->create();

        AuditLog::query()->create([
            'user_id' => $user->id,
            'action' => 'create',
            'module' => 'incidents',
        ]);

        AuditLog::query()->create([
            'user_id' => $user->id,
            'action' => 'update',
            'module' => 'incidents',
        ]);

        AuditLog::query()->create([
            'user_id' => $user->id,
            'action' => 'approve',
            'module' => 'audits',
        ]);

        Cache::forever('audit_logs_cleanup_status', [
            'last_run_at' => now()->subHour()->toIso8601String(),
            'deleted_count' => 12,
            'days' => 180,
            'dry_run' => false,
            'eligible_count' => 12,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Audit Log Health');
        $response->assertSee('Audit Events Today');
        $response->assertSee('Top Modules Today');
        $response->assertSee('Cleanup Last Run');
        $response->assertDontSee('Cleanup status is stale (older than 24h). Consider running the cleanup command.');
        $response->assertSee('incidents: 2');
        $response->assertSee('audits: 1');
        $response->assertViewHas('stats', function (array $stats): bool {
            $auditHealth = $stats['auditHealth'] ?? [];
            $cleanup = $auditHealth['cleanup'] ?? [];
            $topModules = collect($auditHealth['top_modules_today'] ?? [])->pluck('total', 'module');

            return ($auditHealth['today_count'] ?? null) === 3
                && (int) ($topModules['incidents'] ?? 0) === 2
                && (int) ($topModules['audits'] ?? 0) === 1
                && ($cleanup['deleted_count'] ?? null) === 12
                && ($cleanup['days'] ?? null) === 180;
        });
    }

    public function test_dashboard_shows_subtle_warning_when_cleanup_status_is_stale(): void
    {
        $user = User::factory()->create();

        Cache::forever('audit_logs_cleanup_status', [
            'last_run_at' => now()->subDays(2)->toIso8601String(),
            'deleted_count' => 0,
            'days' => 180,
            'dry_run' => false,
            'eligible_count' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Cleanup status is stale (older than 24h). Consider running the cleanup command.');
    }
}
