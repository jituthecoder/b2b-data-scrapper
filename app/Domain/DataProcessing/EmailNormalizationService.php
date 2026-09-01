<?php

namespace App\Domain\DataProcessing;

class EmailNormalizationService
{
    private const GENERIC_PREFIXES = [
        'info', 'contact', 'sales', 'support', 'help', 'admin', 'administrator',
        'jobs', 'careers', 'billing', 'inquiries', 'general', 'hello', 'team',
    ];

    public function normalize(string $email): ?array
    {
        $clean = trim(mb_strtolower($email));

        if (!filter_var($clean, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $parts = explode('@', $clean);
        if (count($parts) !== 2) {
            return null;
        }

        [$localPart, $domainPart] = $parts;

        $type = in_array($localPart, self::GENERIC_PREFIXES, true) ? 'generic' : 'personal';

        return [
            'email' => $clean,
            'normalized_email' => $clean,
            'domain' => $domainPart,
            'local_part' => $localPart,
            'type' => $type,
        ];
    }
}
