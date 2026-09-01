<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::get('https://api.microlink.io/?url=https://digitilizeweb.com&screenshot=true');
echo "Status: " . $res->status() . "\n";
$data = $res->json();
echo "Screenshot URL: " . ($data['data']['screenshot']['url'] ?? 'NULL') . "\n";
