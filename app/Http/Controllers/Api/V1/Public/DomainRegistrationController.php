<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Domain\Crawling\Models\CrawlJob;
use App\Domain\DataProcessing\DomainNormalizationService;
use App\Domain\Domains\Models\Domain;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DomainRegistrationController extends Controller
{
    public function register(Request $request, DomainNormalizationService $normalizer): JsonResponse
    {
        $request->validate([
            'domain' => 'required|string|max:255',
        ]);

        $inputDomain = $request->input('domain');
        $normalizedData = $normalizer->normalize($inputDomain);

        $domain = Domain::updateOrCreate(
            ['normalized_domain' => $normalizedData['normalized_domain']],
            [
                'domain' => $normalizedData['domain'],
                'tld' => $normalizedData['tld'],
                'scheme' => $normalizedData['scheme'],
                'has_www' => $normalizedData['www_variant'] ?? false,
                'status' => 'active',
                'crawl_status' => 'pending',
                'crawl_cadence_days' => 30,
            ]
        );

        // Stage 1: Fast Reachability Job Creation (Priority 100)
        $job = CrawlJob::create([
            'id' => (string) Str::uuid(),
            'domain_id' => $domain->id,
            'job_type' => 'reachability',
            'priority' => 100,
            'status' => 'pending',
            'attempt_count' => 0,
            'max_attempts' => 3,
        ]);

        return response()->json([
            'message' => 'Domain registered successfully. Stage 1 reachability job queued.',
            'domain' => [
                'id' => $domain->id,
                'domain' => $domain->domain,
                'normalized_domain' => $domain->normalized_domain,
                'tld' => $domain->tld,
                'crawl_status' => $domain->crawl_status,
            ],
            'stage1_job' => [
                'job_id' => $job->id,
                'job_type' => $job->job_type,
                'priority' => $job->priority,
                'status' => $job->status,
            ]
        ], 201);
    }
}
