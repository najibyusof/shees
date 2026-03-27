<?php

namespace Database\Factories;

use App\Models\SiteAudit;
use App\Models\SiteAuditApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteAuditApproval>
 */
class SiteAuditApprovalFactory extends Factory
{
    protected $model = SiteAuditApproval::class;

    public function definition(): array
    {
        $decision = fake()->randomElement(['approved', 'rejected']);

        return [
            'site_audit_id' => SiteAudit::query()->inRandomOrder()->value('id') ?? SiteAuditFactory::new(),
            'approver_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'approver_role' => fake()->randomElement(['Manager', 'Safety Officer']),
            'decision' => $decision,
            'remarks' => fake()->sentence(8),
            'decided_at' => fake()->dateTimeBetween('-45 days', 'now'),
        ];
    }
}
