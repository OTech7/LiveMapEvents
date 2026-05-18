<?php

namespace App\Models;

use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pin extends Model
{
    use HasFactory;

    protected $fillable = [
        'venue_id',
        'event_id',
        'type',
        'location',
        'has_promotion',
        'label',
    ];

    protected $casts = [
        'has_promotion' => 'boolean',
        'location' => Point::class,
    ];

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}

