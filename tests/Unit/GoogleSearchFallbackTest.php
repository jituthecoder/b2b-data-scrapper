<?php

namespace Tests\Unit;

use App\Domain\Integrations\Google\GoogleSearchFallbackService;
use App\Domain\Integrations\Google\GoogleSearchKeyPoolService;
use App\Domain\Integrations\Google\Models\GoogleApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleSearchFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_add_keys_command_and_database_rotation(): void
    {
        // Execute bulk import command
        Artisan::call('google:add-keys', ['file_or_keys' => 'db_key1,db_key2,db_key3']);

        $this->assertDatabaseHas('google_api_keys', ['api_key' => 'db_key1']);
        $this->assertDatabaseHas('google_api_keys', ['api_key' => 'db_key2']);

        $pool = new GoogleSearchKeyPoolService();
        $key1 = $pool->getNextAvailableKey();
        $this->assertNotNull($key1);

        // Increment key1 to daily limit
        $dbKey1 = GoogleApiKey::where('api_key', $key1)->first();
        $dbKey1->update(['requests_today' => 95, 'is_exhausted' => true]);

        // Key pool should now automatically return next available key
        $key2 = $pool->getNextAvailableKey();
        $this->assertNotEquals($key1, $key2);
    }

    public function test_reset_quotas_command(): void
    {
        GoogleApiKey::create([
            'api_key' => 'exhausted_key',
            'requests_today' => 95,
            'is_exhausted' => true,
        ]);

        Artisan::call('google:reset-quotas');

        $this->assertDatabaseHas('google_api_keys', [
            'api_key' => 'exhausted_key',
            'requests_today' => 0,
            'is_exhausted' => false,
        ]);
    }

    public function test_google_search_fallback_finds_contact_url(): void
    {
        GoogleApiKey::create([
            'api_key' => 'valid_db_key',
            'cx' => 'test_cx_id',
        ]);

        Http::fake([
            'googleapis.com/customsearch/*' => Http::response([
                'items' => [
                    ['link' => 'https://w3speedup.com/contact-us']
                ]
            ], 200)
        ]);

        $pool = new GoogleSearchKeyPoolService();
        $service = new GoogleSearchFallbackService($pool, 'test_cx_id');

        $resultUrl = $service->findMissingPageUrl('w3speedup.com', 'contact');

        $this->assertEquals('https://w3speedup.com/contact-us', $resultUrl);
    }
}
