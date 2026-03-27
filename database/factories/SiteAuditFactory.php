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
        return [
            'created_by' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'submitted_by' => null,
            'reviewed_by' => null,
            'approved_by' => null,
            'rejected_by' => null,
            'reference_no' => 'AUD-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'site_name' => fake()->randomElement(['North Plant', 'South Yard', 'Main Office', 'Utility Building']),
            'area' => fake()->randomElement(['Production', 'Storage', 'Utilities', 'Loading Bay']),
            'audit_type' => fake()->randomElement(['internal', 'external']),
            'scheduled_for' => fake()->dateTimeBetween('-20 days', '+20 days')->format('Y-m-d'),
            'conducted_at' => fake()->optional()->dateTimeBetween('-20 days', 'now'),
            'status' => fake()->randomElement(SiteAudit::STATUSES),
            'kpi_score' => fake()->optional()->randomFloat(2, 65, 100),
            'scope' => fake()->sentence(10),
            'summary' => fake()->paragraph(2),
            'rejection_reason' => null,
            'submitted_at' => null,
            'reviewed_at' => null,
            'approved_at' => null,
            'rejected_at' => null,
        ];
    }
}
