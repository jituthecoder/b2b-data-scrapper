<?php

namespace Tests\Feature;

use App\Domain\Crawling\Models\CrawlJob;
use App\Domain\DataProcessing\Jobs\ProcessCrawlResultJob;
use App\Domain\Domains\Models\Domain;
use App\Domain\Emails\Models\Email;
use App\Domain\Integrations\Google\GoogleEmailHarvestingService;
use App\Domain\Integrations\Google\GoogleFacebookDiscoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeepScrapePipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_deep_scrape_triggers_facebook_discovery_and_offsite_email_harvesting_when_zero_emails_found(): void
    {
        $domain = Domain::create([
            'domain' => 'w3speedup.com',
            'normalized_domain' => 'w3speedup.com',
            'tld' => 'com',
            'status' => 'active',
            'crawl_status' => 'pending',
            'is_accessible' => true,
        ]);

        $job = CrawlJob::create([
            'domain_id' => $domain->id,
            'job_type' => 'homepage',
            'priority' => 1000,
            'status' => 'pending',
            'attempt_count' => 0,
            'max_attempts' => 3,
            'payload' => [
                'crawl_mode' => 'deep',
            ],
        ]);

        // Mock GoogleFacebookDiscoveryService and GoogleEmailHarvestingService
        $this->mock(GoogleFacebookDiscoveryService::class, function ($mock) use ($domain) {
            $mock->shouldReceive('discoverFacebookUrl')
                ->once()
                ->withAnyArgs()
                ->andReturn('https://facebook.com/w3speedup');
        });

        $this->mock(GoogleEmailHarvestingService::class, function ($mock) use ($domain) {
            $mock->shouldReceive('harvestOffSiteEmails')
                ->once()
                ->withAnyArgs()
                ->andReturn(['support@w3speedup.com']);
        });

        // Dispatch ProcessCrawlResultJob with 0 emails in payload
        ProcessCrawlResultJob::dispatchSync($job->id, [
            'domain_status' => ['is_accessible' => true, 'http_status' => 200],
            'company' => ['name' => 'W3 SpeedUp'],
            'emails' => [], // 0 emails found on homepage
        ]);

        $domain->refresh();
        $this->assertEquals('completed', $domain->crawl_status);
    }
}
