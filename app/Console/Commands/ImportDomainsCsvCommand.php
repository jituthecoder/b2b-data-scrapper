<?php

namespace App\Console\Commands;

use App\Domain\Domains\Jobs\ImportDomainChunkJob;
use Illuminate\Console\Command;

class ImportDomainsCsvCommand extends Command
{
    protected $signature = 'import:domains 
                            {file : Path to the CSV file containing domain names} 
                            {--chunk=1000 : Number of domains per background chunk job}
                            {--sync : Run chunk jobs synchronously instead of queuing}';

    protected $description = 'Stream and import multi-million domain CSV dataset using chunked queue jobs';

    public function handle(): int
    {
        $filePath = $this->argument('file');
        $chunkSize = (int) $this->option('chunk');
        $isSync = (bool) $this->option('sync');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->error("Could not open file: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("Starting domain CSV streaming import from: {$filePath}");
        $startTime = microtime(true);

        $chunk = [];
        $totalProcessed = 0;
        $chunksDispatched = 0;
        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $raw = $data[0] ?? '';
            $domain = trim(mb_convert_encoding((string) $raw, 'UTF-8', 'UTF-8'));
            $domain = preg_replace('/[^\x20-\x7E]/', '', $domain);

            if (empty($domain) || strtolower($domain) === 'domain' || strtolower($domain) === 'url') {
                continue;
            }

            $chunk[] = $domain;
            $totalProcessed++;

            if (count($chunk) >= $chunkSize) {
                $this->processChunk($chunk, $isSync);
                $chunksDispatched++;
                $chunk = [];

                if ($totalProcessed % 10000 === 0) {
                    $elapsed = round(microtime(true) - $startTime, 1);
                    $rate = round($totalProcessed / max(1, $elapsed));
                    $this->info("[Progress] Imported {$totalProcessed} domains ({$rate} domains/sec, Elapsed: {$elapsed}s)");
                }
            }
        }

        if (!empty($chunk)) {
            $this->processChunk($chunk, $isSync);
            $chunksDispatched++;
        }

        fclose($handle);

        $duration = round(microtime(true) - $startTime, 2);
        $this->info("Import complete!");
        $this->info("Total Domains Processed: {$totalProcessed}");
        $this->info("Chunk Jobs Dispatched: {$chunksDispatched}");
        $this->info("Time Elapsed: {$duration} seconds");

        return Command::SUCCESS;
    }

    private function processChunk(array $chunk, bool $isSync): void
    {
        if ($isSync) {
            ImportDomainChunkJob::dispatchSync($chunk);
        } else {
            ImportDomainChunkJob::dispatch($chunk);
        }
    }
}
