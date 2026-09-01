<?php

namespace Tests\Feature;

use Database\Seeders\DomainPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWebDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DomainPlatformSeeder::class);
    }

    public function test_admin_dashboard_web_overview_page(): void
    {
        $response = $this->get('/admin');

        $response->assertStatus(200)
            ->assertSee('System Dashboard Overview')
            ->assertSee('Total Target Domains')
            ->assertSee('Enriched Companies');
    }

    public function test_admin_domains_web_page(): void
    {
        $response = $this->get('/admin/domains');

        $response->assertStatus(200)
            ->assertSee('Domain Explorer')
            ->assertSee('Registered Domains');
    }

    public function test_admin_crawlers_web_page(): void
    {
        $response = $this->get('/admin/crawlers');

        $response->assertStatus(200)
            ->assertSee('Crawler Worker Nodes')
            ->assertSee('Registered Worker Nodes');
    }

    public function test_admin_jobs_web_page(): void
    {
        $response = $this->get('/admin/jobs');

        $response->assertStatus(200)
            ->assertSee('Crawl Job Queue')
            ->assertSee('Target Domain');
    }
}
