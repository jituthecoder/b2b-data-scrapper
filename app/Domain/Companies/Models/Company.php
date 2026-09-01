<?php

namespace App\Domain\Companies\Models;

use App\Domain\Contacts\Models\Contact;
use App\Domain\Domains\Models\Domain;
use App\Domain\SocialProfiles\Models\SocialProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'normalized_name',
        'logo_url',
        'description',
        'industry',
        'employee_count_range',
        'founded_year',
        'country',
        'state_region',
        'city',
        'address',
        'postal_code',
        'metadata',
        'confidence_score',
    ];

    protected $casts = [
        'founded_year' => 'integer',
        'metadata' => 'array',
        'confidence_score' => 'decimal:2',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\CompanyFactory::new();
    }

    public function domains(): BelongsToMany
    {
        return $this->belongsToMany(Domain::class, 'company_domains')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function socialProfiles(): MorphMany
    {
        return $this->morphMany(SocialProfile::class, 'entity');
    }
}
