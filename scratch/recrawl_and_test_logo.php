<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Services\CompanyLogoStorageService;
use App\Domain\Domains\Models\Domain;
use Illuminate\Support\Facades\Http;

$domain = Domain::where('normalized_domain', 'digitilizeweb.com')->first();
if (!$domain) {
    echo "Domain digitilizeweb.com not found.\n";
    exit;
}

$company = $domain->companies()->first();
if (!$company) {
    echo "No company attached to digitilizeweb.com.\n";
    exit;
}

echo "Found company: {$company->name} (ID: {$company->id})\n";

// Let's fetch digitilizeweb.com homepage HTML to extract og:image or logo
$res = Http::get('https://digitilizeweb.com');
$html = $res->body();

// Extract og:image or favicon
preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m);
$logoUrl = $m[1] ?? 'https://www.digitilizeweb.com/wp-content/uploads/2023/06/digitilizeweb-logo.png';

echo "Extracted logo URL: {$logoUrl}\n";

$logoStorage = app(CompanyLogoStorageService::class);
$savedUrl = $logoStorage->storeLogo($company, $logoUrl);

echo "Saved Logo URL: " . ($company->fresh()->logo_url ?? 'NULL') . "\n";
