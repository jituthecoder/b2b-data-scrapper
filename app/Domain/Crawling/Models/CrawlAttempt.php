<?php

namespace App\Domain\Crawling\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrawlAttempt extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'crawl_job_id',
        'crawler_id',
        'attempt_number',
        'status',
        'duration_ms',
        'response_code',
        'error_message',
        'created_at',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'duration_ms' => 'integer',
        'response_code' => 'integer',
        'created_at' => 'datetime',
    ];

    public function crawlJob(): BelongsTo
    {
        return $this->belongsTo(CrawlJob::class);
    }
}
