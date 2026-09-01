<?php

namespace Database\Factories;

use App\Domain\Crawling\Models\CrawlJob;
use App\Domain\Domains\Models\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CrawlJobFactory extends Factory
{
    protected $model = CrawlJob::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'domain_id' => Domain::factory(),
            'job_type' => $this->faker->randomElement(['reachability', 'homepage', 'tech_detect', 'contact_discover']),
            'priority' => $this->faker->numberBetween(0, 10),
            'status' => 'pending',
            'crawler_id' => null,
            'attempt_count' => 0,
            'max_attempts' => 3,
            'idempotency_key' => (string) Str::uuid(),
        ];
    }
}
