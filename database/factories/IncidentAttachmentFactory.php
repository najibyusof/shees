<?php

namespace Database\Factories;

use App\Models\AttachmentCategory;
use App\Models\AttachmentType;
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
        $name = Str::slug($this->faker->words(3, true)).'-'.$this->faker->numerify('####');
        $filename = $name.'.'.$extension;
        $attachmentType = AttachmentType::query()->inRandomOrder()->first();
        $attachmentCategory = AttachmentCategory::query()->inRandomOrder()->first();

        return [
            'incident_id' => Incident::query()->inRandomOrder()->value('id') ?? IncidentFactory::new(),
            'attachment_type_id' => $attachmentType?->id,
            'attachment_category_id' => $attachmentCategory?->id,
            'original_name' => $filename,
            'filename' => $filename,
            'path' => 'incidents/'.$name.'-'.$this->faker->uuid().'.'.$extension,
            'description' => $this->faker->sentence(6),
            'mime_type' => $extension === 'pdf' ? 'application/pdf' : 'image/'.$extension,
            'size' => $this->faker->numberBetween(10_000, 4_000_000),
            'temporary_id' => (string) Str::uuid(),
            'local_created_at' => now()->subDays(random_int(0, 60)),
        ];
    }
}
