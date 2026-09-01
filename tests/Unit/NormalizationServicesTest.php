<?php

namespace Tests\Unit;

use App\Domain\DataProcessing\CompanyNormalizationService;
use App\Domain\DataProcessing\DomainNormalizationService;
use App\Domain\DataProcessing\EmailNormalizationService;
use App\Domain\DataProcessing\PhoneNormalizationService;
use App\Domain\DataProcessing\SocialUrlNormalizationService;
use PHPUnit\Framework\TestCase;

class NormalizationServicesTest extends TestCase
{
    public function test_domain_normalization(): void
    {
        $service = new DomainNormalizationService();
        $result = $service->normalize('HTTPS://WWW.Example-Company.COM/about?query=1');

        $this->assertEquals('www.example-company.com', $result['domain']);
        $this->assertEquals('example-company.com', $result['normalized_domain']);
        $this->assertEquals('com', $result['tld']);
        $this->assertTrue($result['www_variant']);
        $this->assertEquals('https', $result['scheme']);
    }

    public function test_email_normalization_generic(): void
    {
        $service = new EmailNormalizationService();
        $result = $service->normalize('  INFO@AcmeCorp.com ');

        $this->assertNotNull($result);
        $this->assertEquals('info@acmecorp.com', $result['email']);
        $this->assertEquals('generic', $result['type']);
        $this->assertEquals('acmecorp.com', $result['domain']);
    }

    public function test_email_normalization_personal(): void
    {
        $service = new EmailNormalizationService();
        $result = $service->normalize('John.Doe@AcmeCorp.com');

        $this->assertNotNull($result);
        $this->assertEquals('john.doe@acmecorp.com', $result['email']);
        $this->assertEquals('personal', $result['type']);
    }

    public function test_phone_normalization(): void
    {
        $service = new PhoneNormalizationService();
        $result = $service->normalize('+1 (555) 019-2834');

        $this->assertEquals('+15550192834', $result['normalized_phone']);
    }

    public function test_social_url_normalization(): void
    {
        $service = new SocialUrlNormalizationService();
        $result = $service->normalize('https://www.linkedin.com/in/john-doe-123/?trackingId=abc');

        $this->assertNotNull($result);
        $this->assertEquals('linkedin', $result['platform']);
        $this->assertEquals('https://linkedin.com/in/john-doe-123', $result['normalized_url']);
        $this->assertEquals('in/john-doe-123', $result['username_handle']);
    }

    public function test_company_normalization(): void
    {
        $service = new CompanyNormalizationService();
        $result = $service->normalize('Acme Technologies, Inc.');

        $this->assertEquals('Acme Technologies, Inc.', $result['name']);
        $this->assertEquals('acme technologies', $result['normalized_name']);
    }
}
