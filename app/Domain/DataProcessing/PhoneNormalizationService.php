<?php

namespace App\Domain\DataProcessing;

class PhoneNormalizationService
{
    public function normalize(string $phone): array
    {
        $raw = trim($phone);
        // Strip non-digit characters except leading +
        $hasPlus = str_starts_with($raw, '+');
        $digits = preg_replace('/[^\d]/', '', $raw);

        $normalized = $hasPlus ? '+' . $digits : $digits;

        return [
            'phone_number' => $raw,
            'normalized_phone' => $normalized,
        ];
    }
}
