<?php

namespace Database\Factories;

use App\Domain\Sources\Models\DataSource;
use Illuminate\Database\Eloquent\Factories\Factory;

class DataSourceFactory extends Factory
{
    protected $model = DataSource::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word() . '_source',
            'type' => $this->faker->randomElement(['crawler', 'api', 'file_import', 'manual']),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
