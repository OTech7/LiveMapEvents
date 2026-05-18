<?php

namespace App\Models;

use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'type',
        'address',
        'city',
        'location',
        'is_active',
        'is_verified',
    ];

    protected $casts = [
        'location' => Point::class,
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function pins()
    {
        return $this->hasMany(Pin::class);
    }

    public function promotions()
    {
        return $this->hasMany(Promotion::class);
    }
}

