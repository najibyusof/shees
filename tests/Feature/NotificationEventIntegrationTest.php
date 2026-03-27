<?php

namespace Tests\Feature;

use App\Events\ApprovalRequired;
use App\Events\IncidentSubmitted;
use App\Events\TrainingExpiryDetected;
use App\Models\Certificate;
use App\Models\Incident;
use App\Models\Role;
use App\Models\Training;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class NotificationEventIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_incident_submission_dispatches_incident_submitted_event(): void
    {
        Event::fake([IncidentSubmitted::class]);
        $this->seed(RolesAndPermissionsSeeder::class);

        $reporter = User::factory()->create();
        $manager = User::factory()->create();
        $safetyOfficer = User::factory()->create();

        $workerRole = Role::query()->where('name', 'Worker')->firstOrFail();
        $managerRole = Role::query()->where('name', 'Manager')->firstOrFail();
        $safetyRole = Role::query()->where('name', 'Safety Officer')->firstOrFail();

        $reporter->roles()->attach($workerRole->id);
        $manager->roles()->attach($managerRole->id);
        $safetyOfficer->roles()->attach($safetyRole->id);

        $incident = Incident::query()->create([
            'reported_by' => $reporter->id,
            'title' => 'Event dispatch check',
            'description' => 'Verify submit dispatches event.',
            'location' => 'Test Site',
            'datetime' => now(),
            'classification' => 'Major',
            'status' => 'draft',
        ]);

        $this->actingAs($reporter)
            ->post(route('incidents.submit', $incident))
            ->assertRedirect(route('incidents.show', $incident));

        Event::assertDispatched(IncidentSubmitted::class, function (IncidentSubmitted $event) use ($incident): bool {
            return $event->incident->is($incident) && $event->approvers->count() >= 2;
        });
    }

    public function test_partial_incident_approval_dispatches_approval_required_event(): void
    {
        Event::fake([ApprovalRequired::class]);
        $this->seed(RolesAndPermissionsSeeder::class);

        $reporter = User::factory()->create();
        $manager = User::factory()->create();
        $safetyOfficer = User::factory()->create();

        $workerRole = Role::query()->where('name', 'Worker')->firstOrFail();
        $managerRole = Role::query()->where('name', 'Manager')->firstOrFail();
        $safetyRole = Role::query()->where('name', 'Safety Officer')->firstOrFail();

        $reporter->roles()->attach($workerRole->id);
        $manager->roles()->attach($managerRole->id);
        $safetyOfficer->roles()->attach($safetyRole->id);

        $incident = Incident::query()->create([
            'reported_by' => $reporter->id,
            'title' => 'Approval required event check',
            'description' => 'Verify first approval triggers approval-required event.',
            'location' => 'Unit 8',
            'datetime' => now(),
            'classification' => 'Critical',
            'status' => 'draft',
        ]);

        $this->actingAs($reporter)
            ->post(route('incidents.submit', $incident))
            ->assertRedirect(route('incidents.show', $incident));

        $this->actingAs($manager)
            ->post(route('incidents.approve', $incident), ['remarks' => 'Manager approved.'])
            ->assertRedirect(route('incidents.show', $incident));

        Event::assertDispatched(ApprovalRequired::class, function (ApprovalRequired $event) use ($incident, $reporter): bool {
            return $event->incident->is($incident) && $event->recipient->is($reporter);
        });
    }

    public function test_expiring_certificate_scan_dispatches_training_expiry_event(): void
    {
        Event::fake([TrainingExpiryDetected::class]);

        $user = User::factory()->create();
        $training = Training::query()->create([
            'title' => 'Forklift Safety',
            'certificate_validity_days' => 365,
            'is_active' => true,
        ]);

        $certificate = Certificate::query()->create([
            'training_id' => $training->id,
            'user_id' => $user->id,
            'file_path' => 'certificates/forklift.pdf',
            'original_name' => 'forklift.pdf',
            'size' => 1200,
            'issued_at' => now()->subMonths(11)->toDateString(),
            'expires_at' => now()->addDays(7)->toDateString(),
        ]);

        Artisan::call('trainings:notify-expiring-certificates', ['--days' => 30]);

        Event::assertDispatched(TrainingExpiryDetected::class, function (TrainingExpiryDetected $event) use ($certificate): bool {
            return $event->certificate->is($certificate);
        });
    }
}
