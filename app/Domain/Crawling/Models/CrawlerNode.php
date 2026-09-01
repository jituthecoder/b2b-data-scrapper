<?php

namespace App\Domain\Crawling\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrawlerNode extends Model
{
    use HasFactory;

    protected $fillable = [
        'crawler_id',
        'api_key_hash',
        'hostname',
        'version',
        'worker_count',
        'capabilities',
        'status',
        'last_heartbeat_at',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'last_heartbeat_at' => 'datetime',
    ];

    public function getIsOnlineAttribute(): bool
    {
        return $this->status === 'active' 
            && $this->last_heartbeat_at 
            && $this->last_heartbeat_at->gte(now()->subMinutes(2));
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_online ? 'Active' : 'Stopped';
    }
}
