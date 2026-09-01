<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Services\CompanyLogoStorageService;

$company = Company::find(58); // digitilizeweb.com
$logoUrl = 'https://www.digitilizeweb.com/wp-content/w3-webp/uploads/2023/11/DigitilizeWeblogo.pngw3.webp';

$logoStorage = app(CompanyLogoStorageService::class);
$savedUrl = $logoStorage->storeLogo($company, $logoUrl);

echo "Saved Logo URL: " . ($company->fresh()->logo_url ?? 'NULL') . "\n";
