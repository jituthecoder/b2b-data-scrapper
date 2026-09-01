<?php

namespace App\Domain\Integrations\PageSpeed;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePageSpeedInsightsService
{
    private string $apiKey;
    private string $baseUrl = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? config('services.pagespeed.api_key', env('PAGESPEED_API_KEY', ''));
    }

    public function analyze(string $url, string $strategy = 'mobile'): array
    {
        try {
            $params = [
                'url' => $url,
                'strategy' => strtolower($strategy),
                'category' => ['performance', 'accessibility', 'best-practices', 'seo'],
            ];

            if (!empty($this->apiKey)) {
                $params['key'] = $this->apiKey;
            }

            $response = Http::timeout(30)->get($this->baseUrl, $params);

            if ($response->failed()) {
                Log::warning("PageSpeed Insights API request failed for URL {$url}", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'status_code' => $response->status(),
                    'error' => 'PageSpeed API request failed',
                ];
            }

            $data = $response->json();
            $lighthouse = $data['lighthouseResult'] ?? [];
            $categories = $lighthouse['categories'] ?? [];
            $audits = $lighthouse['audits'] ?? [];

            return [
                'success' => true,
                'url' => $url,
                'strategy' => $strategy,
                'scores' => [
                    'performance' => isset($categories['performance']['score']) ? (int) ($categories['performance']['score'] * 100) : null,
                    'accessibility' => isset($categories['accessibility']['score']) ? (int) ($categories['accessibility']['score'] * 100) : null,
                    'best_practices' => isset($categories['best-practices']['score']) ? (int) ($categories['best-practices']['score'] * 100) : null,
                    'seo' => isset($categories['seo']['score']) ? (int) ($categories['seo']['score'] * 100) : null,
                ],
                'core_web_vitals' => [
                    'lcp_ms' => $audits['largest-contentful-paint']['numericValue'] ?? null,
                    'cls' => $audits['cumulative-layout-shift']['numericValue'] ?? null,
                    'fcp_ms' => $audits['first-contentful-paint']['numericValue'] ?? null,
                ],
                'timestamp' => now()->toIso8601String(),
            ];
        } catch (\Throwable $e) {
            Log::error("PageSpeed Insights Exception for URL {$url}: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
