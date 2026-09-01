<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSystemControlWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_system_info_page(): void
    {
        $response = $this->get('/admin/system');
        $response->assertStatus(200);
        $response->assertSee('Global System Crawl Engine');
        $response->assertSee('Database Status');
    }
}
