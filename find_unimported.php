<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Domain\DataProcessing\DomainNormalizationService;
use Illuminate\Support\Facades\DB;

$normalizer = app(DomainNormalizationService::class);
$filePath = '5M_domains_clean.csv';

if (!file_exists($filePath)) {
    echo "File not found: {$filePath}\n";
    exit(1);
}

$handle = fopen($filePath, 'r');
$line = 0;
$checked = 0;
$foundNew = 0;

echo "Scanning {$filePath} to find first unimported domains...\n";

while (($data = fgetcsv($handle, 1000, ',')) !== false) {
    $line++;
    $raw = trim($data[0] ?? '');
    if (empty($raw) || strtolower($raw) === 'domain') continue;

    $norm = $normalizer->normalize($raw);
    if (empty($norm['normalized_domain'])) continue;

    $checked++;
    $exists = DB::table('domains')->where('normalized_domain', $norm['normalized_domain'])->exists();

    if (!$exists) {
        $foundNew++;
        echo "Found NEW unimported domain at CSV line #{$line}: '{$norm['normalized_domain']}'\n";
        if ($foundNew >= 5) {
            break;
        }
    }

    if ($checked % 50000 === 0) {
        echo "Checked {$checked} CSV lines (current line #{$line})...\n";
    }
}

fclose($handle);
echo "Scan completed.\n";
