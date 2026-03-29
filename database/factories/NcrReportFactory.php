<?php

namespace Database\Factories;

use App\Models\NcrReport;
use App\Models\SiteAudit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NcrReport>
 */
class NcrReportFactory extends Factory
{
    protected $model = NcrReport::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['open', 'in_progress', 'pending_verification', 'closed']);
        $dueDate = $this->faker->dateTimeBetween('-10 days', '+45 days');
        $verifiedAt = $status === 'closed' ? Carbon::instance($dueDate)->addDays(random_int(1, 5)) : null;

        return [
            'site_audit_id' => SiteAudit::query()->inRandomOrder()->value('id') ?? SiteAuditFactory::new(),
            'reported_by' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'owner_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'verified_by' => $status === 'closed' ? (User::query()->inRandomOrder()->value('id') ?? User::factory()) : null,
            'reference_no' => 'NCR-'.now()->format('Ymd').'-'.$this->faker->unique()->numerify('####'),
            'title' => $this->faker->sentence(5),
            'description' => $this->faker->paragraph(2),
            'severity' => $this->faker->randomElement(['low', 'medium', 'high']),
            'status' => $status,
            'root_cause' => $this->faker->sentence(8),
            'containment_action' => $this->faker->sentence(8),
            'corrective_action_plan' => $this->faker->sentence(10),
            'due_date' => Carbon::instance($dueDate)->format('Y-m-d'),
            'verified_at' => $verifiedAt,
            'closed_at' => $status === 'closed' ? ($verifiedAt ?? now()) : null,
        ];
    }

    public function highSeverityOpen(): static
    {
        return $this->state(fn () => [
            'severity' => 'high',
            'status' => 'open',
            'due_date' => now()->addDays(10)->toDateString(),
            'verified_by' => null,
            'verified_at' => null,
            'closed_at' => null,
        ]);
    }

    public function overdueOpen(): static
    {
        return $this->state(fn () => [
            'status' => 'open',
            'due_date' => now()->subDays(random_int(2, 14))->toDateString(),
            'verified_by' => null,
            'verified_at' => null,
            'closed_at' => null,
        ]);
    }

    public function pendingVerification(): static
    {
        return $this->state(fn () => [
            'status' => 'pending_verification',
            'due_date' => now()->addDays(random_int(1, 7))->toDateString(),
            'verified_by' => null,
            'verified_at' => null,
            'closed_at' => null,
        ]);
    }

    public function closed(): static
    {
        return $this->state(function () {
            $verifiedBy = User::query()->inRandomOrder()->value('id') ?? User::factory();
            $verifiedAt = now()->subDays(random_int(1, 10));

            return [
                'status' => 'closed',
                'due_date' => $verifiedAt->copy()->subDays(random_int(3, 15))->toDateString(),
                'verified_by' => $verifiedBy,
                'verified_at' => $verifiedAt,
                'closed_at' => $verifiedAt->copy()->addHours(random_int(1, 6)),
            ];
        });
    }
}
