<?php

namespace App\Console\Commands;

use App\Domain\Domains\Jobs\ImportDomainChunkJob;
use App\Domain\Domains\Models\Domain;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ImportDomainsCsvCommand extends Command
{
    protected $signature = 'import:domains 
                            {file : Path to the CSV file containing domain names} 
                            {--chunk=2000 : Number of domains per batch}
                            {--skip=0 : Number of lines to skip}
                            {--resume : Automatically resume after the last inserted domain in DB}
                            {--resume-from= : Resume CSV import immediately after this specific domain}
                            {--delay=10 : Sleep milliseconds between batches to protect RDS CPU}
                            {--sync : Run chunk jobs synchronously inline (default true)}';

    protected $description = 'Stream and import multi-million domain CSV dataset safely into PostgreSQL RDS';

    public function handle(): int
    {
        $filePath = $this->argument('file');
        $chunkSize = (int) $this->option('chunk');
        $skipCount = (int) $this->option('skip');
        $resumeAuto = (bool) $this->option('resume');
        $resumeFromDomain = $this->option('resume-from');
        $delayMs = (int) $this->option('delay');
        $isSync = $this->option('sync') || !$this->hasOption('queue');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        // 1. Determine resume target domain if requested
        $targetResumeDomain = null;
        if ($resumeAuto) {
            $lastDomainObj = Domain::orderBy('id', 'desc')->first(['id', 'domain', 'normalized_domain']);
            if ($lastDomainObj) {
                $targetResumeDomain = strtolower(trim($lastDomainObj->normalized_domain ?: $lastDomainObj->domain));
                $totalExistingInDb = Domain::count();
                $this->info("=== Auto-Resume Mode Activated ===");
                $this->info("Current DB Domains Count: " . number_format($totalExistingInDb));
                $this->info("Last Inserted DB Domain: ID #{$lastDomainObj->id} -> '{$targetResumeDomain}'");
            } else {
                $this->info("DB is empty. Starting fresh import from beginning.");
            }
        } elseif ($resumeFromDomain) {
            $targetResumeDomain = strtolower(trim($resumeFromDomain));
            $this->info("=== Manual Resume Mode Activated ===");
            $this->info("Seeking target resume domain in CSV: '{$targetResumeDomain}'");
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->error("Could not open file: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("Streaming domain import from: {$filePath}");
        $startTime = microtime(true);

        $chunk = [];
        $totalProcessed = 0;
        $totalSkipped = 0;
        $batchesCount = 0;
        $isSeekingResume = !empty($targetResumeDomain);

        if ($isSeekingResume) {
            $this->info("Scanning CSV to locate last imported domain '{$targetResumeDomain}'...");
        }

        $lineIndex = 0;
        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $lineIndex++;
            $raw = $data[0] ?? '';
            $domain = trim(mb_convert_encoding((string) $raw, 'UTF-8', 'UTF-8'));
            $domain = preg_replace('/[^\x20-\x7E]/', '', $domain);

            if (empty($domain) || strtolower($domain) === 'domain' || strtolower($domain) === 'url') {
                continue;
            }

            $cleanDomain = strtolower($domain);

            // Fast-forward phase if seeking resume position
            if ($isSeekingResume) {
                $totalSkipped++;
                if ($totalSkipped % 100000 === 0) {
                    $this->info("[Scanning CSV] Checked {$totalSkipped} lines...");
                }

                if ($cleanDomain === $targetResumeDomain) {
                    $this->info("SUCCESS! Found exact last DB domain '{$cleanDomain}' at CSV line #{$lineIndex}. Resuming import from next line...");
                    $isSeekingResume = false;
                }
                continue;
            }

            // Handle manual numeric skip
            if ($skipCount > 0 && $totalSkipped < $skipCount) {
                $totalSkipped++;
                if ($totalSkipped % 100000 === 0) {
                    $this->info("[Skipping] Fast-forwarded {$totalSkipped} / {$skipCount} lines...");
                }
                continue;
            }

            $chunk[] = $domain;
            $totalProcessed++;

            if (count($chunk) >= $chunkSize) {
                $this->processChunk($chunk, $isSync, $delayMs);
                $batchesCount++;
                $chunk = [];

                if ($totalProcessed % 2000 === 0) {
                    $elapsed = round(microtime(true) - $startTime, 1);
                    $rate = round($totalProcessed / max(1, $elapsed));
                    $this->info("[Progress] Imported " . number_format($totalProcessed) . " new domains (" . number_format($rate) . " domains/sec, Elapsed: {$elapsed}s)");
                    if (function_exists('ob_flush')) { @ob_flush(); }
                    @flush();
                }
            }
        }

        if ($isSeekingResume) {
            $this->warn("Target domain '{$targetResumeDomain}' was not found in CSV. Imported remaining unindexed domains.");
        }

        if (!empty($chunk)) {
            $this->processChunk($chunk, $isSync, $delayMs);
            $batchesCount++;
        }

        fclose($handle);

        Cache::forget('admin_dashboard_stats');
        Cache::forget('domains_count_' . md5('_'));

        $duration = round(microtime(true) - $startTime, 2);
        $totalInDbNow = Domain::count();

        $this->info("==========================================");
        $this->info("Import Completed Successfully!");
        $this->info("New Domains Imported This Run: " . number_format($totalProcessed));
        $this->info("Total Domains in Database Now: " . number_format($totalInDbNow));
        $this->info("Batches Executed: " . number_format($batchesCount));
        $this->info("Time Elapsed: {$duration} seconds");
        $this->info("==========================================");

        return Command::SUCCESS;
    }

    private function processChunk(array $chunk, bool $isSync, int $delayMs): void
    {
        if ($isSync) {
            ImportDomainChunkJob::dispatchSync($chunk);
        } else {
            ImportDomainChunkJob::dispatch($chunk);
        }

        if ($delayMs > 0) {
            usleep($delayMs * 1000);
        }
    }
}
