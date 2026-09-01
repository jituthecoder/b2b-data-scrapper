<?php

namespace App\Http\Controllers\Api\V1\Crawler;

use App\Domain\Crawling\Models\CrawlAttempt;
use App\Domain\Crawling\Models\CrawlJob;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrawlerJobFailureController extends Controller
{
    public function failed(Request $request, string $jobId): JsonResponse
    {
        $node = $request->attributes->get('crawler_node');
        $job = CrawlJob::find($jobId);

        if (!$job) {
            return response()->json(['error' => 'Not Found', 'message' => 'Crawl job not found.'], 404);
        }

        $errorMessage = $request->input('error', 'Unknown crawler error');
        $newAttemptCount = $job->attempt_count + 1;

        CrawlAttempt::create([
            'crawl_job_id' => $job->id,
            'crawler_id' => $node->crawler_id,
            'attempt_number' => $newAttemptCount,
            'status' => 'failed',
            'duration_ms' => $request->input('duration_ms', 0),
            'response_code' => $request->input('response_code', 500),
            'error_message' => $errorMessage,
            'created_at' => now(),
        ]);

        if ($newAttemptCount >= $job->max_attempts) {
            $job->update([
                'status' => 'failed',
                'attempt_count' => $newAttemptCount,
                'failed_at' => now(),
                'last_error' => $errorMessage,
            ]);
        } else {
            $job->update([
                'status' => 'pending',
                'crawler_id' => null,
                'claimed_at' => null,
                'lease_expires_at' => null,
                'attempt_count' => $newAttemptCount,
                'last_error' => $errorMessage,
            ]);
        }

        return response()->json([
            'message' => 'Job failure recorded successfully.',
            'job_id' => $job->id,
            'status' => $job->status,
            'attempt_count' => $job->attempt_count,
        ]);
    }
}
