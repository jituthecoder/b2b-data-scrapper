<?php

namespace App\Domain\Domains\Services;

use App\Domain\Domains\Models\Domain;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WebsiteScreenshotStorageService
{
    public function storeScreenshot(Domain $domain, string $screenshotData): ?string
    {
        if (empty($screenshotData)) return null;

        try {
            $imageBytes = null;

            // Handle base64 encoded screenshot or HTTP image URL
            if (str_starts_with($screenshotData, 'data:image')) {
                $base64Str = explode(',', $screenshotData, 2)[1] ?? '';
                $imageBytes = base64_decode($base64Str);
            } elseif (str_starts_with($screenshotData, 'http://') || str_starts_with($screenshotData, 'https://')) {
                $res = Http::timeout(10)->get($screenshotData);
                if ($res->successful()) {
                    $imageBytes = $res->body();
                }
            } else {
                $imageBytes = base64_decode($screenshotData, true) ?: $screenshotData;
            }

            if (!$imageBytes || strlen($imageBytes) < 100) {
                return null;
            }

            $filename = "snapshots/domain_{$domain->id}_" . Str::random(8) . ".webp";

            // Target S3 explicitly if credentials exist, otherwise default disk
            $disk = !empty(config('filesystems.disks.s3.key')) ? 's3' : config('filesystems.default', 'public');
            Storage::disk($disk)->put($filename, $imageBytes);

            $publicUrl = Storage::disk($disk)->url($filename);

            $domain->update(['screenshot_url' => $publicUrl]);

            Log::info("WebsiteScreenshotStorageService: Successfully uploaded website screenshot for {$domain->domain} to S3/Disk ({$disk}): {$publicUrl}");
            return $publicUrl;
        } catch (\Throwable $e) {
            Log::error("WebsiteScreenshotStorageService Error: " . $e->getMessage());
            return null;
        }
    }
}
