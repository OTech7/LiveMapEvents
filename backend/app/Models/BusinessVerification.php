<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'venue_id',
        'registration_doc_url',
        'owner_id_doc_url',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

