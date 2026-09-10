<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$maxId = DB::table('domains')->max('id');
$lastDomain = DB::table('domains')->orderBy('id', 'desc')->first(['id', 'domain', 'normalized_domain', 'created_at']);
$totalCountEst = DB::selectOne("SELECT reltuples::bigint AS count FROM pg_class WHERE relname = 'domains'");

echo json_encode([
    'max_id' => $maxId,
    'last_domain' => $lastDomain,
    'reltuples_est' => $totalCountEst ? $totalCountEst->count : null,
], JSON_PRETTY_PRINT) . PHP_EOL;
