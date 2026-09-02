<?php

namespace App\Http\Controllers\Api\V1\Crawler;

use App\Domain\Crawling\Models\CrawlJob;
use App\Domain\Crawling\Services\SystemCrawlControlService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrawlerJobClaimController extends Controller
{
    public function claim(Request $request, SystemCrawlControlService $crawlControl): JsonResponse
    {
        if ($crawlControl->isPaused()) {
            return response()->json([
                'status' => 'paused',
                'message' => 'Global crawler system is currently PAUSED by admin.',
                'claimed_count' => 0,
                'jobs' => [],
            ]);
        }

        if ($crawlControl->isStopped()) {
            return response()->json([
                'status' => 'stopped',
                'message' => 'Global crawler system is currently STOPPED by admin.',
                'claimed_count' => 0,
                'jobs' => [],
            ]);
        }

        $node = $request->attributes->get('crawler_node');
        $limit = min((int) ($request->input('limit', 20)), 100);
        $capabilityFilter = $request->input('capability');
        $allowedTypes = $capabilityFilter ? [$capabilityFilter] : null;

        // Atomically claim pending jobs using composite B-Tree index (status, priority, created_at)
        $claimedJobs = DB::transaction(function () use ($allowedTypes, $limit, $node) {
            // 1. Fetch & lock ONLY the top pending job IDs from crawl_jobs table (Sub-millisecond index lookup!)
            $jobIds = CrawlJob::where('status', 'pending')
                ->when(!empty($allowedTypes), fn($q) => $q->whereIn('job_type', $allowedTypes))
                ->orderBy('priority', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->lock('FOR UPDATE SKIP LOCKED')
                ->pluck('id')
                ->toArray();

            if (empty($jobIds)) {
                return [];
            }

            $leaseExpiresAt = now()->addMinutes(10);

            // 2. Mark jobs as claimed
            CrawlJob::whereIn('id', $jobIds)->update([
                'status' => 'claimed',
                'crawler_id' => $node->crawler_id,
                'claimed_at' => now(),
                'lease_expires_at' => $leaseExpiresAt,
            ]);

            // 3. Eager load domain without locking domains table
            $jobs = CrawlJob::with('domain')->whereIn('id', $jobIds)->get();

            $claimed = [];
            foreach ($jobs as $job) {
                $claimed[] = [
                    'job_id' => $job->id,
                    'domain_id' => $job->domain_id,
                    'domain' => $job->domain ? $job->domain->domain : null,
                    'job_type' => $job->job_type,
                    'priority' => $job->priority,
                    'payload' => $job->payload,
                    'lease_expires_at' => $leaseExpiresAt->toIso8601String(),
                ];
            }

            return $claimed;
        });

        return response()->json([
            'status' => 'active',
            'crawler_id' => $node->crawler_id,
            'claimed_count' => count($claimedJobs),
            'jobs' => $claimedJobs,
        ]);
    }
}
