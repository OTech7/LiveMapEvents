<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'venue_id',
        'title',
        'description',
        'category',
        'starts_at',
        'ends_at',
        'is_free',
        'image_url',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_free' => 'boolean',
    ];

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function pin()
    {
        return $this->hasOne(Pin::class);
    }
}

