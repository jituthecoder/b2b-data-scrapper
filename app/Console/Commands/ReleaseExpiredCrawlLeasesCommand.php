<?php

namespace App\Console\Commands;

use App\Domain\Crawling\Models\CrawlJob;
use Illuminate\Console\Command;

class ReleaseExpiredCrawlLeasesCommand extends Command
{
    protected $signature = 'crawler:release-expired-leases';
    protected $description = 'Release expired crawler job leases back to the pending queue';

    public function handle(): int
    {
        $expiredJobs = CrawlJob::where('status', 'claimed')
            ->where('lease_expires_at', '<', now())
            ->get();

        $count = 0;
        foreach ($expiredJobs as $job) {
            $job->update([
                'status' => 'pending',
                'crawler_id' => null,
                'claimed_at' => null,
                'lease_expires_at' => null,
                'attempt_count' => $job->attempt_count + 1,
                'last_error' => 'Lease expired (worker heartbeat/timeout failure)',
            ]);
            $count++;
        }

        $this->info("Released {$count} expired crawl job leases back to pending.");

        return Command::SUCCESS;
    }
}
