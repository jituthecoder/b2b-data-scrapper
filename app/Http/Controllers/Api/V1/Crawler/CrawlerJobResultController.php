<?php

namespace App\Http\Controllers\Api\V1\Crawler;

use App\Domain\Crawling\Models\CrawlAttempt;
use App\Domain\Crawling\Models\CrawlJob;
use App\Domain\DataProcessing\Jobs\ProcessCrawlResultJob;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CrawlerJobResultController extends Controller
{
    public function result(Request $request, string $jobId): JsonResponse
    {
        $node = $request->attributes->get('crawler_node');
        $job = CrawlJob::find($jobId);

        if (!$job) {
            return response()->json(['error' => 'Not Found', 'message' => 'Crawl job not found.'], 404);
        }

        // Idempotency: Return 200 OK immediately if job was already completed
        if ($job->status === 'completed') {
            return response()->json([
                'message' => 'Job already completed (idempotent response).',
                'job_id' => $job->id,
                'status' => 'completed',
            ], 200);
        }

        // Mark job status as completed
        $job->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Save raw JSON payload to disk/S3 abstraction
        $s3Path = "crawls/raw_{$job->id}_" . time() . ".json";
        Storage::disk('local')->put($s3Path, json_encode($request->all(), JSON_PRETTY_PRINT));

        // Create Crawl Attempt record
        CrawlAttempt::create([
            'crawl_job_id' => $job->id,
            'crawler_id' => $node->crawler_id,
            'attempt_number' => $job->attempt_count + 1,
            'status' => 'success',
            'duration_ms' => $request->input('duration_ms', 0),
            'response_code' => $request->input('response_code', 200),
            'created_at' => now(),
        ]);

        // Dispatch Synchronous Ingestion Job to immediately populate entities
        ProcessCrawlResultJob::dispatchSync($job->id, $request->all());

        return response()->json([
            'message' => 'Crawl result accepted and queued for processing.',
            'job_id' => $job->id,
            'status' => 'processing',
        ], 202);
    }
}
