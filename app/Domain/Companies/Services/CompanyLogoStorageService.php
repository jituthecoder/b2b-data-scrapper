<?php

namespace App\Domain\Companies\Services;

use App\Domain\Companies\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompanyLogoStorageService
{
    public function storeLogo(Company $company, string $rawLogoUrl): ?string
    {
        if (empty($rawLogoUrl)) return null;

        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0.0.0'])
                ->get($rawLogoUrl);

            if (!$response->successful()) {
                Log::warning("CompanyLogoStorageService: Failed downloading logo from {$rawLogoUrl} (HTTP {$response->status()})");
                return null;
            }

            $imageBytes = $response->body();
            if (strlen($imageBytes) < 50) { // Reject invalid tiny files
                return null;
            }

            // Determine image extension
            $contentType = $response->header('Content-Type', 'image/png');
            $extension = match (true) {
                str_contains($contentType, 'svg') => 'svg',
                str_contains($contentType, 'webp') => 'webp',
                str_contains($contentType, 'jpeg') || str_contains($contentType, 'jpg') => 'jpg',
                str_contains($contentType, 'gif') => 'gif',
                default => 'png',
            };

            $filename = "logos/company_{$company->id}_" . Str::random(8) . ".{$extension}";

            // Target S3 explicitly if credentials exist, otherwise default disk
            $disk = !empty(config('filesystems.disks.s3.key')) ? 's3' : config('filesystems.default', 'public');
            Storage::disk($disk)->put($filename, $imageBytes);

            $publicUrl = Storage::disk($disk)->url($filename);

            $company->update(['logo_url' => $publicUrl]);

            Log::info("CompanyLogoStorageService: Successfully stored logo on S3/Disk ({$disk}): {$publicUrl}");
            return $publicUrl;
        } catch (\Throwable $e) {
            Log::error("CompanyLogoStorageService Error: " . $e->getMessage());
            return null;
        }
    }
}
