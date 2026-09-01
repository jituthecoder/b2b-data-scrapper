<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDomainImportWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_custom_domains_from_dashboard(): void
    {
        $response = $this->post('/admin/domains', [
            'domains_input' => "stripe.com\nw3speedup.com\nhttps://shopify.com",
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('domains', ['normalized_domain' => 'stripe.com']);
        $this->assertDatabaseHas('domains', ['normalized_domain' => 'w3speedup.com']);
        $this->assertDatabaseHas('domains', ['normalized_domain' => 'shopify.com']);

        $this->assertDatabaseHas('crawl_jobs', [
            'job_type' => 'reachability',
            'priority' => 1000,
            'status' => 'pending',
        ]);
    }
}
