<?php

namespace Database\Seeders;

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
use Illuminate\Database\Seeder;

class DomainPlatformSeeder extends Seeder
{
    public function run(): void
    {
        // Seed default Data Sources
        $sources = [
            ['name' => 'website_crawler', 'type' => 'crawler', 'description' => 'Stateless Python/Node.js web crawlers'],
            ['name' => 'contact_page_extractor', 'type' => 'crawler', 'description' => 'Specialized contact page parser'],
            ['name' => 'external_enrichment_api', 'type' => 'api', 'description' => 'External enrichment provider APIs'],
            ['name' => 'csv_bulk_import', 'type' => 'file_import', 'description' => 'Initial 5M domain CSV dataset import'],
        ];

        foreach ($sources as $src) {
            DataSource::firstOrCreate(['name' => $src['name']], $src);
        }

        // Seed common technologies
        $techs = [
            ['name' => 'WordPress', 'slug' => 'wordpress', 'category' => 'CMS'],
            ['name' => 'Shopify', 'slug' => 'shopify', 'category' => 'E-commerce'],
            ['name' => 'Laravel', 'slug' => 'laravel', 'category' => 'Framework'],
            ['name' => 'React', 'slug' => 'react', 'category' => 'UI Framework'],
            ['name' => 'Google Analytics', 'slug' => 'google-analytics', 'category' => 'Analytics'],
            ['name' => 'Cloudflare', 'slug' => 'cloudflare', 'category' => 'CDN/DNS'],
        ];

        $techModels = [];
        foreach ($techs as $tech) {
            $techModels[] = Technology::firstOrCreate(['slug' => $tech['slug']], $tech);
        }

        // Seed 20 domains with attached companies, contacts, emails, phones, pages, and crawl jobs
        Domain::factory(20)->create()->each(function (Domain $domain) use ($techModels) {
            // Attach company
            $company = Company::factory()->create();
            $domain->companies()->attach($company->id, ['is_primary' => true]);

            // Attach contacts
            $contacts = Contact::factory(2)->create(['company_id' => $company->id]);
            foreach ($contacts as $contact) {
                $email = Email::factory()->create(['domain_id' => $domain->id]);
                $contact->emails()->attach($email->id, ['is_primary' => true]);

                $phone = Phone::factory()->create();
                $contact->phones()->attach($phone->id);
            }

            // Attach social profiles
            SocialProfile::factory()->create([
                'entity_type' => Company::class,
                'entity_id' => $company->id,
            ]);

            // Attach domain technologies
            $domain->technologies()->attach($techModels[0]->id, [
                'version' => '6.4',
                'detection_source' => 'website_crawler',
                'confidence_score' => 1.00,
                'first_detected_at' => now(),
                'last_detected_at' => now(),
            ]);

            // Attach pages
            Page::factory()->create([
                'domain_id' => $domain->id,
                'page_type' => 'homepage',
            ]);

            // Attach crawl jobs
            CrawlJob::factory()->create([
                'domain_id' => $domain->id,
                'job_type' => 'homepage',
                'status' => 'pending',
            ]);
        });
    }
}
