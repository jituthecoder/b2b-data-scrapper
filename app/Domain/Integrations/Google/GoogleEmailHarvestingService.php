<?php

namespace App\Domain\Integrations\Google;

use App\Domain\DataProcessing\EmailNormalizationService;
use App\Domain\Domains\Models\Domain;
use App\Domain\Emails\Models\Email;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleEmailHarvestingService
{
    public function __construct(
        private GoogleSearchKeyPoolService $keyPool,
        private EmailNormalizationService $emailNormalizer
    ) {}

    public function harvestOffSiteEmails(Domain $domain): array
    {
        $domainName = $domain->normalized_domain;
        $query = "\"*@{$domainName}\" -site:{$domainName} -site:youtube.com -site:instagram.com -site:twitter.com -site:facebook.com -site:linkedin.com -site:pinterest.com";

        $activeKey = $this->keyPool->getNextAvailableKey($query);

        if (!$activeKey) {
            Log::warning("GoogleEmailHarvestingService: No available Google API keys for query: {$query}");
            return [];
        }

        $discoveredEmails = [];

        try {
            $cx = config('services.google_search.cx', env('GOOGLE_SEARCH_CX'));
            $url = "https://www.googleapis.com/customsearch/v1?key={$activeKey->api_key}&cx={$cx}&q=" . urlencode($query);

            $response = Http::timeout(10)->get($url);
            $this->keyPool->recordUsage($activeKey->id);

            if ($response->successful()) {
                $data = $response->json();
                $items = $data['items'] ?? [];

                foreach ($items as $item) {
                    $snippet = ($item['snippet'] ?? '') . ' ' . ($item['title'] ?? '');
                    $link = $item['link'] ?? '';

                    // 1. Extract emails from snippet text directly
                    preg_match_all('/[a-zA-Z0-9._%+-]+@' . preg_quote($domainName, '/') . '/i', $snippet, $matches);
                    if (!empty($matches[0])) {
                        foreach ($matches[0] as $rawEmail) {
                            $norm = $this->emailNormalizer->normalize($rawEmail);
                            if ($norm && !empty($norm['email'])) {
                                $emailModel = Email::firstOrCreate(
                                    ['email' => $norm['email']],
                                    [
                                        'domain_id' => $domain->id,
                                        'normalized_email' => $norm['normalized_email'] ?? $norm['email'],
                                        'local_part' => $norm['local_part'],
                                        'domain' => $norm['domain'],
                                        'type' => $norm['type'],
                                        'verification_status' => 'unverified',
                                    ]
                                );
                                $emailModel->update(['domain_id' => $domain->id]);
                                $discoveredEmails[] = $norm['email'];
                            }
                        }
                    }

                    // 2. Fetch candidate link HTML if valid and extract emails
                    if ($link && !preg_match('~\.(pdf|png|jpg|zip)$~i', $link)) {
                        try {
                            $pageRes = Http::timeout(5)->get($link);
                            if ($pageRes->successful()) {
                                $pageHtml = $pageRes->body();
                                preg_match_all('/[a-zA-Z0-9._%+-]+@' . preg_quote($domainName, '/') . '/i', $pageHtml, $pageMatches);
                                if (!empty($pageMatches[0])) {
                                    foreach ($pageMatches[0] as $rawEmail) {
                                        $norm = $this->emailNormalizer->normalize($rawEmail);
                                        if ($norm && !empty($norm['email'])) {
                                            $emailModel = Email::firstOrCreate(
                                                ['email' => $norm['email']],
                                                [
                                                    'domain_id' => $domain->id,
                                                    'normalized_email' => $norm['normalized_email'] ?? $norm['email'],
                                                    'local_part' => $norm['local_part'],
                                                    'domain' => $norm['domain'],
                                                    'type' => $norm['type'],
                                                    'verification_status' => 'unverified',
                                                ]
                                            );
                                            $emailModel->update(['domain_id' => $domain->id]);
                                            $discoveredEmails[] = $norm['email'];
                                        }
                                    }
                                }
                            }
                        } catch (\Throwable $t) {
                            // Link fetch failure
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error("GoogleEmailHarvestingService Error: " . $e->getMessage());
        }

        return array_unique($discoveredEmails);
    }
}
