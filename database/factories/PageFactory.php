<?php

namespace Database\Factories;

use App\Domain\Domains\Models\Domain;
use App\Domain\Pages\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $path = $this->faker->slug();
        $url = "https://example.com/{$path}";

        return [
            'domain_id' => Domain::factory(),
            'url' => $url,
            'normalized_url' => strtolower($url),
            'page_type' => $this->faker->randomElement(['homepage', 'contact', 'about', 'careers', 'blog']),
            'http_status' => 200,
            'title' => $this->faker->sentence(4),
            'html_snapshot_s3_path' => "snapshots/pages/{$path}.html",
            'content_metadata' => ['word_count' => $this->faker->numberBetween(100, 2000)],
            'crawled_at' => now(),
        ];
    }
}
