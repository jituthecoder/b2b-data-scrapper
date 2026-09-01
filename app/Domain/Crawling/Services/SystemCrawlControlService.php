<?php

namespace App\Domain\Crawling\Services;

use Illuminate\Support\Facades\Cache;

class SystemCrawlControlService
{
    private const CACHE_KEY = 'system_crawl_status';

    public function getStatus(): string
    {
        return Cache::get(self::CACHE_KEY, 'active');
    }

    public function setStatus(string $status): string
    {
        $status = strtolower($status);
        if (!in_array($status, ['active', 'paused', 'stopped'], true)) {
            $status = 'active';
        }

        Cache::forever(self::CACHE_KEY, $status);
        return $status;
    }

    public function isActive(): bool
    {
        return $this->getStatus() === 'active';
    }

    public function isPaused(): bool
    {
        return $this->getStatus() === 'paused';
    }

    public function isStopped(): bool
    {
        return $this->getStatus() === 'stopped';
    }
}
