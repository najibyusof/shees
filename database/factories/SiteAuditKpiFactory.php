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
        $target = $this->faker->randomFloat(2, 80, 100);
        $actual = $this->faker->randomFloat(2, 50, 100);

        return [
            'site_audit_id' => SiteAudit::query()->inRandomOrder()->value('id') ?? SiteAuditFactory::new(),
            'name' => $this->faker->randomElement(['PPE Compliance', 'Housekeeping', 'Permit Adherence', 'Emergency Readiness']),
            'target_value' => $target,
            'actual_value' => $actual,
            'unit' => '%',
            'weight' => $this->faker->numberBetween(1, 5),
            'status' => $actual >= $target ? 'met' : 'pending',
            'notes' => $this->faker->optional()->sentence(8),
        ];
    }
}
