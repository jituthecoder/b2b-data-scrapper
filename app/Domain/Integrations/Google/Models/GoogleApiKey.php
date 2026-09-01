<?php

namespace App\Domain\Integrations\Google\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoogleApiKey extends Model
{
    use HasFactory;

    protected $table = 'google_api_keys';

    protected $fillable = [
        'api_key',
        'cx',
        'requests_today',
        'daily_limit',
        'is_active',
        'is_exhausted',
        'last_used_at',
    ];

    protected $casts = [
        'requests_today' => 'integer',
        'daily_limit' => 'integer',
        'is_active' => 'boolean',
        'is_exhausted' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('is_exhausted', false)
            ->whereRaw('requests_today < daily_limit');
    }
}
