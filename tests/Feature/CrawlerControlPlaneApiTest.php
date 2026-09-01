<?php

namespace Tests\Feature;

use App\Domain\Crawling\Models\CrawlerNode;
use App\Domain\Crawling\Models\CrawlJob;
use App\Domain\Domains\Models\Domain;
use App\Domain\Emails\Models\Email;
use App\Domain\Technologies\Models\Technology;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

use Tests\TestCase;

class CrawlerControlPlaneApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_crawler_registration(): void
    {
        $response = $this->postJson('/api/v1/crawler/register', [
            'hostname' => 'crawler-worker-01.local',
            'version' => '1.2.0',
            'worker_count' => 20,
            'capabilities' => ['homepage', 'tech_detect', 'contact_discover'],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'crawler_id', 'api_key', 'worker_count', 'capabilities']);

        $this->assertDatabaseHas('crawler_nodes', [
            'hostname' => 'crawler-worker-01.local',
            'version' => '1.2.0',
        ]);
    }

    public function test_crawler_heartbeat_authentication(): void
    {
        $reg = $this->postJson('/api/v1/crawler/register', [
            'hostname' => 'worker-02',
            'version' => '1.0',
            'capabilities' => ['homepage'],
        ]);

        $crawlerId = $reg->json('crawler_id');
        $apiKey = $reg->json('api_key');

        // Unauthenticated request should fail
        $this->postJson('/api/v1/crawler/heartbeat')->assertStatus(401);

        // Authenticated request should succeed
        $response = $this->postJson('/api/v1/crawler/heartbeat', [], [
            'X-Crawler-ID' => $crawlerId,
            'X-Crawler-Key' => $apiKey,
        ]);

        $response->assertStatus(200)->assertJson(['status' => 'ok', 'crawler_id' => $crawlerId]);
    }

    public function test_crawler_job_claiming_and_leasing(): void
    {
        $reg = $this->postJson('/api/v1/crawler/register', [
            'hostname' => 'worker-03',
            'version' => '1.0',
            'capabilities' => ['homepage', 'tech_detect'],
        ]);
        $crawlerId = $reg->json('crawler_id');
        $apiKey = $reg->json('api_key');

        $domain = Domain::factory()->create();
        $job = CrawlJob::factory()->create([
            'domain_id' => $domain->id,
            'job_type' => 'homepage',
            'status' => 'pending',
        ]);

        $claimRes = $this->postJson('/api/v1/crawler/jobs/claim', ['limit' => 5], [
            'X-Crawler-ID' => $crawlerId,
            'X-Crawler-Key' => $apiKey,
        ]);

        $claimRes->assertStatus(200)
            ->assertJsonPath('claimed_count', 1)
            ->assertJsonPath('jobs.0.job_id', $job->id);

        $this->assertDatabaseHas('crawl_jobs', [
            'id' => $job->id,
            'status' => 'claimed',
            'crawler_id' => $crawlerId,
        ]);
    }

    public function test_crawler_result_submission_and_ingestion(): void
    {
        $reg = $this->postJson('/api/v1/crawler/register', [
            'hostname' => 'worker-04',
            'version' => '1.0',
            'capabilities' => ['homepage'],
        ]);
        $crawlerId = $reg->json('crawler_id');
        $apiKey = $reg->json('api_key');

        $domain = Domain::factory()->create(['domain' => 'acmecorp.com', 'normalized_domain' => 'acmecorp.com']);
        $job = CrawlJob::factory()->create([
            'domain_id' => $domain->id,
            'job_type' => 'homepage',
            'status' => 'claimed',
            'crawler_id' => $crawlerId,
        ]);

        $payload = [
            'domain_status' => [
                'is_accessible' => true,
                'http_status' => 200,
                'final_url' => 'https://acmecorp.com',
            ],
            'company' => [
                'name' => 'Acme Corporation',
                'industry' => 'Technology',
            ],
            'emails' => [
                'contact@acmecorp.com',
                'sales@acmecorp.com',
            ],
            'technologies' => [
                ['name' => 'WordPress', 'version' => '6.4'],
                ['name' => 'React', 'version' => '18.2'],
            ],
            'pages' => [
                ['url' => 'https://acmecorp.com', 'title' => 'Acme Home', 'page_type' => 'homepage'],
            ],
        ];

        $res = $this->postJson("/api/v1/crawler/jobs/{$job->id}/result", $payload, [
            'X-Crawler-ID' => $crawlerId,
            'X-Crawler-Key' => $apiKey,
        ]);

        $res->assertStatus(202)->assertJson(['status' => 'processing', 'job_id' => $job->id]);

        // Verify Data Ingestion into PostgreSQL/Database
        $this->assertDatabaseHas('emails', ['email' => 'contact@acmecorp.com']);
        $this->assertDatabaseHas('emails', ['email' => 'sales@acmecorp.com']);
        $this->assertDatabaseHas('technologies', ['slug' => 'wordpress']);
        $this->assertDatabaseHas('technologies', ['slug' => 'react']);
        $this->assertDatabaseHas('companies', ['name' => 'Acme Corporation']);
    }

    public function test_idempotency_duplicate_submission(): void
    {
        $reg = $this->postJson('/api/v1/crawler/register', [
            'hostname' => 'worker-05',
            'version' => '1.0',
            'capabilities' => ['homepage'],
        ]);
        $crawlerId = $reg->json('crawler_id');
        $apiKey = $reg->json('api_key');

        $domain = Domain::factory()->create();
        $job = CrawlJob::factory()->create([
            'domain_id' => $domain->id,
            'job_type' => 'homepage',
            'status' => 'completed',
            'crawler_id' => $crawlerId,
        ]);

        $res = $this->postJson("/api/v1/crawler/jobs/{$job->id}/result", ['test' => 123], [
            'X-Crawler-ID' => $crawlerId,
            'X-Crawler-Key' => $apiKey,
        ]);

        $res->assertStatus(200)->assertJson(['status' => 'completed']);
    }

    public function test_crawler_job_failure_handling(): void
    {
        $reg = $this->postJson('/api/v1/crawler/register', [
            'hostname' => 'worker-06',
            'version' => '1.0',
            'capabilities' => ['homepage'],
        ]);
        $crawlerId = $reg->json('crawler_id');
        $apiKey = $reg->json('api_key');

        $domain = Domain::factory()->create();
        $job = CrawlJob::factory()->create([
            'domain_id' => $domain->id,
            'job_type' => 'homepage',
            'status' => 'claimed',
            'crawler_id' => $crawlerId,
            'attempt_count' => 0,
            'max_attempts' => 2,
        ]);

        // Attempt 1 Failure -> Should reset to pending
        $this->postJson("/api/v1/crawler/jobs/{$job->id}/failed", ['error' => 'Connection timeout'], [
            'X-Crawler-ID' => $crawlerId,
            'X-Crawler-Key' => $apiKey,
        ])->assertStatus(200)->assertJson(['status' => 'pending', 'attempt_count' => 1]);

        // Attempt 2 Failure -> Max attempts reached -> Should mark failed
        $job->update(['status' => 'claimed', 'crawler_id' => $crawlerId]);
        $this->postJson("/api/v1/crawler/jobs/{$job->id}/failed", ['error' => 'Connection timeout second time'], [
            'X-Crawler-ID' => $crawlerId,
            'X-Crawler-Key' => $apiKey,
        ])->assertStatus(200)->assertJson(['status' => 'failed', 'attempt_count' => 2]);
    }

    public function test_release_expired_leases_command(): void
    {
        $domain = Domain::factory()->create();
        $expiredJob = CrawlJob::factory()->create([
            'domain_id' => $domain->id,
            'job_type' => 'homepage',
            'status' => 'claimed',
            'crawler_id' => 'node-crashed',
            'lease_expires_at' => now()->subMinutes(15),
        ]);

        $this->artisan('crawler:release-expired-leases')
            ->expectsOutput('Released 1 expired crawl job leases back to pending.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('crawl_jobs', [
            'id' => $expiredJob->id,
            'status' => 'pending',
            'crawler_id' => null,
        ]);
    }
}
