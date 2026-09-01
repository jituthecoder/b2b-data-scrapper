<?php

namespace Database\Factories;

use App\Domain\DataProcessing\DomainNormalizationService;
use App\Domain\Domains\Models\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;

class DomainFactory extends Factory
{
    protected $model = Domain::class;

    public function definition(): array
    {
        $rawDomain = $this->faker->unique()->domainName();
        $normalizer = new DomainNormalizationService();
        $normData = $normalizer->normalize($rawDomain);

        return [
            'domain' => $normData['domain'],
            'normalized_domain' => $normData['normalized_domain'],
            'scheme' => 'https',
            'www_variant' => false,
            'tld' => $normData['tld'],
            'status' => 'active',
            'is_accessible' => true,
            'http_status' => 200,
            'first_discovered_at' => now(),
            'last_crawled_at' => now(),
            'next_crawl_at' => now()->addDays(30),
            'crawl_status' => 'completed',
            'crawl_attempts' => 1,
            'priority' => 0,
        ];
    }
}
