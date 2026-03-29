<?php

namespace Database\Factories;

use App\Models\SiteAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteAudit>
 */
class SiteAuditFactory extends Factory
{
    protected $model = SiteAudit::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(SiteAudit::STATUSES);
        $scheduledFor = $this->faker->dateTimeBetween('-20 days', '+20 days');
        $submittedAt = in_array($status, ['submitted', 'under_review', 'approved', 'rejected', 'closed'], true)
            ? (clone $scheduledFor)->modify('+1 hour')
            : null;
        $reviewedAt = in_array($status, ['under_review', 'approved', 'rejected', 'closed'], true)
            ? (clone $scheduledFor)->modify('+6 hours')
            : null;
        $approvedAt = in_array($status, ['approved', 'closed'], true)
            ? (clone $scheduledFor)->modify('+10 hours')
            : null;
        $rejectedAt = $status === 'rejected'
            ? (clone $scheduledFor)->modify('+8 hours')
            : null;

        return [
            'created_by' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'submitted_by' => null,
            'reviewed_by' => null,
            'approved_by' => null,
            'rejected_by' => null,
            'reference_no' => 'AUD-'.now()->format('Ymd').'-'.$this->faker->unique()->numerify('####'),
            'site_name' => $this->faker->randomElement(['North Plant', 'South Yard', 'Main Office', 'Utility Building']),
            'area' => $this->faker->randomElement(['Production', 'Storage', 'Utilities', 'Loading Bay']),
            'audit_type' => $this->faker->randomElement(['internal', 'external']),
            'scheduled_for' => $scheduledFor->format('Y-m-d'),
            'conducted_at' => $this->faker->optional()->dateTimeBetween('-20 days', 'now'),
            'status' => $status,
            'kpi_score' => $this->faker->optional()->randomFloat(2, 65, 100),
            'scope' => $this->faker->sentence(10),
            'summary' => $this->faker->paragraph(2),
            'rejection_reason' => $status === 'rejected' ? $this->faker->sentence(10) : null,
            'submitted_at' => $submittedAt,
            'reviewed_at' => $reviewedAt,
            'approved_at' => $approvedAt,
            'rejected_at' => $rejectedAt,
        ];
    }
}
