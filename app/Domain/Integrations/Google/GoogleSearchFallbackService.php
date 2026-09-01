<?php

namespace App\Domain\Integrations\Google;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleSearchFallbackService
{
    private string $cx;
    private GoogleSearchKeyPoolService $keyPool;

    public function __construct(?GoogleSearchKeyPoolService $keyPool = null, ?string $cx = null)
    {
        $this->keyPool = $keyPool ?? new GoogleSearchKeyPoolService();
        $this->cx = $cx ?? config('services.google.cx', env('GOOGLE_SEARCH_CX', ''));
    }

    public function findMissingPageUrl(string $domain, string $pageType = 'contact'): ?string
    {
        $cleanDomain = preg_replace('/^www\./i', '', trim($domain));

        if ($pageType === 'contact') {
            $query = "site:{$cleanDomain} inurl:contact OR inurl:contact-us";
        } elseif ($pageType === 'careers') {
            $query = "site:{$cleanDomain} inurl:careers OR inurl:jobs";
        } else {
            $query = "site:{$cleanDomain} {$pageType}";
        }

        $attempts = 0;
        $maxAttempts = 3;

        while ($attempts < $maxAttempts) {
            $apiKey = $this->keyPool->getNextAvailableKey();
            if (!$apiKey) {
                Log::warning("GoogleSearchFallbackService: No available Google API keys for query: {$query}");
                return null;
            }

            try {
                $response = Http::timeout(15)->get('https://www.googleapis.com/customsearch/v1', [
                    'key' => $apiKey,
                    'cx' => $this->cx,
                    'q' => $query,
                    'num' => 1,
                ]);

                if ($response->status() === 429 || str_contains($response->body(), 'quotaExceeded')) {
                    Log::warning("GoogleSearchFallbackService: Key quota exceeded for key prefix " . substr($apiKey, 0, 6));
                    $this->keyPool->markKeyExhausted($apiKey);
                    $attempts++;
                    continue;
                }

                if ($response->failed()) {
                    Log::warning("GoogleSearchFallbackService API Error for {$cleanDomain}", [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    return null;
                }

                $this->keyPool->incrementUsage($apiKey);
                $data = $response->json();
                $items = $data['items'] ?? [];

                if (!empty($items[0]['link'])) {
                    $foundUrl = $items[0]['link'];
                    Log::info("GoogleSearchFallbackService Found {$pageType} page for {$cleanDomain}: {$foundUrl}");
                    return $foundUrl;
                }

                return null;
            } catch (\Throwable $e) {
                Log::error("GoogleSearchFallbackService Exception: " . $e->getMessage());
                return null;
            }
        }

        return null;
    }
}
