<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domain\Pages\Models\Page;

$pages = Page::all();
foreach ($pages as $p) {
    // If page was already deleted in a previous iteration
    if (!Page::where('id', $p->id)->exists()) continue;

    $cleanUrl = rtrim(strtok($p->url, '#'), '/');
    $norm = strtolower(preg_replace('~^https?://(www\.)?~i', '', $cleanUrl));

    $duplicate = Page::where('domain_id', $p->domain_id)
        ->where('id', '!=', $p->id)
        ->where(function ($q) use ($norm) {
            $q->where('normalized_url', $norm)
              ->orWhere('normalized_url', $norm . '/');
        })
        ->first();

    if ($duplicate) {
        $p->delete();
        echo "Deleted duplicate page ID {$p->id}: {$p->url}\n";
    } else {
        $p->update([
            'normalized_url' => $norm,
            'url' => $cleanUrl,
        ]);
        echo "Normalized page ID {$p->id}: {$cleanUrl}\n";
    }
}

echo "Page deduplication complete!\n";
