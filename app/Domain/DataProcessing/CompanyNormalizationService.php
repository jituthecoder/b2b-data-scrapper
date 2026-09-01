<?php

namespace App\Domain\DataProcessing;

class CompanyNormalizationService
{
    private const LEGAL_SUFFIXES = [
        '/\binc\.?\b/i',
        '/\bllc\.?\b/i',
        '/\bltd\.?\b/i',
        '/\bcorp(oration)?\.?\b/i',
        '/\bco(mpany)?\.?\b/i',
        '/\bgroup\b/i',
        '/\bholdings\b/i',
        '/\bplc\b/i',
        '/\bgmbh\b/i',
    ];

    public function normalize(string $name): array
    {
        $raw = trim($name);
        $clean = mb_strtolower($raw);

        foreach (self::LEGAL_SUFFIXES as $pattern) {
            $clean = preg_replace($pattern, '', $clean);
        }

        $clean = preg_replace('/[^\w\s]/u', '', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);
        $normalizedName = trim($clean);

        return [
            'name' => $raw,
            'normalized_name' => $normalizedName ?: mb_strtolower($raw),
        ];
    }
}
