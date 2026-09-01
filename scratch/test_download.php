<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$url = 'https://www.digitilizeweb.com/wp-content/uploads/2023/06/digitilizeweb-logo.png';
$res = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])->get($url);

echo "HTTP Status: " . $res->status() . "\n";
echo "Content-Type: " . $res->header('Content-Type') . "\n";
echo "Body Length: " . strlen($res->body()) . " bytes\n";
