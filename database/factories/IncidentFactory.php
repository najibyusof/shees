<?php

namespace Database\Factories;

use App\Models\CauseType;
use App\Models\Incident;
use App\Models\IncidentClassification;
use App\Models\IncidentLocation;
use App\Models\IncidentStatus;
use App\Models\IncidentType;
use App\Models\WorkActivity;
use App\Models\Subcontractor;
use App\Models\User;
use App\Models\WorkPackage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
{
    protected $model = Incident::class;

    public function definition(): array
    {
        $occurredAt = $this->faker->dateTimeBetween('-90 days', 'now');
        $incidentType = IncidentType::query()->inRandomOrder()->first();
        $status = IncidentStatus::query()->inRandomOrder()->first();
        $classification = IncidentClassification::query()->inRandomOrder()->first();
        $location = IncidentLocation::query()->inRandomOrder()->first();
        $workPackage = WorkPackage::query()->inRandomOrder()->first();
        $workActivity = WorkActivity::query()->inRandomOrder()->first();
        $subcontractor = Subcontractor::query()->inRandomOrder()->first();
        $rootCause = CauseType::query()->inRandomOrder()->first();
        $description = $this->faker->paragraph(3);

        // Ensure work_package_id has a fallback default (use existing or 1)
        $workPackageId = $workPackage?->id ?? 1;

        return [
            'reported_by' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'submitted_by' => null,
            'reviewed_by' => null,
            'approved_by' => null,
            'rejected_by' => null,
            'incident_reference_number' => 'INC-FAC-'.strtoupper(Str::random(8)),
            'title' => $this->faker->sentence(5),
            'description' => $description,
            'incident_description' => $description,
            'incident_type_id' => $incidentType?->id,
            'location' => $location?->name ?? $this->faker->randomElement(['Warehouse A', 'Plant 1', 'Boiler Room', 'Loading Dock', 'Chemical Storage']),
            'location_type_id' => $location?->location_type_id ?? 1,
            'location_id' => $location?->id,
            'other_location' => $location?->name,
            'datetime' => $occurredAt,
            'incident_date' => $occurredAt->format('Y-m-d'),
            'incident_time' => $occurredAt->format('H:i:s'),
            'classification' => $classification?->name ?? $this->faker->randomElement(Incident::CLASSIFICATIONS),
            'classification_id' => $classification?->id,
            'status' => $status?->code ?? $this->faker->randomElement(Incident::STATUSES),
            'status_id' => $status?->id,
            'work_package_id' => $workPackageId,
            'work_activity_id' => $workActivity?->id ?? WorkActivity::query()->inRandomOrder()->value('id'),
            'immediate_response' => $this->faker->sentence(12),
            'subcontractor_id' => $subcontractor?->id,
            'person_in_charge' => $this->faker->name(),
            'subcontractor_contact_number' => $subcontractor?->contact_number ?? $this->faker->numerify('+60#########'),
            'gps_location' => $this->faker->latitude().', '.$this->faker->longitude(),
            'activity_during_incident' => $this->faker->sentence(8),
            'type_of_accident' => $this->faker->randomElement(['Slip', 'Fall', 'Near Miss', 'Equipment Contact']),
            'basic_effect' => $this->faker->sentence(6),
            'conclusion' => $this->faker->sentence(8),
            'close_remark' => null,
            'rootcause_id' => $rootCause?->id,
            'temporary_id' => (string) Str::uuid(),
            'local_created_at' => now()->subDays(random_int(1, 90)),
            'submitted_at' => null,
            'reviewed_at' => null,
            'approved_at' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ];
    }
}
