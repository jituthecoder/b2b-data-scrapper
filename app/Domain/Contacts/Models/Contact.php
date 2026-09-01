<?php

namespace App\Domain\Contacts\Models;

use App\Domain\Companies\Models\Company;
use App\Domain\Emails\Models\Email;
use App\Domain\Phones\Models\Phone;
use App\Domain\SocialProfiles\Models\SocialProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'first_name',
        'last_name',
        'full_name',
        'job_title',
        'department',
        'seniority',
        'confidence_score',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:2',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\ContactFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function emails(): BelongsToMany
    {
        return $this->belongsToMany(Email::class, 'contact_emails')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function phones(): BelongsToMany
    {
        return $this->belongsToMany(Phone::class, 'contact_phones')
            ->withTimestamps();
    }

    public function socialProfiles(): MorphMany
    {
        return $this->morphMany(SocialProfile::class, 'entity');
    }
}
