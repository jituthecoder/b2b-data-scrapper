<?php

namespace Tests\Feature;

use App\Domain\Crawling\Models\CrawlJob;
use App\Domain\Domains\Models\Domain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainCsvImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_streaming_csv_domain_import(): void
    {
        $tempFile = base_path('test_import_domains.csv');
        $csvContent = "domain\nhttps://WWW.Stripe-Test.com\nhttps://github-test.org\nhttps://Shopify-Test.net\n";
        file_put_contents($tempFile, $csvContent);

        $this->artisan('import:domains', [
            'file' => $tempFile,
            '--sync' => true,
        ])
        ->expectsOutput('Import complete!')
        ->assertExitCode(0);

        $this->assertDatabaseHas('domains', [
            'normalized_domain' => 'stripe-test.com',
        ]);
        $this->assertDatabaseHas('domains', [
            'normalized_domain' => 'github-test.org',
        ]);
        $this->assertDatabaseHas('domains', [
            'normalized_domain' => 'shopify-test.net',
        ]);

        // Verify initial pending crawl jobs auto-created
        $domain = Domain::where('normalized_domain', 'stripe-test.com')->first();
        $this->assertNotNull($domain);
        $this->assertDatabaseHas('crawl_jobs', [
            'domain_id' => $domain->id,
            'job_type' => 'homepage',
            'status' => 'pending',
        ]);

        @unlink($tempFile);
    }
}
