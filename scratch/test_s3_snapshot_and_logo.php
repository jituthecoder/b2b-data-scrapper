<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Services\CompanyLogoStorageService;
use App\Domain\Domains\Models\Domain;
use App\Domain\Domains\Services\WebsiteScreenshotStorageService;
use Illuminate\Support\Facades\Http;

echo "=== Live S3 Screenshot & Logo Generator Diagnostic ===\n";

$domain = Domain::where('normalized_domain', 'digitilizeweb.com')->first();
if (!$domain) {
    echo "Domain digitilizeweb.com not found.\n";
    exit;
}

echo "Target Domain ID: {$domain->id} ({$domain->domain})\n";

// 1. Generate & Upload Screenshot to S3
$res = Http::get("https://api.microlink.io/?url=https://{$domain->normalized_domain}&screenshot=true");
if ($res->successful()) {
    $data = $res->json();
    $tempScreenshotUrl = $data['data']['screenshot']['url'] ?? null;
    echo "Temp Microlink Screenshot URL: {$tempScreenshotUrl}\n";

    if ($tempScreenshotUrl) {
        $screenshotService = app(WebsiteScreenshotStorageService::class);
        $s3ScreenshotUrl = $screenshotService->storeScreenshot($domain, $tempScreenshotUrl);
        echo "Uploaded S3 Screenshot URL: {$s3ScreenshotUrl}\n";
    }
}

// 2. Fix Company Logo on S3
$company = $domain->companies()->first();
if ($company) {
    $realLogoUrl = 'https://www.digitilizeweb.com/wp-content/w3-webp/uploads/2023/11/DigitilizeWeblogo.pngw3.webp';
    $logoService = app(CompanyLogoStorageService::class);
    $s3LogoUrl = $logoService->storeLogo($company, $realLogoUrl);
    echo "Uploaded S3 Logo URL: {$s3LogoUrl}\n";
}
