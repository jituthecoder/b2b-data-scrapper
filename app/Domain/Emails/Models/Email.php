<?php

namespace App\Domain\Emails\Models;

use App\Domain\Contacts\Models\Contact;
use App\Domain\Domains\Models\Domain;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Email extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'email',
        'normalized_email',
        'domain_id',
        'type',
        'verification_status',
        'confidence_score',
        'first_discovered_at',
        'last_checked_at',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:2',
        'first_discovered_at' => 'datetime',
        'last_checked_at' => 'datetime',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\EmailFactory::new();
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_emails')
            ->withPivot('is_primary')
            ->withTimestamps();
    }
}
