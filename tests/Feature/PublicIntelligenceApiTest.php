<?php

namespace Tests\Feature;

use App\Domain\Companies\Models\Company;
use App\Domain\Contacts\Models\Contact;
use App\Domain\Domains\Models\Domain;
use App\Domain\Technologies\Models\Technology;
use Database\Seeders\DomainPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicIntelligenceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DomainPlatformSeeder::class);
    }

    public function test_domain_intelligence_lookup(): void
    {
        $domain = Domain::first();
        $this->assertNotNull($domain);

        $response = $this->getJson("/api/v1/domains/{$domain->domain}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'domain',
                    'normalized_domain',
                    'status',
                    'is_accessible',
                    'companies',
                    'technologies',
                ]
            ]);
    }

    public function test_domain_lookup_not_found(): void
    {
        $response = $this->getJson('/api/v1/domains/non-existent-domain-9999.xyz');
        $response->assertStatus(404)->assertJson(['error' => 'Not Found']);
    }

    public function test_company_search(): void
    {
        $company = Company::first();
        $this->assertNotNull($company);

        $response = $this->getJson("/api/v1/companies/search?name={$company->name}");

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_contact_search(): void
    {
        $contact = Contact::first();
        $this->assertNotNull($contact);

        $response = $this->getJson("/api/v1/contacts/search?job_title={$contact->job_title}");

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_technology_lookup(): void
    {
        $tech = Technology::first();
        $this->assertNotNull($tech);

        $response = $this->getJson("/api/v1/technologies/lookup?category={$tech->category}");

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }
}
