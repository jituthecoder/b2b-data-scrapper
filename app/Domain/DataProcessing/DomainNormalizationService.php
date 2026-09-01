<?php

namespace App\Domain\DataProcessing;

class DomainNormalizationService
{
    public function normalize(string $domainOrUrl): array
    {
        $input = trim(mb_strtolower($domainOrUrl));

        $hasExplicitScheme = (bool) preg_match('~^https?://~i', $input);
        $scheme = 'https'; // Default modern web standard

        if ($hasExplicitScheme) {
            $parsed = parse_url($input);
            $scheme = isset($parsed['scheme']) ? strtolower($parsed['scheme']) : 'https';
            $url = $input;
        } else {
            $url = 'https://' . $input;
        }

        $parsed = parse_url($url);
        $host = $parsed['host'] ?? $input;

        // Convert IDN punycode if applicable
        if (function_exists('idn_to_ascii')) {
            $host = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) ?: $host;
        }

        // Clean domain
        $cleanDomain = preg_replace('/:[0-9]+$/', '', $host);
        $cleanDomain = trim($cleanDomain, '.');

        // Normalized domain (strip leading www.)
        $normalizedDomain = preg_replace('/^www\./i', '', $cleanDomain);

        // Extract TLD
        $parts = explode('.', $normalizedDomain);
        $tld = count($parts) > 1 ? end($parts) : null;

        $wwwVariant = (bool) preg_match('/^www\./i', $cleanDomain);

        return [
            'domain' => $cleanDomain,
            'normalized_domain' => $normalizedDomain,
            'scheme' => $scheme,
            'www_variant' => $wwwVariant,
            'tld' => $tld,
        ];
    }
}
