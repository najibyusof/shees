<?php

namespace Database\Factories;

use App\Models\CorrectiveAction;
use App\Models\NcrReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<CorrectiveAction>
 */
class CorrectiveActionFactory extends Factory
{
    protected $model = CorrectiveAction::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['open', 'in_progress', 'completed', 'verified']);
        $dueDate = Carbon::instance($this->faker->dateTimeBetween('-15 days', '+60 days'));
        $completedAt = in_array($status, ['completed', 'verified'], true)
            ? $dueDate->copy()->subDays(random_int(0, 5))->addHours(random_int(1, 8))
            : null;
        $verifiedAt = $status === 'verified' && $completedAt
            ? $completedAt->copy()->addHours(random_int(2, 24))
            : null;

        return [
            'ncr_report_id' => NcrReport::query()->inRandomOrder()->value('id') ?? NcrReportFactory::new(),
            'assigned_to' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'verified_by' => $status === 'verified' ? (User::query()->inRandomOrder()->value('id') ?? User::factory()) : null,
            'title' => $this->faker->sentence(5),
            'description' => $this->faker->paragraph(2),
            'status' => $status,
            'due_date' => $dueDate->toDateString(),
            'completed_at' => $completedAt,
            'verified_at' => $verifiedAt,
            'completion_notes' => $this->faker->optional()->sentence(10),
        ];
    }

    public function open(): static
    {
        return $this->state(fn () => [
            'status' => 'open',
            'due_date' => now()->addDays(random_int(7, 21))->toDateString(),
            'completed_at' => null,
            'verified_by' => null,
            'verified_at' => null,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => 'in_progress',
            'due_date' => now()->addDays(random_int(3, 14))->toDateString(),
            'completed_at' => null,
            'verified_by' => null,
            'verified_at' => null,
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'status' => $this->faker->randomElement(['open', 'in_progress']),
            'due_date' => now()->subDays(random_int(1, 12))->toDateString(),
            'completed_at' => null,
            'verified_by' => null,
            'verified_at' => null,
            'completion_notes' => 'Overdue corrective action pending completion.',
        ]);
    }

    public function completed(): static
    {
        return $this->state(function () {
            $completedAt = now()->subDays(random_int(1, 10));

            return [
                'status' => 'completed',
                'due_date' => $completedAt->copy()->addDays(random_int(1, 8))->toDateString(),
                'completed_at' => $completedAt,
                'verified_by' => null,
                'verified_at' => null,
                'completion_notes' => 'Completed and awaiting verification.',
            ];
        });
    }

    public function verified(): static
    {
        return $this->state(function () {
            $completedAt = now()->subDays(random_int(2, 12));
            $verifiedAt = $completedAt->copy()->addHours(random_int(4, 48));

            return [
                'status' => 'verified',
                'due_date' => $completedAt->copy()->addDays(random_int(1, 8))->toDateString(),
                'completed_at' => $completedAt,
                'verified_by' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
                'verified_at' => $verifiedAt,
                'completion_notes' => 'Verified by auditor and closed out.',
            ];
        });
    }
}
