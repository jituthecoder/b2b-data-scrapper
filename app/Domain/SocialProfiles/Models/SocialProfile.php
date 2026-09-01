<?php

namespace App\Domain\SocialProfiles\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SocialProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform',
        'profile_url',
        'normalized_url',
        'username_handle',
        'entity_type',
        'entity_id',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\SocialProfileFactory::new();
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }
}
