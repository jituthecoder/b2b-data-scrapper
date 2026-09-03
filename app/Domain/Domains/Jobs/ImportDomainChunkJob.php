<?php

namespace App\Domain\Domains\Jobs;

use App\Domain\Crawling\Models\CrawlJob;
use App\Domain\DataProcessing\DomainNormalizationService;
use App\Domain\Domains\Models\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ImportDomainChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $rawDomains
    ) {
        $this->onQueue('imports');
    }

    public function handle(DomainNormalizationService $normalizer): void
    {
        if (empty($this->rawDomains)) {
            return;
        }

        $insertData = [];
        $now = now()->toDateTimeString();

        foreach ($this->rawDomains as $item) {
            $raw = is_array($item) ? ($item[0] ?? '') : (string) $item;
            $raw = trim($raw);

            if (empty($raw) || strlen($raw) > 255) {
                continue;
            }

            $norm = $normalizer->normalize($raw);
            if (empty($norm['normalized_domain'])) {
                continue;
            }

            $insertData[$norm['normalized_domain']] = [
                'domain' => $norm['domain'],
                'normalized_domain' => $norm['normalized_domain'],
                'scheme' => $norm['scheme'],
                'www_variant' => $norm['www_variant'] ? 1 : 0,
                'tld' => $norm['tld'],
                'status' => 'active',
                'crawl_status' => 'pending',
                'priority' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (empty($insertData)) {
            return;
        }

        // Bulk upsert into PostgreSQL
        Domain::upsert(
            array_values($insertData),
            ['normalized_domain'],
            ['domain', 'scheme', 'www_variant', 'tld', 'updated_at']
        );

        // Fetch inserted domain IDs to create initial crawl jobs in 1 bulk operation
        $domains = Domain::whereIn('normalized_domain', array_keys($insertData))->select(['id'])->get();

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
    }
}
