<?php

namespace App\Domain\Domains\Models;

use App\Domain\Companies\Models\Company;
use App\Domain\Crawling\Models\CrawlJob;
use App\Domain\Emails\Models\Email;
use App\Domain\Pages\Models\Page;
use App\Domain\SocialProfiles\Models\SocialProfile;
use App\Domain\Technologies\Models\Technology;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Domain extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'domain',
        'normalized_domain',
        'scheme',
        'www_variant',
        'tld',
        'status',
        'is_accessible',
        'http_status',
        'final_url',
        'canonical_url',
        'screenshot_url',
        'first_discovered_at',
        'last_crawled_at',
        'next_crawl_at',
        'crawl_status',
        'crawl_attempts',
        'last_crawl_error',
        'priority',
    ];

    protected $casts = [
        'www_variant' => 'boolean',
        'is_accessible' => 'boolean',
        'http_status' => 'integer',
        'first_discovered_at' => 'datetime',
        'last_crawled_at' => 'datetime',
        'next_crawl_at' => 'datetime',
        'crawl_attempts' => 'integer',
        'priority' => 'integer',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\DomainFactory::new();
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_domains')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
    }

    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class, 'domain_technologies')
            ->withPivot(['version', 'detection_source', 'confidence_score', 'first_detected_at', 'last_detected_at'])
            ->withTimestamps();
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function crawlJobs(): HasMany
    {
        return $this->hasMany(CrawlJob::class);
    }

    public function socialProfiles(): MorphMany
    {
        return $this->morphMany(SocialProfile::class, 'entity');
    }
}
