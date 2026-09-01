<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])->get('https://digitilizeweb.com');
$html = $res->body();

preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches);
foreach ($matches[1] as $img) {
    if (str_contains(strtolower($img), 'logo') || str_contains(strtolower($img), 'brand')) {
        echo "Found logo candidate: {$img}\n";
    }
}
