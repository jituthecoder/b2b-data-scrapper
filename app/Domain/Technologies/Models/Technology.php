<?php

namespace App\Domain\Technologies\Models;

use App\Domain\Domains\Models\Domain;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Technology extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'icon_url',
        'description',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\TechnologyFactory::new();
    }

    public function domains(): BelongsToMany
    {
        return $this->belongsToMany(Domain::class, 'domain_technologies')
            ->withPivot(['version', 'detection_source', 'confidence_score', 'first_detected_at', 'last_detected_at'])
            ->withTimestamps();
    }
}
