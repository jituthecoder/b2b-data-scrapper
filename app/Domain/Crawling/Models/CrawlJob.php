<?php

namespace App\Domain\Crawling\Models;

use App\Domain\Domains\Models\Domain;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrawlJob extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'domain_id',
        'job_type',
        'priority',
        'status',
        'crawler_id',
        'claimed_at',
        'lease_expires_at',
        'completed_at',
        'failed_at',
        'attempt_count',
        'max_attempts',
        'last_error',
        'payload',
        'raw_result_s3_path',
        'idempotency_key',
    ];

    protected $casts = [
        'priority' => 'integer',
        'claimed_at' => 'datetime',
        'lease_expires_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'attempt_count' => 'integer',
        'max_attempts' => 'integer',
        'payload' => 'array',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\CrawlJobFactory::new();
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(CrawlAttempt::class);
    }
}
