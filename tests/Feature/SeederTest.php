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
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_populates_all_core_entities(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThanOrEqual(4, DataSource::count());
        $this->assertGreaterThanOrEqual(6, Technology::count());
        $this->assertGreaterThanOrEqual(20, Domain::count());
        $this->assertGreaterThanOrEqual(20, Company::count());
        $this->assertGreaterThanOrEqual(20, Contact::count());
        $this->assertGreaterThanOrEqual(20, Email::count());
        $this->assertGreaterThanOrEqual(20, Phone::count());
        $this->assertGreaterThanOrEqual(20, SocialProfile::count());
        $this->assertGreaterThanOrEqual(20, Page::count());
        $this->assertGreaterThanOrEqual(20, CrawlJob::count());
    }
}
