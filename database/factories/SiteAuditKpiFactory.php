<?php

namespace Database\Factories;

use App\Models\SiteAudit;
use App\Models\SiteAuditKpi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteAuditKpi>
 */
class SiteAuditKpiFactory extends Factory
{
    protected $model = SiteAuditKpi::class;

    public function definition(): array
    {
        $target = fake()->randomFloat(2, 80, 100);
        $actual = fake()->randomFloat(2, 50, 100);

        return [
            'site_audit_id' => SiteAudit::query()->inRandomOrder()->value('id') ?? SiteAuditFactory::new(),
            'name' => fake()->randomElement(['PPE Compliance', 'Housekeeping', 'Permit Adherence', 'Emergency Readiness']),
            'target_value' => $target,
            'actual_value' => $actual,
            'unit' => '%',
            'weight' => fake()->numberBetween(1, 5),
            'status' => $actual >= $target ? 'met' : 'pending',
            'notes' => fake()->optional()->sentence(8),
        ];
    }
}
