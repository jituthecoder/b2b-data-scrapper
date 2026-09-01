<?php

namespace App\Domain\Integrations\Google;

use App\Domain\Domains\Models\Domain;
use App\Domain\SocialProfiles\Models\SocialProfile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleFacebookDiscoveryService
{
    public function __construct(
        private GoogleSearchKeyPoolService $keyPool
    ) {}

    public function discoverFacebookUrl(Domain $domain): ?string
    {
        // Check if domain already has a facebook profile saved
        $existing = $domain->socialProfiles()->where('platform', 'facebook')->first();
        if ($existing && !empty($existing->profile_url)) {
            return $existing->profile_url;
        }

        $query = "{$domain->normalized_domain} (site:facebook.com OR site:facebook.in OR site:facebook.co.uk)";
        $activeKey = $this->keyPool->getNextAvailableKey($query);

        if (!$activeKey) {
            Log::warning("GoogleFacebookDiscoveryService: No available Google API keys for query: {$query}");
            return null;
        }

        try {
            $cx = config('services.google_search.cx', env('GOOGLE_SEARCH_CX'));
            $url = "https://www.googleapis.com/customsearch/v1?key={$activeKey->api_key}&cx={$cx}&q=" . urlencode($query);

            $response = Http::timeout(10)->get($url);
            $this->keyPool->recordUsage($activeKey->id);

            if ($response->successful()) {
                $data = $response->json();
                $items = $data['items'] ?? [];

                foreach ($items as $item) {
                    $link = $item['link'] ?? '';
                    if (preg_match('~https?://(www\.)?facebook\.com/([a-zA-Z0-9\.\-_]+)/?~i', $link, $matches)) {
                        $facebookUrl = "https://facebook.com/" . trim($matches[2], '/');

                        // Save discovered Facebook URL to social_profiles table
                        SocialProfile::firstOrCreate(
                            ['normalized_url' => strtolower($facebookUrl)],
                            [
                                'entity_type' => Domain::class,
                                'entity_id' => $domain->id,
                                'platform' => 'facebook',
                                'profile_url' => $facebookUrl,
                                'username_handle' => $matches[2],
                            ]
                        );

                        Log::info("GoogleFacebookDiscoveryService Found Facebook page for {$domain->domain}: {$facebookUrl}");
                        return $facebookUrl;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error("GoogleFacebookDiscoveryService Error: " . $e->getMessage());
        }

        return null;
    }
}
