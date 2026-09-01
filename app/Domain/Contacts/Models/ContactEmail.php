<?php

namespace App\Domain\Contacts\Models;

use App\Domain\Emails\Models\Email;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactEmail extends Model
{
    use HasFactory;

    protected $table = 'contact_emails';

    protected $fillable = [
        'contact_id',
        'email_id',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class);
    }
}
