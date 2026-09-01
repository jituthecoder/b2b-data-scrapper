<?php

namespace Tests\Feature;

use App\Domain\Crawling\Models\CrawlJob;
use App\Domain\DataProcessing\Jobs\ProcessCrawlResultJob;
use App\Domain\Domains\Models\Domain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WordPressDetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_wordpress_theme_and_plugins_ingestion(): void
    {
        $domain = Domain::create([
            'domain' => 'w3speedup.com',
            'normalized_domain' => 'w3speedup.com',
            'tld' => 'com',
            'status' => 'active',
            'crawl_status' => 'in_progress',
        ]);

        $job = CrawlJob::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'domain_id' => $domain->id,
            'job_type' => 'homepage',
            'priority' => 1000,
            'status' => 'claimed',
        ]);

        ProcessCrawlResultJob::dispatchSync($job->id, [
            'domain_status' => ['is_accessible' => true, 'http_status' => 200],
            'technologies' => [
                ['name' => 'WordPress', 'category' => 'CMS'],
                ['name' => 'Understrap Framework', 'category' => 'WordPress Theme'],
                ['name' => 'Yoast SEO', 'category' => 'WordPress Plugin'],
                ['name' => 'WooCommerce', 'category' => 'WordPress Plugin'],
                ['name' => 'WPBakery Page Builder', 'category' => 'WordPress Plugin'],
                ['name' => 'GDPR Cookie Compliance', 'category' => 'WordPress Plugin'],
            ]
        ]);

        $domain->refresh();

        $this->assertDatabaseHas('technologies', [
            'name' => 'Understrap Framework',
            'category' => 'WordPress Theme',
        ]);

        $this->assertDatabaseHas('technologies', [
            'name' => 'Yoast SEO',
            'category' => 'WordPress Plugin',
        ]);

        $this->assertEquals(6, $domain->technologies->count());
        $this->assertEquals(1, $domain->technologies->where('category', 'WordPress Theme')->count());
        $this->assertEquals(4, $domain->technologies->where('category', 'WordPress Plugin')->count());
    }
}
