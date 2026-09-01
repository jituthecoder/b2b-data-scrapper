<?php

namespace Database\Factories;

use App\Domain\Companies\Models\Company;
use App\Domain\DataProcessing\CompanyNormalizationService;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $name = $this->faker->company();
        $normalizer = new CompanyNormalizationService();
        $normData = $normalizer->normalize($name);

        return [
            'name' => $normData['name'],
            'normalized_name' => $normData['normalized_name'],
            'description' => $this->faker->catchPhrase(),
            'industry' => $this->faker->randomElement(['Software', 'Healthcare', 'Finance', 'E-commerce', 'Marketing']),
            'employee_count_range' => $this->faker->randomElement(['1-10', '11-50', '51-200', '201-500', '500+']),
            'founded_year' => $this->faker->numberBetween(1990, 2024),
            'country' => $this->faker->country(),
            'state_region' => $this->faker->state(),
            'city' => $this->faker->city(),
            'address' => $this->faker->address(),
            'postal_code' => $this->faker->postcode(),
            'confidence_score' => 1.00,
        ];
    }
}
