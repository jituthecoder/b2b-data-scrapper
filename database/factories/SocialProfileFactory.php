<?php

namespace Database\Factories;

use App\Domain\Companies\Models\Company;
use App\Domain\SocialProfiles\Models\SocialProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class SocialProfileFactory extends Factory
{
    protected $model = SocialProfile::class;

    public function definition(): array
    {
        $platform = $this->faker->randomElement(['linkedin', 'twitter', 'facebook', 'github']);
        $username = $this->faker->userName();
        $url = "https://{$platform}.com/{$username}";

        return [
            'platform' => $platform,
            'profile_url' => $url,
            'normalized_url' => strtolower($url),
            'username_handle' => $username,
            'entity_type' => Company::class,
            'entity_id' => Company::factory(),
        ];
    }
}
