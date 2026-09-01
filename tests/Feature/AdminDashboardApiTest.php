<?php

namespace Tests\Feature;

use Database\Seeders\DomainPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DomainPlatformSeeder::class);
    }

    public function test_admin_dashboard_metrics(): void
    {
        $response = $this->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'domains' => ['total', 'accessible', 'inaccessible', 'pending_crawls', 'completed_crawls', 'failed_crawls'],
                'entities' => ['companies', 'contacts', 'emails', 'verified_emails', 'phones', 'technologies', 'pages'],
                'crawlers' => ['active_nodes_count', 'total_worker_capacity', 'active_nodes'],
                'jobs' => ['total_jobs', 'pending', 'claimed', 'completed', 'failed'],
            ]);
    }

    public function test_admin_domains_list(): void
    {
        $response = $this->getJson('/api/v1/admin/domains');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'current_page', 'total']);
    }

    public function test_admin_crawlers_list(): void
    {
        $response = $this->getJson('/api/v1/admin/crawlers');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'current_page', 'total']);
    }

    public function test_admin_jobs_list(): void
    {
        $response = $this->getJson('/api/v1/admin/jobs');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'current_page', 'total']);
    }
}
