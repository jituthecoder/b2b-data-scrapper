<?php

namespace App\Domain\DataProcessing\Jobs;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Services\CompanyLogoStorageService;
use App\Domain\Contacts\Models\Contact;
use App\Domain\Crawling\Models\CrawlJob;
use App\Domain\DataProcessing\CompanyNormalizationService as DataCompanyNormalizer;
use App\Domain\DataProcessing\EmailNormalizationService;
use App\Domain\DataProcessing\PhoneNormalizationService;
use App\Domain\DataProcessing\SocialUrlNormalizationService;
use App\Domain\Domains\Models\Domain;
use App\Domain\Domains\Services\WebsiteScreenshotStorageService;
use App\Domain\Emails\Models\Email;
use App\Domain\Integrations\Google\GoogleEmailHarvestingService;
use App\Domain\Integrations\Google\GoogleFacebookDiscoveryService;
use App\Domain\Integrations\Google\GoogleSearchFallbackService;
use App\Domain\Pages\Models\Page;
use App\Domain\Phones\Models\Phone;
use App\Domain\SocialProfiles\Models\SocialProfile;
use App\Domain\Technologies\Models\Technology;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessCrawlResultJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $jobId,
        public array $payload
    ) {}

    public function handle(
        EmailNormalizationService $emailNormalizer,
        PhoneNormalizationService $phoneNormalizer,
        SocialUrlNormalizationService $socialNormalizer,
        DataCompanyNormalizer $companyNormalizer,
        GoogleSearchFallbackService $googleFallback,
        GoogleFacebookDiscoveryService $facebookDiscovery,
        GoogleEmailHarvestingService $emailHarvester,
        CompanyLogoStorageService $logoStorage,
        WebsiteScreenshotStorageService $screenshotStorage
    ): void {
        $job = CrawlJob::find($this->jobId);
        if (!$job) {
            Log::warning("ProcessCrawlResultJob: Job {$this->jobId} not found.");
            return;
        }

        $domain = $job->domain;
        if (!$domain) {
            Log::warning("ProcessCrawlResultJob: Domain missing for job {$this->jobId}.");
            return;
        }

        $jobType = $job->job_type;
        $domainStatus = $this->payload['domain_status'] ?? [];
        $isAccessible = (bool) ($domainStatus['is_accessible'] ?? true);
        $httpStatus = (int) ($domainStatus['http_status'] ?? 200);

        // Dynamically update scheme & www_variant from final redirected crawl URL
        $finalUrl = $domainStatus['final_url'] ?? $domainStatus['canonical_url'] ?? null;
        $updates = [
            'is_accessible' => $isAccessible,
            'http_status' => $httpStatus,
            'last_crawled_at' => now(),
        ];

        if ($finalUrl) {
            $updates['final_url'] = $finalUrl;
            $scheme = parse_url($finalUrl, PHP_URL_SCHEME);
            if ($scheme) {
                $updates['scheme'] = strtolower($scheme);
            }
            $host = parse_url($finalUrl, PHP_URL_HOST);
            if ($host) {
                $updates['www_variant'] = str_starts_with(strtolower($host), 'www.');
            }
        }

        // STAGE 1: Reachability Check
        if ($jobType === 'reachability') {
            $domain->update($updates);

            $autoAdvance = (bool) ($job->payload['auto_advance'] ?? true);

            if ($isAccessible) {
                if ($autoAdvance) {
                    $domain->update(['crawl_status' => 'in_progress']);

                    // Auto-advance to STAGE 2: Homepage & Full Data Extract (Inherits priority)
                    CrawlJob::create([
                        'id' => (string) Str::uuid(),
                        'domain_id' => $domain->id,
                        'job_type' => 'homepage',
                        'priority' => $job->priority ?? 50,
                        'status' => 'pending',
                        'attempt_count' => 0,
                        'max_attempts' => 3,
                        'payload' => $job->payload,
                    ]);

                    Log::info("Pipeline STAGE 1 Passed for {$domain->domain}. Auto-queued STAGE 2 (homepage) with priority {$job->priority}.");
                } else {
                    $domain->update(['crawl_status' => 'completed']);
                    Log::info("Pipeline STAGE 1 Passed for {$domain->domain}. Auto-advance disabled by user. Pipeline completed.");
                }
            } else {
                $domain->update(['crawl_status' => 'failed']);
                Log::info("Pipeline STAGE 1 Failed for {$domain->domain} (Unreachable HTTP {$httpStatus}). Pipeline stopped.");
            }

            return;
        }

        // STAGE 2 & STAGE 3 Data Processing
        $updates['crawl_status'] = 'completed';
        $domain->update($updates);

        // Process Website Visual Screenshot Snapshot on S3
        if ($isAccessible) {
            $screenshotData = $this->payload['domain_status']['screenshot'] ?? $this->payload['screenshot'] ?? null;
            if (!$screenshotData) {
                try {
                    $snapRes = Http::timeout(8)->get("https://api.microlink.io/?url=https://{$domain->normalized_domain}&screenshot=true");
                    if ($snapRes->successful()) {
                        $snapJson = $snapRes->json();
                        $screenshotData = $snapJson['data']['screenshot']['url'] ?? null;
                    }
                } catch (\Throwable $e) {
                    Log::warning("Microlink Screenshot Fallback Error: " . $e->getMessage());
                }
            }

            if ($screenshotData) {
                $screenshotStorage->storeScreenshot($domain, $screenshotData);
            }
        }

        // Process Company & Upload Logo to S3/Storage
        if (!empty($this->payload['company']['name'])) {
            $rawCompany = $this->payload['company']['name'];
            $normalizedCompany = $companyNormalizer->normalize($rawCompany);

            $company = Company::firstOrCreate(
                ['normalized_name' => $normalizedCompany['normalized_name']],
                [
                    'name' => $rawCompany,
                    'legal_name' => $normalizedCompany['legal_name'] ?? $rawCompany,
                    'description' => $this->payload['company']['description'] ?? null,
                ]
            );

            $domain->companies()->syncWithoutDetaching([$company->id => ['is_primary' => true]]);

            $logoUrlCandidate = $this->payload['company']['logo_url'] ?? null;
            $logoStorage->storeLogo($company, $logoUrlCandidate, $domain->normalized_domain);
        }

        // Process Technologies
        if (!empty($this->payload['technologies']) && is_array($this->payload['technologies'])) {
            foreach ($this->payload['technologies'] as $tech) {
                if (empty($tech['name'])) continue;
                $techModel = Technology::firstOrCreate(
                    ['slug' => Str::slug($tech['name'])],
                    [
                        'name' => $tech['name'],
                        'category' => $tech['category'] ?? 'General',
                    ]
                );

                $domain->technologies()->syncWithoutDetaching([
                    $techModel->id => [
                        'first_detected_at' => now(),
                        'last_detected_at' => now(),
                        'confidence_score' => $tech['confidence_score'] ?? 1.00,
                    ]
                ]);
            }
        }

        // Process Emails
        if (!empty($this->payload['emails']) && is_array($this->payload['emails'])) {
            foreach ($this->payload['emails'] as $rawEmail) {
                if (is_array($rawEmail)) {
                    $rawEmail = $rawEmail['email'] ?? null;
                }
                if (empty($rawEmail) || !is_string($rawEmail)) continue;

                $norm = $emailNormalizer->normalize($rawEmail);
                if (!$norm || empty($norm['email'])) continue;

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
            }
        }

        // Process Phones
        if (!empty($this->payload['phones']) && is_array($this->payload['phones'])) {
            foreach ($this->payload['phones'] as $rawPhone) {
                if (is_array($rawPhone)) {
                    $rawPhone = $rawPhone['phone'] ?? $rawPhone['phone_number'] ?? null;
                }
                if (empty($rawPhone) || !is_string($rawPhone)) continue;

                $normPhone = $phoneNormalizer->normalize($rawPhone);
                $phoneStr = $normPhone['normalized_phone'] ?? $rawPhone;
                if (empty($phoneStr)) continue;

                Phone::firstOrCreate(
                    ['normalized_phone' => $phoneStr],
                    [
                        'phone_number' => $rawPhone,
                        'type' => 'work',
                    ]
                );
            }
        }

        // Process Social Profiles
        if (!empty($this->payload['social_profiles']) && is_array($this->payload['social_profiles'])) {
            foreach ($this->payload['social_profiles'] as $rawSocial) {
                if (is_array($rawSocial)) {
                    $rawSocial = $rawSocial['url'] ?? $rawSocial['profile_url'] ?? null;
                }
                if (empty($rawSocial) || !is_string($rawSocial)) continue;

                $normSocial = $socialNormalizer->normalize($rawSocial);
                $targetUrl = $normSocial['normalized_url'] ?? $normSocial['profile_url'] ?? $rawSocial;
                $platform = $normSocial['platform'] ?? 'other';
                $handle = $normSocial['username_handle'] ?? null;

                if (empty($targetUrl)) continue;

                SocialProfile::firstOrCreate(
                    [
                        'normalized_url' => $targetUrl,
                    ],
                    [
                        'entity_type' => Domain::class,
                        'entity_id' => $domain->id,
                        'platform' => $platform,
                        'profile_url' => $rawSocial,
                        'username_handle' => $handle,
                    ]
                );
            }
        }

        // Process Pages & Discover Sub-Pages (STAGE 3) - Strictly allow only Homepage, Contact, About, Careers, Team
        $allowedTypes = ['homepage', 'contact', 'about', 'careers', 'team'];
        if (!empty($this->payload['pages']) && is_array($this->payload['pages'])) {
            // Purge old unallowed pages, hash fragment URLs, and services/case-study/iso links for this domain
            Page::where('domain_id', $domain->id)
                ->where(function ($query) use ($allowedTypes) {
                    $query->whereNotIn('page_type', $allowedTypes)
                          ->orWhere('url', 'LIKE', '%#%')
                          ->orWhere('url', 'LIKE', '%/services/%')
                          ->orWhere('url', 'LIKE', '%/iso-%')
                          ->orWhere('url', 'LIKE', '%/case-study/%');
                })
                ->delete();

            foreach ($this->payload['pages'] as $p) {
                if (empty($p['url'])) continue;
                $type = strtolower($p['page_type'] ?? 'general');
                if (!in_array($type, $allowedTypes, true)) continue;

                // Strip anchor hash fragments and normalize trailing slash
                $cleanUrl = strtok($p['url'], '#');
                if (strlen($cleanUrl) > 8 && str_ends_with($cleanUrl, '/')) {
                    $cleanUrl = rtrim($cleanUrl, '/');
                }

                $normalizedUrl = preg_replace('~^https?://(www\.)?~i', '', $cleanUrl);
                $normalizedUrl = strtolower(rtrim($normalizedUrl, '/'));

                Page::firstOrCreate(
                    [
                        'domain_id' => $domain->id,
                        'normalized_url' => $normalizedUrl,
                    ],
                    [
                        'url' => $cleanUrl,
                        'page_type' => $type,
                        'title' => $p['title'] ?? null,
                        'http_status' => $p['http_status'] ?? 200,
                    ]
                );
            }
        }

        // Normal Scrape Fallback: If Contact page was missing, trigger Google Custom Search Fallback
        if (!$domain->pages()->where('page_type', 'contact')->exists()) {
            $contactUrl = $googleFallback->findMissingPageUrl($domain->normalized_domain, 'contact');
            if ($contactUrl) {
                $cleanContactUrl = rtrim(strtok($contactUrl, '#'), '/');
                $normContactUrl = strtolower(preg_replace('~^https?://(www\.)?~i', '', $cleanContactUrl));
                Page::firstOrCreate(
                    [
                        'domain_id' => $domain->id,
                        'normalized_url' => $normContactUrl,
                    ],
                    [
                        'url' => $cleanContactUrl,
                        'page_type' => 'contact',
                        'title' => 'Contact Us',
                        'http_status' => 200,
                    ]
                );
            }
        }

        // DEEP SCRAPE MODE CONDITIONAL FALLBACKS (If 0 emails found & Deep Mode enabled or requested)
        $isDeepMode = ($job->payload['crawl_mode'] ?? 'normal') === 'deep';
        if ($isDeepMode && $domain->is_accessible && $domain->emails()->count() === 0) {
            Log::info("Deep Scrape 0-Email Fallback triggered for {$domain->domain}...");

            // Fallback Step 1: Facebook Google Discovery if Facebook URL missing
            $facebookUrl = $facebookDiscovery->discoverFacebookUrl($domain);

            // Fallback Step 2: Google Search Off-Site Email Harvester
            $emailHarvester->harvestOffSiteEmails($domain);
        }
    }
}
