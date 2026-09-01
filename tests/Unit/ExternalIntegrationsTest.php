<?php

namespace Tests\Unit;

use App\Domain\Integrations\Email\EmailVerificationService;
use App\Domain\Integrations\PageSpeed\GooglePageSpeedInsightsService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExternalIntegrationsTest extends TestCase
{
    public function test_google_pagespeed_insights_service(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'lighthouseResult' => [
                    'categories' => [
                        'performance' => ['score' => 0.92],
                        'accessibility' => ['score' => 0.98],
                        'best-practices' => ['score' => 1.00],
                        'seo' => ['score' => 0.95],
                    ],
                    'audits' => [
                        'largest-contentful-paint' => ['numericValue' => 1200],
                        'cumulative-layout-shift' => ['numericValue' => 0.02],
                    ],
                ]
            ], 200)
        ]);

        $service = new GooglePageSpeedInsightsService('test-key');
        $result = $service->analyze('https://example.com');

        $this->assertTrue($result['success']);
        $this->assertEquals(92, $result['scores']['performance']);
        $this->assertEquals(98, $result['scores']['accessibility']);
        $this->assertEquals(1200, $result['core_web_vitals']['lcp_ms']);
    }

    public function test_email_verification_service(): void
    {
        $service = new EmailVerificationService();

        // Valid deliverable
        $valid = $service->verify('john@stripe.com');
        $this->assertEquals('deliverable', $valid['status']);
        $this->assertTrue($valid['is_deliverable']);

        // Disposable email
        $disposable = $service->verify('user@mailinator.com');
        $this->assertEquals('disposable', $disposable['status']);
        $this->assertFalse($disposable['is_deliverable']);

        // Invalid syntax
        $invalid = $service->verify('not-an-email');
        $this->assertEquals('invalid_syntax', $invalid['status']);
        $this->assertFalse($invalid['is_deliverable']);
    }
}
