<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domain\Companies\Models\Company;
use Illuminate\Support\Facades\Http;

$company = Company::find(58);
$url = 'https://www.digitilizeweb.com/wp-content/w3-webp/uploads/2023/11/DigitilizeWeblogo.pngw3.webp';

try {
    $res = Http::timeout(10)->withHeaders(['User-Agent' => 'Mozilla/5.0'])->get($url);
    echo "Status: " . $res->status() . "\n";
    echo "Content-Type: " . $res->header('Content-Type') . "\n";
    echo "Body Bytes: " . strlen($res->body()) . "\n";
} catch (\Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
