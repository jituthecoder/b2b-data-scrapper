<?php

namespace App\Console\Commands;

use App\Domain\Integrations\Google\Models\GoogleApiKey;
use Illuminate\Console\Command;

class ResetGoogleKeyQuotasCommand extends Command
{
    protected $signature = 'google:reset-quotas';
    protected $description = 'Reset daily requests counter and exhaustion state for all Google Search API keys in database';

    public function handle(): int
    {
        $updated = GoogleApiKey::where('is_active', true)->update([
            'requests_today' => 0,
            'is_exhausted' => false,
        ]);

        $this->info("Successfully reset daily search quotas for {$updated} Google API keys.");
        return self::SUCCESS;
    }
}
