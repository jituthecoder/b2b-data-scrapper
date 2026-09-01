<?php

namespace Database\Factories;

use App\Domain\Technologies\Models\Technology;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TechnologyFactory extends Factory
{
    protected $model = Technology::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'category' => $this->faker->randomElement(['CMS', 'Analytics', 'E-commerce', 'Framework', 'UI']),
            'icon_url' => $this->faker->imageUrl(),
            'description' => $this->faker->sentence(),
        ];
    }
}
