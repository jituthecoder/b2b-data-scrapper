<?php

namespace Tests\Feature;

use App\Domain\Crawling\Models\CrawlJob;
use App\Domain\DataProcessing\Jobs\ProcessCrawlResultJob;
use App\Domain\Domains\Models\Domain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelectiveCrawlTriggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_trigger_selective_reachability_only_crawl(): void
    {
        $domain = Domain::create([
            'domain' => 'stripe.com',
            'normalized_domain' => 'stripe.com',
            'tld' => 'com',
            'status' => 'active',
            'crawl_status' => 'pending',
        ]);

        $response = $this->post("/admin/domains/{$domain->id}/crawl", [
            'stages' => ['reachability'],
            'auto_advance' => false,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('crawl_jobs', [
            'domain_id' => $domain->id,
            'job_type' => 'reachability',
            'priority' => 1000,
        ]);

        $job = CrawlJob::where('domain_id', $domain->id)->where('job_type', 'reachability')->first();

        // Simulate worker submitting result for reachability job with auto_advance = false
        ProcessCrawlResultJob::dispatchSync($job->id, [
            'domain_status' => ['is_accessible' => true, 'http_status' => 200]
        ]);

        $domain->refresh();

        $this->assertEquals('completed', $domain->crawl_status);
        $this->assertEquals(1, CrawlJob::where('domain_id', $domain->id)->count());
    }

    public function test_admin_can_trigger_targeted_homepage_crawl(): void
    {
        $domain = Domain::create([
            'domain' => 'stripe.com',
            'normalized_domain' => 'stripe.com',
            'tld' => 'com',
            'status' => 'active',
            'crawl_status' => 'pending',
        ]);

        $response = $this->post("/admin/domains/{$domain->id}/crawl", [
            'stages' => ['homepage'],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('crawl_jobs', [
            'domain_id' => $domain->id,
            'job_type' => 'homepage',
            'priority' => 1000,
        ]);
    }
}
