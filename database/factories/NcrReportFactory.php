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
            'reference_no' => 'NCR-'.now()->format('Ymd').'-'.$this->faker->unique()->numerify('####'),
            'title' => $this->faker->sentence(5),
            'description' => $this->faker->paragraph(2),
            'severity' => $this->faker->randomElement(['low', 'medium', 'high']),
            'status' => $this->faker->randomElement(['open', 'in_progress', 'pending_verification', 'closed']),
            'root_cause' => $this->faker->sentence(8),
            'containment_action' => $this->faker->sentence(8),
            'corrective_action_plan' => $this->faker->sentence(10),
            'due_date' => $this->faker->optional()->dateTimeBetween('now', '+45 days')->format('Y-m-d'),
            'verified_at' => null,
            'closed_at' => null,
        ];
    }
}
