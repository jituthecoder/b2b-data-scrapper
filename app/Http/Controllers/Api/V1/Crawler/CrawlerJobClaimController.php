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

        // Automatically release any expired claimed jobs from dead workers back to pending
        CrawlJob::where('status', 'claimed')
            ->where('lease_expires_at', '<', now())
            ->update([
                'status' => 'pending',
                'crawler_id' => null,
                'claimed_at' => null,
                'lease_expires_at' => null,
            ]);

        // Atomically claim pending OR expired leased jobs
        $claimedJobs = DB::transaction(function () use ($allowedTypes, $limit, $node) {
            $query = CrawlJob::with('domain')->where(function ($q) {
                $q->where('status', 'pending')
                  ->orWhere(function ($expired) {
                      $expired->where('status', 'claimed')
                              ->where('lease_expires_at', '<', now());
                  });
            });

            if (!empty($allowedTypes)) {
                $query->whereIn('job_type', $allowedTypes);
            }

            $jobs = $query->orderBy('priority', 'desc')
                ->orderBy('created_at', 'asc')
                ->limit($limit)
                ->lockForUpdate()
                ->get();

            if ($jobs->isEmpty()) {
                return [];
            }

            $leaseExpiresAt = now()->addMinutes(10);
            $jobIds = $jobs->pluck('id')->toArray();

            CrawlJob::whereIn('id', $jobIds)->update([
                'status' => 'claimed',
                'crawler_id' => $node->crawler_id,
                'claimed_at' => now(),
                'lease_expires_at' => $leaseExpiresAt,
            ]);

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
