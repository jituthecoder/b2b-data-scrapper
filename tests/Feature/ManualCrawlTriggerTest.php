<?php

namespace Tests\Feature;

use App\Domain\Crawling\Models\CrawlJob;
use App\Domain\Domains\Models\Domain;
use Database\Seeders\DomainPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualCrawlTriggerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DomainPlatformSeeder::class);
    }

    public function test_admin_can_trigger_manual_crawl(): void
    {
        $domain = Domain::first();
        $this->assertNotNull($domain);

        $response = $this->post("/admin/domains/{$domain->id}/crawl");

        $response->assertRedirect();

        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'crawl_status' => 'pending',
        ]);

        $this->assertDatabaseHas('crawl_jobs', [
            'domain_id' => $domain->id,
            'job_type' => 'reachability',
            'priority' => 1000,
            'status' => 'pending',
        ]);
    }
}
