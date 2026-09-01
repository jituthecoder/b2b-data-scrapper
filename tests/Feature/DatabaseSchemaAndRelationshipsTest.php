<?php

namespace Tests\Feature;

use App\Domain\Companies\Models\Company;
use App\Domain\Contacts\Models\Contact;
use App\Domain\Crawling\Models\CrawlJob;
use App\Domain\Domains\Models\Domain;
use App\Domain\Emails\Models\Email;
use App\Domain\Pages\Models\Page;
use App\Domain\Phones\Models\Phone;
use App\Domain\SocialProfiles\Models\SocialProfile;
use App\Domain\Sources\Models\DataSource;
use App\Domain\Technologies\Models\Technology;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSchemaAndRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_company_relationship(): void
    {
        $domain = Domain::factory()->create(['domain' => 'stripe.com', 'normalized_domain' => 'stripe.com']);
        $company = Company::factory()->create(['name' => 'Stripe Inc.', 'normalized_name' => 'stripe']);

        $domain->companies()->attach($company->id, ['is_primary' => true]);

        $this->assertCount(1, $domain->companies);
        $this->assertEquals('Stripe Inc.', $domain->companies->first()->name);
        $this->assertTrue((bool) $domain->companies->first()->pivot->is_primary);
    }

    public function test_contact_email_phone_relationships(): void
    {
        $company = Company::factory()->create();
        $contact = Contact::factory()->create(['company_id' => $company->id]);
        $email = Email::factory()->create();
        $phone = Phone::factory()->create();

        $contact->emails()->attach($email->id, ['is_primary' => true]);
        $contact->phones()->attach($phone->id);

        $this->assertCount(1, $contact->emails);
        $this->assertCount(1, $contact->phones);
        $this->assertEquals($email->email, $contact->emails->first()->email);
    }

    public function test_domain_technology_and_page_relationships(): void
    {
        $domain = Domain::factory()->create();
        $tech = Technology::factory()->create(['name' => 'Vue.js', 'slug' => 'vue-js']);
        $page = Page::factory()->create(['domain_id' => $domain->id]);

        $domain->technologies()->attach($tech->id, ['version' => '3.4']);

        $this->assertCount(1, $domain->technologies);
        $this->assertCount(1, $domain->pages);
        $this->assertEquals('Vue.js', $domain->technologies->first()->name);
        $this->assertEquals('3.4', $domain->technologies->first()->pivot->version);
    }

    public function test_crawl_job_creation_and_uuids(): void
    {
        $domain = Domain::factory()->create();
        $crawlJob = CrawlJob::factory()->create(['domain_id' => $domain->id]);

        $this->assertNotNull($crawlJob->id);
        $this->assertIsString($crawlJob->id);
        $this->assertEquals($domain->id, $crawlJob->domain->id);
    }

    public function test_social_profile_polymorphic_relation(): void
    {
        $company = Company::factory()->create();
        $social = SocialProfile::factory()->create([
            'entity_type' => Company::class,
            'entity_id' => $company->id,
        ]);

        $this->assertInstanceOf(Company::class, $social->entity);
        $this->assertEquals($company->id, $social->entity->id);
    }
}
