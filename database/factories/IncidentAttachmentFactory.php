<?php

namespace Database\Factories;

use App\Models\Incident;
use App\Models\IncidentAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<IncidentAttachment>
 */
class IncidentAttachmentFactory extends Factory
{
    protected $model = IncidentAttachment::class;

    public function definition(): array
    {
        $extension = $this->faker->randomElement(['pdf', 'jpg', 'png']);
        $name = Str::slug($this->faker->words(3, true));

        return [
            'incident_id' => Incident::query()->inRandomOrder()->value('id') ?? IncidentFactory::new(),
            'original_name' => $name.'.'.$extension,
            'path' => 'incidents/'.$name.'-'.$this->faker->uuid().'.'.$extension,
            'mime_type' => $extension === 'pdf' ? 'application/pdf' : 'image/'.$extension,
            'size' => $this->faker->numberBetween(10_000, 4_000_000),
        ];
    }
}
