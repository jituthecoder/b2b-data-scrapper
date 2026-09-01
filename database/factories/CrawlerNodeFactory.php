<?php

namespace Database\Factories;

use App\Domain\Crawling\Models\CrawlerNode;
use Illuminate\Database\Eloquent\Factories\Factory;

class CrawlerNodeFactory extends Factory
{
    protected $model = CrawlerNode::class;

    public function definition(): array
    {
        $crawlerId = 'node-' . $this->faker->unique()->numberBetween(100, 999);

        return [
            'crawler_id' => $crawlerId,
            'api_key_hash' => hash('sha256', 'secret-key-' . $crawlerId),
            'hostname' => $this->faker->domainName(),
            'version' => '1.0.0',
            'worker_count' => 20,
            'status' => 'active',
            'capabilities' => ['reachability', 'homepage', 'tech_detect', 'contact_discover', 'careers', 'social', 'seo'],
            'last_heartbeat_at' => now(),
        ];
    }
}
