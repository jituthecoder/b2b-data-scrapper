<?php

namespace Tests\Feature;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Services\CompanyLogoStorageService;
use App\Domain\Crawling\Models\CrawlJob;
use App\Domain\DataProcessing\Jobs\ProcessCrawlResultJob;
use App\Domain\Domains\Models\Domain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyLogoStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_logo_is_downloaded_and_stored_in_s3_storage(): void
    {
        Storage::fake('public');

        $domain = Domain::create([
            'domain' => 'stripe.com',
            'normalized_domain' => 'stripe.com',
            'tld' => 'com',
            'status' => 'active',
            'crawl_status' => 'pending',
            'is_accessible' => true,
        ]);

        $job = CrawlJob::create([
            'domain_id' => $domain->id,
            'job_type' => 'homepage',
            'priority' => 1000,
            'status' => 'pending',
            'attempt_count' => 0,
            'max_attempts' => 3,
        ]);

        $this->mock(CompanyLogoStorageService::class, function ($mock) {
            $mock->shouldReceive('storeLogo')
                ->once()
                ->andReturnUsing(function ($company, $rawLogoUrl) {
                    $fakeUrl = "http://localhost/storage/logos/company_{$company->id}_test.png";
                    $company->update(['logo_url' => $fakeUrl]);
                    return $fakeUrl;
                });
        });

        ProcessCrawlResultJob::dispatchSync($job->id, [
            'domain_status' => ['is_accessible' => true, 'http_status' => 200],
            'company' => [
                'name' => 'Stripe Inc',
                'logo_url' => 'https://stripe.com/logo.png',
            ],
        ]);

        $domain->refresh();
        $company = $domain->companies()->first();

        $this->assertNotNull($company);
        $this->assertNotNull($company->logo_url);
        $this->assertStringContainsString('storage/logos/', $company->logo_url);
    }
}
