<?php

namespace App\Domain\Contacts\Models;

use App\Domain\Phones\Models\Phone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactPhone extends Model
{
    use HasFactory;

    protected $table = 'contact_phones';

    protected $fillable = [
        'contact_id',
        'phone_id',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function phone(): BelongsTo
    {
        return $this->belongsTo(Phone::class);
    }
}
