<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Services\CompanyLogoStorageService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

echo "=== AWS S3 Live Upload Diagnostic ===\n";
echo "Disk Configured: " . config('filesystems.default') . "\n";
echo "S3 Bucket: " . config('filesystems.disks.s3.bucket') . "\n";
echo "S3 Region: " . config('filesystems.disks.s3.region') . "\n";

$company = Company::where('name', 'LIKE', '%DigitilizeWeb%')->first();
if (!$company) {
    $company = Company::first();
}

echo "Target Company ID: {$company->id} ({$company->name})\n";

$logoUrl = 'https://www.digitilizeweb.com/wp-content/w3-webp/uploads/2023/11/DigitilizeWeblogo.pngw3.webp';

// Force disk to S3 to test real upload
config(['filesystems.default' => 's3']);

$logoStorage = app(CompanyLogoStorageService::class);
$publicUrl = $logoStorage->storeLogo($company, $logoUrl);

echo "Upload Result Public S3 URL: " . ($publicUrl ?? 'FAILED') . "\n";

if ($publicUrl) {
    // Verify HTTP 200 GET on public S3 URL
    $res = Http::get($publicUrl);
    echo "Public S3 Reachability Check: HTTP " . $res->status() . " (Content-Length: " . strlen($res->body()) . " bytes)\n";
}
