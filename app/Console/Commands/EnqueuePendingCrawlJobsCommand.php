<?php

namespace App\Console\Commands;

use App\Domain\Crawling\Models\CrawlJob;
use App\Domain\Domains\Models\Domain;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class EnqueuePendingCrawlJobsCommand extends Command
{
    protected $signature = 'crawl:enqueue-missing {--chunk=5000 : Chunk size for bulk job creation}';

    protected $description = 'Generate pending homepage crawl jobs for all registered domains that lack a crawl job';

    public function handle(): int
    {
        $this->info("Scanning for domains without pending crawl jobs...");
        $startTime = microtime(true);

        $now = now()->toDateTimeString();
        $chunkSize = (int) $this->option('chunk');

        // Find domains that do not have a homepage crawl job yet
        $query = Domain::whereDoesntHave('crawlJobs', function ($q) {
            $q->where('job_type', 'homepage');
        });

        $totalMissing = $query->count();
        $this->info("Found {$totalMissing} domains missing crawl jobs.");

        if ($totalMissing === 0) {
            $this->info("All domains already have queued crawl jobs!");
            return Command::SUCCESS;
        }

        $processed = 0;
        $query->select(['id'])->chunkById($chunkSize, function ($domains) use (&$processed, $now) {
            $crawlJobs = [];
            foreach ($domains as $d) {
                $crawlJobs[] = [
                    'id' => (string) Str::uuid(),
                    'domain_id' => $d->id,
                    'job_type' => 'homepage',
                    'priority' => 0,
                    'status' => 'pending',
                    'attempt_count' => 0,
                    'max_attempts' => 3,
                    'idempotency_key' => 'init-homepage-' . $d->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($crawlJobs)) {
                CrawlJob::upsert(
                    $crawlJobs,
                    ['idempotency_key'],
                    ['updated_at']
                );
            }

            $processed += count($domains);
            $this->info("[Enqueued] {$processed} / " . count($domains) . " crawl jobs queued.");
        });

        $duration = round(microtime(true) - $startTime, 2);
        $this->info("Enqueued {$processed} crawl jobs in {$duration} seconds!");

        return Command::SUCCESS;
    }
}
