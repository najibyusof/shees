<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Training;
use App\Models\User;
use App\Notifications\TrainingCertificateExpiryNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrainingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_training_with_multilingual_fields_and_assignments(): void
    {
        $actor = User::factory()->create();
        $assignee = User::factory()->create();

        $this->actingAs($actor)
            ->post(route('trainings.store'), [
                'title' => 'Fire Safety Basics',
                'description' => 'Initial fire safety orientation.',
                'title_translations' => [
                    'en' => 'Fire Safety Basics',
                    'id' => 'Dasar Keselamatan Kebakaran',
                ],
                'description_translations' => [
                    'en' => 'Initial fire safety orientation.',
                    'id' => 'Orientasi awal keselamatan kebakaran.',
                ],
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addDays(2)->toDateString(),
                'certificate_validity_days' => 365,
                'is_active' => 1,
                'assigned_user_ids' => [$assignee->id],
            ])
            ->assertRedirect();

        $training = Training::query()->firstOrFail();

        $this->assertSame('Fire Safety Basics', $training->title);
        $this->assertSame('Dasar Keselamatan Kebakaran', $training->title_translations['id'] ?? null);

        $this->assertDatabaseHas('training_user', [
            'training_id' => $training->id,
            'user_id' => $assignee->id,
            'completion_status' => 'assigned',
        ]);
    }

    public function test_authenticated_user_can_assign_users_and_mark_completion(): void
    {
        $actor = User::factory()->create();
        $assigned = User::factory()->create();
        $training = Training::query()->create([
            'title' => 'Machine Operation',
            'certificate_validity_days' => 180,
            'is_active' => true,
        ]);

        $this->actingAs($actor)
            ->post(route('trainings.assign-users', $training), [
                'user_ids' => [$assigned->id],
            ])
            ->assertRedirect(route('trainings.show', $training));

        $this->assertDatabaseHas('training_user', [
            'training_id' => $training->id,
            'user_id' => $assigned->id,
            'completion_status' => 'assigned',
        ]);

        $this->actingAs($actor)
            ->post(route('trainings.mark-completion', [$training, $assigned]), [
                'completion_status' => 'completed',
            ])
            ->assertRedirect(route('trainings.show', $training));

        $pivot = $training->users()->where('user_id', $assigned->id)->firstOrFail()->pivot;

        $this->assertSame('completed', $pivot->completion_status);
        $this->assertNotNull($pivot->completed_at);
    }

    public function test_authenticated_user_can_upload_certificate(): void
    {
        Storage::fake('public');

        $actor = User::factory()->create();
        $assigned = User::factory()->create();

        $training = Training::query()->create([
            'title' => 'Electrical Safety',
            'certificate_validity_days' => 365,
            'is_active' => true,
        ]);

        $training->users()->attach($assigned->id, [
            'assigned_by' => $actor->id,
            'assigned_at' => now(),
            'completion_status' => 'completed',
            'completed_at' => now(),
        ]);

        $file = UploadedFile::$this->faker->create('certificate.pdf', 256, 'application/pdf');

        $this->actingAs($actor)
            ->post(route('trainings.upload-certificate', $training), [
                'user_id' => $assigned->id,
                'certificate' => $file,
                'issued_at' => now()->subDay()->toDateString(),
                'expires_at' => now()->addDays(350)->toDateString(),
            ])
            ->assertRedirect(route('trainings.show', $training));

        $certificate = Certificate::query()->firstOrFail();

        $this->assertSame($training->id, $certificate->training_id);
        $this->assertSame($assigned->id, $certificate->user_id);
        Storage::disk('public')->assertExists($certificate->file_path);
    }

    public function test_expiry_command_notifies_only_certificates_within_window_and_not_notified_yet(): void
    {
        Notification::fake();

        $userInWindow = User::factory()->create();
        $userOutOfWindow = User::factory()->create();

        $training = Training::query()->create([
            'title' => 'Hazmat Awareness',
            'certificate_validity_days' => 365,
            'is_active' => true,
        ]);

        $inWindow = Certificate::query()->create([
            'training_id' => $training->id,
            'user_id' => $userInWindow->id,
            'file_path' => 'certificates/in-window.pdf',
            'original_name' => 'in-window.pdf',
            'size' => 1200,
            'issued_at' => now()->subMonths(11)->toDateString(),
            'expires_at' => now()->addDays(10)->toDateString(),
        ]);

        Certificate::query()->create([
            'training_id' => $training->id,
            'user_id' => $userOutOfWindow->id,
            'file_path' => 'certificates/out-window.pdf',
            'original_name' => 'out-window.pdf',
            'size' => 1200,
            'issued_at' => now()->subMonths(11)->toDateString(),
            'expires_at' => now()->addDays(90)->toDateString(),
        ]);

        $alreadyNotified = Certificate::query()->create([
            'training_id' => $training->id,
            'user_id' => $userInWindow->id,
            'file_path' => 'certificates/already-notified.pdf',
            'original_name' => 'already-notified.pdf',
            'size' => 1200,
            'issued_at' => now()->subMonths(11)->toDateString(),
            'expires_at' => now()->addDays(5)->toDateString(),
            'expiry_notified_at' => now()->subDay(),
        ]);

        Artisan::call('trainings:notify-expiring-certificates', ['--days' => 30]);

        Notification::assertSentTo($userInWindow, TrainingCertificateExpiryNotification::class);
        Notification::assertNotSentTo($userOutOfWindow, TrainingCertificateExpiryNotification::class);

        $this->assertNotNull($inWindow->fresh()->expiry_notified_at);
        $this->assertNotNull($alreadyNotified->fresh()->expiry_notified_at);
    }

    public function test_guest_cannot_access_training_routes(): void
    {
        $training = Training::query()->create([
            'title' => 'Guest Access Check',
            'certificate_validity_days' => 365,
            'is_active' => true,
        ]);

        $this->get(route('trainings.index'))->assertRedirect(route('login'));
        $this->get(route('trainings.show', $training))->assertRedirect(route('login'));
    }
}
