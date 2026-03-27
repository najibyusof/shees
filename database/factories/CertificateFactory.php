<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\Training;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Certificate>
 */
class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    public function definition(): array
    {
        $issuedAt = fake()->dateTimeBetween('-15 months', '-2 months');

        return [
            'training_id' => Training::query()->inRandomOrder()->value('id') ?? TrainingFactory::new(),
            'user_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'uploaded_by' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'file_path' => 'certificates/'.Str::slug(fake()->words(3, true)).'-'.fake()->uuid().'.pdf',
            'original_name' => Str::slug(fake()->words(2, true)).'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(80_000, 900_000),
            'issued_at' => $issuedAt,
            'expires_at' => (clone $issuedAt)->modify('+365 days'),
            'expiry_notified_at' => null,
            'metadata' => [
                'seeded' => true,
            ],
        ];
    }
}
