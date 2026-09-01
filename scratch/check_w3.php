<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domain\Domains\Models\Domain;

$d = Domain::where('normalized_domain', 'w3speedup.com')->first();
if (!$d) {
    echo "Domain w3speedup.com not found in database.\n";
    exit;
}

echo "Domain ID: {$d->id}, Domain: {$d->domain}, Crawl Status: {$d->crawl_status}\n";
echo "Social Profiles Count: " . $d->socialProfiles->count() . "\n";

foreach ($d->socialProfiles as $sp) {
    echo " - Platform: {$sp->platform}, URL: {$sp->profile_url}\n";
}

echo "\nCrawl Jobs History:\n";
foreach ($d->crawlJobs as $j) {
    echo " - Job ID: {$j->id}, Type: {$j->job_type}, Status: {$j->status}, Priority: {$j->priority}, Created: {$j->created_at}\n";
}
