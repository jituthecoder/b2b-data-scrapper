<?php

namespace App\Domain\Technologies\Models;

use App\Domain\Domains\Models\Domain;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainTechnology extends Model
{
    use HasFactory;

    protected $table = 'domain_technologies';

    protected $fillable = [
        'domain_id',
        'technology_id',
        'version',
        'detection_source',
        'confidence_score',
        'first_detected_at',
        'last_detected_at',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:2',
        'first_detected_at' => 'datetime',
        'last_detected_at' => 'datetime',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function technology(): BelongsTo
    {
        return $this->belongsTo(Technology::class);
    }
}
