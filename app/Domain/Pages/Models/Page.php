<?php

namespace App\Domain\Pages\Models;

use App\Domain\Domains\Models\Domain;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'url',
        'normalized_url',
        'page_type',
        'http_status',
        'title',
        'html_snapshot_s3_path',
        'content_metadata',
        'crawled_at',
    ];

    protected $casts = [
        'http_status' => 'integer',
        'content_metadata' => 'array',
        'crawled_at' => 'datetime',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\PageFactory::new();
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
