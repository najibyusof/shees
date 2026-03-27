<?php

namespace Database\Factories;

use App\Models\NcrReport;
use App\Models\SiteAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NcrReport>
 */
class NcrReportFactory extends Factory
{
    protected $model = NcrReport::class;

    public function definition(): array
    {
        return [
            'site_audit_id' => SiteAudit::query()->inRandomOrder()->value('id') ?? SiteAuditFactory::new(),
            'reported_by' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'owner_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'verified_by' => null,
            'reference_no' => 'NCR-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(2),
            'severity' => fake()->randomElement(['low', 'medium', 'high']),
            'status' => fake()->randomElement(['open', 'in_progress', 'pending_verification', 'closed']),
            'root_cause' => fake()->sentence(8),
            'containment_action' => fake()->sentence(8),
            'corrective_action_plan' => fake()->sentence(10),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+45 days')->format('Y-m-d'),
            'verified_at' => null,
            'closed_at' => null,
        ];
    }
}
