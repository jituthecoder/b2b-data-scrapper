<?php

namespace App\Domain\Integrations\Google;

use App\Domain\Integrations\Google\Models\GoogleApiKey;
use Illuminate\Support\Facades\Log;

class GoogleSearchKeyPoolService
{
    private int $maxDailyPerKey = 95;

    public function getNextAvailableKey(): ?string
    {
        // 1. Query Database Key Pool for least recently used active key
        try {
            $dbKey = GoogleApiKey::available()
                ->orderByRaw('last_used_at ASC NULLS FIRST')
                ->first();

            if ($dbKey) {
                return $dbKey->api_key;
            }
        } catch (\Throwable $e) {
            Log::warning("GoogleSearchKeyPoolService DB lookup error: " . $e->getMessage());
        }

        // 2. Fallback to Environment Variables if DB is empty
        $envKeys = env('GOOGLE_SEARCH_API_KEYS', env('GOOGLE_SEARCH_API_KEY', ''));
        $keys = array_filter(array_map('trim', explode(',', $envKeys)));

        if (!empty($keys)) {
            return $keys[0];
        }

        Log::warning("GoogleSearchKeyPoolService: No available Google API keys found in Database or .env.");
        return null;
    }

    public function incrementUsage(string $key): void
    {
        try {
            $dbKey = GoogleApiKey::where('api_key', $key)->first();
            if ($dbKey) {
                $dbKey->increment('requests_today');
                $dbKey->update(['last_used_at' => now()]);

                if ($dbKey->requests_today >= $dbKey->daily_limit) {
                    $dbKey->update(['is_exhausted' => true]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("GoogleSearchKeyPoolService incrementUsage error: " . $e->getMessage());
        }
    }

    public function markKeyExhausted(string $key): void
    {
        try {
            GoogleApiKey::where('api_key', $key)->update([
                'is_exhausted' => true,
                'requests_today' => 95,
            ]);
            Log::info("GoogleSearchKeyPoolService: Key " . substr($key, 0, 6) . "... marked as exhausted in DB.");
        } catch (\Throwable $e) {
            Log::warning("GoogleSearchKeyPoolService markKeyExhausted error: " . $e->getMessage());
        }
    }

    public function getKeyPoolStatus(): array
    {
        try {
            return GoogleApiKey::all()->map(fn($k) => [
                'id' => $k->id,
                'key_prefix' => substr($k->api_key, 0, 6) . '...',
                'requests_today' => $k->requests_today,
                'daily_limit' => $k->daily_limit,
                'is_active' => $k->is_active,
                'is_exhausted' => $k->is_exhausted,
                'last_used_at' => $k->last_used_at?->toIso8601String(),
            ])->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
