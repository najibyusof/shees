<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Str;

class SeedDataGenerator
{
    public function randomElement(array $items): mixed
    {
        if ($items === []) {
            return null;
        }

        return $items[array_rand($items)];
    }

    public function slug(int $words = 3): string
    {
        return Str::slug($this->sentence(max(1, $words), false));
    }

    public function numerify(string $pattern): string
    {
        return preg_replace_callback('/#/', fn () => (string) random_int(0, 9), $pattern) ?? $pattern;
    }

    public function sentence(int $words = 6, bool $endWithPeriod = true): string
    {
        $pool = [
            'safety', 'inspection', 'incident', 'training', 'worker', 'compliance', 'control', 'monitor',
            'audit', 'risk', 'action', 'verification', 'report', 'tracking', 'quality', 'hazard',
        ];

        $count = max(1, $words);
        $parts = [];
        for ($i = 0; $i < $count; $i++) {
            $parts[] = $this->randomElement($pool);
        }

        $sentence = ucfirst(implode(' ', $parts));

        return $endWithPeriod ? $sentence.'.' : $sentence;
    }

    public function paragraph(int $sentences = 3): string
    {
        $count = max(1, $sentences);
        $parts = [];

        for ($i = 0; $i < $count; $i++) {
            $parts[] = $this->sentence(random_int(8, 14));
        }

        return implode(' ', $parts);
    }

    public function name(): string
    {
        $first = ['Alex', 'Jordan', 'Taylor', 'Chris', 'Sam', 'Morgan', 'Jamie', 'Riley'];
        $last = ['Cruz', 'Reyes', 'Santos', 'Lim', 'Garcia', 'Tan', 'Mendoza', 'Bautista'];

        return $this->randomElement($first).' '.$this->randomElement($last);
    }

    public function phoneNumber(): string
    {
        return '+63 9'.$this->numerify('#########');
    }

    public function latitude(float $min = -90, float $max = 90): float
    {
        return $this->randomFloat(7, $min, $max);
    }

    public function longitude(float $min = -180, float $max = 180): float
    {
        return $this->randomFloat(7, $min, $max);
    }

    public function randomFloat(int $maxDecimals = 2, float $min = 0, float $max = 1): float
    {
        $decimals = max(0, $maxDecimals);
        $value = $min + (mt_rand() / mt_getrandmax()) * ($max - $min);

        return round($value, $decimals);
    }

    public function uuid(): string
    {
        return (string) Str::uuid();
    }

    public function ipv4(): string
    {
        return random_int(1, 223).'.'.random_int(0, 255).'.'.random_int(0, 255).'.'.random_int(1, 254);
    }

    public function optional(float $weight = 0.5): OptionalSeedDataGenerator
    {
        return new OptionalSeedDataGenerator($this, $weight);
    }
}
    {
        return random_int(1, 223).'.'.random_int(0, 255).'.'.random_int(0, 255).'.'.random_int(1, 254);
    }

    public function jobTitle(): string
    {
        $titles = [
            'Safety Officer', 'Site Supervisor', 'Project Engineer', 'Field Technician',
            'Quality Inspector', 'Foreman', 'Construction Worker', 'Electrical Engineer',
            'Civil Engineer', 'Health & Safety Manager', 'Site Manager', 'Operator',
        ];

        return $this->randomElement($titles);
    }

    /**
     * @return string|array<int,string>
     */
    public function words(int $count = 3, bool $asText = false): string|array
    {
        $pool = [
            'safety', 'inspection', 'incident', 'training', 'worker', 'compliance',
            'control', 'monitor', 'audit', 'risk', 'action', 'verification', 'report',
            'tracking', 'quality', 'hazard', 'procedure', 'equipment', 'permit', 'zone',
        ];

        $result = [];
        for ($i = 0; $i < max(1, $count); $i++) {
            $result[] = $this->randomElement($pool);
        }

        return $asText ? implode(' ', $result) : $result;
    }
    public function __construct(
        private readonly SeedDataGenerator $generator,
        private readonly float $weight = 0.5,
    ) {
    }

    public function __call(string $method, array $arguments): mixed
    {
        $chance = max(0.0, min(1.0, $this->weight));

        if ((mt_rand() / mt_getrandmax()) > $chance) {
            return null;
        }

        return $this->generator->{$method}(...$arguments);
    }
}
