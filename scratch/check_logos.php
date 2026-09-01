<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domain\Companies\Models\Company;

$companies = Company::all();
echo "TOTAL COMPANIES: " . $companies->count() . "\n";

foreach ($companies as $comp) {
    echo "ID: {$comp->id} | Name: {$comp->name} | Logo URL: " . ($comp->logo_url ?? 'NULL') . "\n";
}
