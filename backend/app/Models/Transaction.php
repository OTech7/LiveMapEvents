<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'promotion_id',
        'venue_id',
        'amount',
        'currency',
        'payment_method',
        'status',
        'external_ref',
    ];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }
}

