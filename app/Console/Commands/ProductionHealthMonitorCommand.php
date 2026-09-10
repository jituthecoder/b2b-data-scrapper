<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductionHealthMonitorCommand extends Command
{
    protected $signature = 'monitor:production';
    protected $description = 'Automated 24/7 production health monitor and auto-healing engine';

    public function handle(): int
    {
        $timestamp = now()->toDateTimeString();
        $logFile = storage_path('logs/maintenance.log');

        $issues = [];
        $actions = [];

        // 1. Check Redis Queue Health
        try {
            $r = Cache::store('redis');
            $r->set('health_check_ping', 'ok', 10);
            $actions[] = "[Queue/Redis] Upstash Redis connected cleanly.";
        } catch (\Throwable $e) {
            $issues[] = "[Queue Error] " . $e->getMessage();
            try {
                Artisan::call('config:clear');
                Artisan::call('config:cache');
                Artisan::call('queue:restart');
                $actions[] = "[Auto-Heal] Cleared config cache and restarted queue workers.";
            } catch (\Throwable $ex) {
                $issues[] = "[Auto-Heal Error] Failed restarting queue: " . $ex->getMessage();
            }
        }

        // 2. Check PostgreSQL Database Connectivity
        try {
            $result = DB::selectOne("SELECT reltuples::bigint AS count FROM pg_class WHERE relname = 'domains'");
            $count = $result ? $result->count : 0;
            $actions[] = "[Database] PostgreSQL connected. Approx 5M+ domains count: {$count}";
        } catch (\Throwable $e) {
            $issues[] = "[Database Error] PostgreSQL connection timed out: " . $e->getMessage();
        }

        // 3. Storage & Inode Health Pruning (Clean up raw crawl files, expired sessions, and old logs)
        try {
            $crawlsDir = storage_path('app/private/crawls');
            if (file_exists($crawlsDir)) {
                $files = glob("{$crawlsDir}/*.json");
                $deletedCount = 0;
                foreach ($files as $f) {
                    if (is_file($f)) {
                        @unlink($f);
                        $deletedCount++;
                    }
                }
                if ($deletedCount > 0) {
                    $actions[] = "[Inode Pruner] Cleaned up {$deletedCount} raw JSON crawl files from storage.";
                }
            }
        } catch (\Throwable $e) {
            $issues[] = "[Inode Pruner Warning] " . $e->getMessage();
        }
        $logEntry = "=== Production Monitoring Run [{$timestamp}] ===" . PHP_EOL;
        foreach ($actions as $act) {
            $logEntry .= "INFO: {$act}" . PHP_EOL;
        }
        if (!empty($issues)) {
            foreach ($issues as $iss) {
                $logEntry .= "WARNING/FIX: {$iss}" . PHP_EOL;
            }
        }
        $logEntry .= "==========================================" . PHP_EOL . PHP_EOL;

        file_put_contents($logFile, $logEntry, FILE_APPEND);

        $this->info("Production monitoring check completed. Logged to {$logFile}");
        return Command::SUCCESS;
    }
}
