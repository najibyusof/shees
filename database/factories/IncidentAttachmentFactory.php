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
        $extension = fake()->randomElement(['pdf', 'jpg', 'png']);
        $name = Str::slug(fake()->words(3, true));

        return [
            'incident_id' => Incident::query()->inRandomOrder()->value('id') ?? IncidentFactory::new(),
            'original_name' => $name.'.'.$extension,
            'path' => 'incidents/'.$name.'-'.fake()->uuid().'.'.$extension,
            'mime_type' => $extension === 'pdf' ? 'application/pdf' : 'image/'.$extension,
            'size' => fake()->numberBetween(10_000, 4_000_000),
        ];
    }
}
