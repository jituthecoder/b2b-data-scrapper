<?php

namespace Database\Factories;

use App\Domain\DataProcessing\EmailNormalizationService;
use App\Domain\Domains\Models\Domain;
use App\Domain\Emails\Models\Email;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailFactory extends Factory
{
    protected $model = Email::class;

    public function definition(): array
    {
        $rawEmail = $this->faker->unique()->safeEmail();
        $normalizer = new EmailNormalizationService();
        $normData = $normalizer->normalize($rawEmail);

        return [
            'email' => $normData['email'],
            'normalized_email' => $normData['normalized_email'],
            'domain_id' => Domain::factory(),
            'type' => $normData['type'],
            'verification_status' => 'unverified',
            'confidence_score' => 1.00,
            'first_discovered_at' => now(),
            'last_checked_at' => now(),
        ];
    }
}
