<?php

namespace Tests\Feature;

use App\Domain\Crawling\Models\CrawlerNode;
use App\Domain\Crawling\Services\SystemCrawlControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSystemControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_pause_and_resume_global_system_crawling(): void
    {
        $controlService = app(SystemCrawlControlService::class);
        $this->assertTrue($controlService->isActive());

        // Admin pauses system
        $response = $this->post('/admin/system/crawl-control', ['action' => 'pause']);
        $response->assertRedirect();
        $this->assertTrue($controlService->isPaused());

        // Worker node tries claiming jobs while system is paused
        $node = CrawlerNode::create([
            'crawler_id' => 'node-test-1',
            'api_key_hash' => hash('sha256', 'test-api-key'),
            'hostname' => 'test-host',
            'ip_address' => '127.0.0.1',
            'version' => '1.0.0',
            'capabilities' => ['reachability', 'homepage', 'subpage'],
            'concurrency' => 20,
            'status' => 'active',
            'last_heartbeat_at' => now(),
        ]);

        $claimResponse = $this->withHeaders([
            'X-Crawler-Id' => 'node-test-1',
            'X-Crawler-Key' => 'test-api-key',
        ])->postJson('/api/v1/crawler/jobs/claim');

        $claimResponse->assertStatus(200);
        $claimResponse->assertJson(['status' => 'paused', 'claimed_count' => 0]);

        // Admin resumes system
        $resumeResponse = $this->post('/admin/system/crawl-control', ['action' => 'start']);
        $resumeResponse->assertRedirect();
        $this->assertTrue($controlService->isActive());
    }
}
