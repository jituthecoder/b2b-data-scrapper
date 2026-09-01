<?php

namespace App\Domain\Phones\Models;

use App\Domain\Contacts\Models\Contact;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Phone extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_number',
        'normalized_phone',
        'country_code',
        'type',
        'confidence_score',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:2',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\PhoneFactory::new();
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_phones')
            ->withTimestamps();
    }
}
