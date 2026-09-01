<?php

namespace App\Console\Commands;

use App\Domain\Integrations\Google\Models\GoogleApiKey;
use Illuminate\Console\Command;

class ImportGoogleApiKeysCommand extends Command
{
    protected $signature = 'google:add-keys {file_or_keys : File path containing keys line-by-line OR comma-separated string of keys} {--cx= : Optional Google Search CX ID}';
    protected $description = 'Bulk import Google Custom Search API keys into database for key rotation pool';

    public function handle(): int
    {
        $input = $this->argument('file_or_keys');
        $cx = $this->option('cx');
        $rawKeys = [];

        if (file_exists($input)) {
            $this->info("Reading Google API keys from file: {$input}");
            $lines = file($input, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (!empty($trimmed) && !str_starts_with($trimmed, '#')) {
                    $rawKeys[] = $trimmed;
                }
            }
        } else {
            $parts = explode(',', $input);
            foreach ($parts as $p) {
                $trimmed = trim($p);
                if (!empty($trimmed)) {
                    $rawKeys[] = $trimmed;
                }
            }
        }

        $rawKeys = array_unique($rawKeys);
        if (empty($rawKeys)) {
            $this->error("No valid API keys found in input.");
            return self::FAILURE;
        }

        $this->info("Importing " . count($rawKeys) . " Google Search API keys into database...");

        $bar = $this->output->createProgressBar(count($rawKeys));
        $bar->start();

        $imported = 0;
        foreach ($rawKeys as $key) {
            GoogleApiKey::updateOrCreate(
                ['api_key' => $key],
                [
                    'cx' => $cx,
                    'is_active' => true,
                    'daily_limit' => 95,
                ]
            );
            $imported++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully imported {$imported} Google Search API keys!");
        $this->info("Total Active Keys in Database: " . GoogleApiKey::where('is_active', true)->count());

        return self::SUCCESS;
    }
}
