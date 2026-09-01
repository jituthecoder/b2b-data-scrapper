<?php

namespace Tests\Feature;

use App\Domain\Crawling\Models\CrawlJob;
use App\Domain\DataProcessing\Jobs\ProcessCrawlResultJob;
use App\Domain\Domains\Models\Domain;
use App\Domain\Domains\Services\WebsiteScreenshotStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebsiteScreenshotStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_website_visual_screenshot_is_stored_in_s3(): void
    {
        Storage::fake('public');

        $domain = Domain::create([
            'domain' => 'digitilizeweb.com',
            'normalized_domain' => 'digitilizeweb.com',
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

        $this->mock(WebsiteScreenshotStorageService::class, function ($mock) use ($domain) {
            $mock->shouldReceive('storeScreenshot')
                ->once()
                ->andReturnUsing(function ($dom, $data) {
                    $fakeUrl = "https://b2b-company-logos.s3.us-east-1.amazonaws.com/snapshots/domain_{$dom->id}_test.webp";
                    $dom->update(['screenshot_url' => $fakeUrl]);
                    return $fakeUrl;
                });
        });

        ProcessCrawlResultJob::dispatchSync($job->id, [
            'domain_status' => [
                'is_accessible' => true,
                'http_status' => 200,
                'screenshot' => 'data:image/webp;base64,UklGRiQAAABXRUJQVlA4IBgAAAAwAQCdASoBAAEAAQAcJaQAA3AA/v3AgAA=',
            ],
            'company' => ['name' => 'DigitilizeWeb'],
        ]);

        $domain->refresh();

        $this->assertNotNull($domain->screenshot_url);
        $this->assertStringContainsString('b2b-company-logos.s3.us-east-1.amazonaws.com/snapshots/', $domain->screenshot_url);
    }
}
