<?php

namespace Tests\Feature;

use App\Domain\Domains\Models\Domain;
use Database\Seeders\DomainPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDomainDetailWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DomainPlatformSeeder::class);
    }

    public function test_admin_can_view_domain_detail_and_timeline(): void
    {
        $domain = Domain::first();
        $this->assertNotNull($domain);

        $response = $this->get("/admin/domains/{$domain->id}");

        $response->assertStatus(200)
            ->assertSee($domain->domain)
            ->assertSee('Stage Task Execution History')
            ->assertSee('Extracted Company Profile')
            ->assertSee('Detected Technology Stack')
            ->assertSee('Extracted Contacts', false);
    }
}
