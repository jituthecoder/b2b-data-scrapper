<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$s3Url = 'https://b2b-company-logos.s3.us-east-1.amazonaws.com/logos/company_1_9TOc7L1E.webp';
$res = Http::get($s3Url);

echo "HTTP Status Code: " . $res->status() . "\n";
echo "Content Type: " . $res->header('Content-Type') . "\n";
echo "Content Length: " . strlen($res->body()) . " bytes\n";
