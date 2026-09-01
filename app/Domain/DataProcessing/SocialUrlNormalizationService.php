<?php

namespace App\Domain\DataProcessing;

class SocialUrlNormalizationService
{
    private const PLATFORMS = [
        'linkedin.com' => 'linkedin',
        'twitter.com' => 'twitter',
        'x.com' => 'twitter',
        'facebook.com' => 'facebook',
        'instagram.com' => 'instagram',
        'github.com' => 'github',
        'youtube.com' => 'youtube',
    ];

    public function normalize(string $url): ?array
    {
        $input = trim($url);
        if (!preg_match('~^https?://~i', $input)) {
            $input = 'https://' . $input;
        }

        $parsed = parse_url($input);
        if (!isset($parsed['host'])) {
            return null;
        }

        $host = strtolower(preg_replace('/^www\./i', '', $parsed['host']));
        $path = $parsed['path'] ?? '/';
        $path = rtrim($path, '/');

        $platform = null;
        foreach (self::PLATFORMS as $domainPattern => $name) {
            if ($host === $domainPattern || str_ends_with($host, '.' . $domainPattern)) {
                $platform = $name;
                break;
            }
        }

        if (!$platform) {
            return null;
        }

        $normalizedUrl = "https://{$host}{$path}";
        $username = trim(ltrim($path, '/'));

        return [
            'platform' => $platform,
            'profile_url' => $url,
            'normalized_url' => strtolower($normalizedUrl),
            'username_handle' => $username ?: null,
        ];
    }
}
