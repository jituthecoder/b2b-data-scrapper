<?php

namespace Database\Factories;

use App\Domain\DataProcessing\PhoneNormalizationService;
use App\Domain\Phones\Models\Phone;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhoneFactory extends Factory
{
    protected $model = Phone::class;

    public function definition(): array
    {
        $raw = $this->faker->e164PhoneNumber();
        $normalizer = new PhoneNormalizationService();
        $norm = $normalizer->normalize($raw);

        return [
            'phone_number' => $norm['phone_number'],
            'normalized_phone' => $norm['normalized_phone'],
            'country_code' => 'US',
            'type' => 'work',
            'confidence_score' => 1.00,
        ];
    }
}
