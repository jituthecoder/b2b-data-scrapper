<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domain\Companies\Models\Company;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

$company = Company::find(58);
echo "Initial logo_url: " . ($company->logo_url ?? 'NULL') . "\n";

$filename = "logos/company_{$company->id}_" . Str::random(8) . ".webp";
$disk = config('filesystems.default', 'public');
echo "Disk: {$disk}\n";

$putRes = Storage::disk($disk)->put($filename, 'fake-image-content');
echo "Put Result: " . ($putRes ? 'true' : 'false') . "\n";

$publicUrl = Storage::disk($disk)->url($filename);
echo "Public URL: {$publicUrl}\n";

$compRes = $company->update(['logo_url' => $publicUrl]);
echo "Company Update Result: " . ($compRes ? 'true' : 'false') . "\n";

$fresh = Company::find(58);
echo "Fresh logo_url: " . ($fresh->logo_url ?? 'NULL') . "\n";
