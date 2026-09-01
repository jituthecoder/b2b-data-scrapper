<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domain\Domains\Models\Domain;

$domain = Domain::where('normalized_domain', 'digitilizeweb.com')->first();
if ($domain) {
    $finalUrl = $domain->final_url ?: 'https://www.digitilizeweb.com/';
    $scheme = strtolower(parse_url($finalUrl, PHP_URL_SCHEME) ?: 'https');
    $host = parse_url($finalUrl, PHP_URL_HOST);
    $wwwVariant = $host ? str_starts_with(strtolower($host), 'www.') : false;

    $domain->update([
        'scheme' => $scheme,
        'www_variant' => $wwwVariant,
        'final_url' => $finalUrl,
    ]);

    echo "Updated Domain {$domain->domain}:\n";
    echo "Scheme: {$domain->scheme}://\n";
    echo "WWW Variant: " . ($domain->www_variant ? 'Yes' : 'No') . "\n";
    echo "Final URL: {$domain->final_url}\n";
}
