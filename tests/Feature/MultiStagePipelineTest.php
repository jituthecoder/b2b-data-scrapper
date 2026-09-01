<?php

namespace Tests\Feature;

use App\Domain\Crawling\Models\CrawlJob;
use App\Domain\DataProcessing\Jobs\ProcessCrawlResultJob;
use App\Domain\Domains\Models\Domain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiStagePipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_registration_queues_stage1_reachability_job(): void
    {
        $response = $this->postJson('/api/v1/domains', [
            'domain' => 'https://www.shopify.com',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('domain.domain', 'www.shopify.com')
            ->assertJsonPath('domain.normalized_domain', 'shopify.com')
            ->assertJsonPath('stage1_job.job_type', 'reachability')
            ->assertJsonPath('stage1_job.priority', 100);

        $this->assertDatabaseHas('domains', ['normalized_domain' => 'shopify.com']);
        $this->assertDatabaseHas('crawl_jobs', [
            'job_type' => 'reachability',
            'priority' => 100,
        ]);
    }

    public function test_stage1_accessible_advances_to_stage2(): void
    {
        $domain = Domain::create([
            'domain' => 'stripe.com',
            'normalized_domain' => 'stripe.com',
            'tld' => 'com',
            'status' => 'active',
            'crawl_status' => 'pending',
        ]);

        $stage1Job = CrawlJob::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'domain_id' => $domain->id,
            'job_type' => 'reachability',
            'priority' => 100,
            'status' => 'claimed',
        ]);

        // Dispatch ProcessCrawlResultJob with Stage 1 accessible result
        ProcessCrawlResultJob::dispatchSync($stage1Job->id, [
            'domain_status' => [
                'is_accessible' => true,
                'http_status' => 200,
            ]
        ]);

        // Verify Domain status updated and Stage 2 job created
        $domain->refresh();
        $this->assertTrue($domain->is_accessible);
        $this->assertEquals('in_progress', $domain->crawl_status);

        $this->assertDatabaseHas('crawl_jobs', [
            'domain_id' => $domain->id,
            'job_type' => 'homepage',
            'priority' => 100,
            'status' => 'pending',
        ]);
    }

    public function test_stage1_unaccessible_halts_pipeline(): void
    {
        $domain = Domain::create([
            'domain' => 'dead-website-xyz.com',
            'normalized_domain' => 'dead-website-xyz.com',
            'tld' => 'com',
            'status' => 'active',
            'crawl_status' => 'pending',
        ]);

        $stage1Job = CrawlJob::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'domain_id' => $domain->id,
            'job_type' => 'reachability',
            'priority' => 100,
            'status' => 'claimed',
        ]);

        // Dispatch ProcessCrawlResultJob with Stage 1 failed result
        ProcessCrawlResultJob::dispatchSync($stage1Job->id, [
            'domain_status' => [
                'is_accessible' => false,
                'http_status' => 500,
            ]
        ]);

        $domain->refresh();
        $this->assertFalse($domain->is_accessible);
        $this->assertEquals('failed', $domain->crawl_status);

        // Assert NO stage 2 homepage job was created
        $this->assertDatabaseMissing('crawl_jobs', [
            'domain_id' => $domain->id,
            'job_type' => 'homepage',
        ]);
    }
}
