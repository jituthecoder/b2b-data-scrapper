<?php

namespace App\Domain\Domains\Services;

use App\Domain\Domains\Models\Domain;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FaviconStorageService
{
    public function storeFavicon(Domain $domain, string $rawFaviconUrl): ?string
    {
        if (empty($rawFaviconUrl)) return null;

        try {
            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0.0.0'])
                ->get($rawFaviconUrl);

            if (!$response->successful()) {
                Log::warning("FaviconStorageService: Failed downloading favicon from {$rawFaviconUrl} (HTTP {$response->status()})");
                return null;
            }

            $imageBytes = $response->body();
            if (strlen($imageBytes) < 20) { // Reject tiny/corrupt files
                return null;
            }

            // Determine image extension
            $contentType = $response->header('Content-Type', 'image/x-icon');
            $extension = match (true) {
                str_contains($contentType, 'svg') => 'svg',
                str_contains($contentType, 'png') => 'png',
                str_contains($contentType, 'webp') => 'webp',
                str_contains($contentType, 'gif') => 'gif',
                default => 'ico',
            };

            $filename = "favicons/domain_{$domain->id}_" . Str::random(8) . ".{$extension}";

            // Target S3 explicitly if credentials exist, otherwise default disk
            $disk = !empty(config('filesystems.disks.s3.key')) ? 's3' : config('filesystems.default', 'public');
            Storage::disk($disk)->put($filename, $imageBytes);

            $publicUrl = Storage::disk($disk)->url($filename);

            $domain->update(['favicon_url' => $publicUrl]);

            Log::info("FaviconStorageService: Stored favicon for {$domain->domain} on S3/Disk ({$disk}): {$publicUrl}");
            return $publicUrl;
        } catch (\Throwable $e) {
            Log::error("FaviconStorageService Error: " . $e->getMessage());
            return null;
        }
    }
}
