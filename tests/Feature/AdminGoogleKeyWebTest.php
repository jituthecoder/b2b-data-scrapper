<?php

namespace Tests\Feature;

use App\Domain\Integrations\Google\Models\GoogleApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGoogleKeyWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_google_keys_manager_page(): void
    {
        $response = $this->get('/admin/google-keys');

        $response->assertStatus(200)
            ->assertSee('Google API Keys Pool Manager')
            ->assertSee('Total Pool Keys');
    }

    public function test_admin_can_bulk_import_keys(): void
    {
        $response = $this->post('/admin/google-keys', [
            'keys_input' => "AIzaSyKey1_test\nAIzaSyKey2_test\nAIzaSyKey3_test",
            'cx' => 'custom_cx_123',
            'daily_limit' => 95,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('google_api_keys', ['api_key' => 'AIzaSyKey1_test', 'cx' => 'custom_cx_123']);
        $this->assertDatabaseHas('google_api_keys', ['api_key' => 'AIzaSyKey2_test']);
    }

    public function test_admin_can_reset_single_key_quota(): void
    {
        $key = GoogleApiKey::create([
            'api_key' => 'key_to_reset',
            'requests_today' => 90,
            'is_exhausted' => true,
        ]);

        $response = $this->post("/admin/google-keys/{$key->id}/reset");

        $response->assertRedirect();
        $this->assertDatabaseHas('google_api_keys', [
            'id' => $key->id,
            'requests_today' => 0,
            'is_exhausted' => false,
        ]);
    }

    public function test_admin_can_reset_all_quotas(): void
    {
        GoogleApiKey::create(['api_key' => 'key_a', 'requests_today' => 50]);
        GoogleApiKey::create(['api_key' => 'key_b', 'requests_today' => 95, 'is_exhausted' => true]);

        $response = $this->post('/admin/google-keys/reset-all');

        $response->assertRedirect();
        $this->assertEquals(0, GoogleApiKey::where('requests_today', '>', 0)->count());
    }

    public function test_admin_can_delete_key(): void
    {
        $key = GoogleApiKey::create(['api_key' => 'key_to_delete']);

        $response = $this->delete("/admin/google-keys/{$key->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('google_api_keys', ['id' => $key->id]);
    }
}
