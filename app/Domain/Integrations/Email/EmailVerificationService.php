<?php

namespace App\Domain\Integrations\Email;

class EmailVerificationService
{
    public function verify(string $email): array
    {
        $clean = trim(mb_strtolower($email));

        if (!filter_var($clean, FILTER_VALIDATE_EMAIL)) {
            return [
                'email' => $clean,
                'status' => 'invalid_syntax',
                'is_deliverable' => false,
                'confidence_score' => 0.00,
            ];
        }

        $domain = substr(strrchr($clean, "@"), 1);
        $hasMxRecords = false;

        if (function_exists('checkdnsrr')) {
            $hasMxRecords = checkdnsrr($domain, 'MX');
        } else {
            $hasMxRecords = true;
        }

        $disposableDomains = ['mailinator.com', '10minutemail.com', 'tempmail.com', 'guerrillamail.com'];
        $isDisposable = in_array($domain, $disposableDomains, true);

        if ($isDisposable) {
            $status = 'disposable';
            $isDeliverable = false;
            $confidence = 0.10;
        } elseif (!$hasMxRecords) {
            $status = 'no_mx_records';
            $isDeliverable = false;
            $confidence = 0.20;
        } else {
            $status = 'deliverable';
            $isDeliverable = true;
            $confidence = 0.95;
        }

        return [
            'email' => $clean,
            'domain' => $domain,
            'status' => $status,
            'is_deliverable' => $isDeliverable,
            'has_mx_records' => $hasMxRecords,
            'is_disposable' => $isDisposable,
            'confidence_score' => $confidence,
            'verified_at' => now()->toIso8601String(),
        ];
    }
}
