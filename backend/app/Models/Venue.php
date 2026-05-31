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
        'notes',
        'location',
        'is_verified',
        'frozen_at',
        'freeze_reason',
    ];

    protected $casts = [
        'location' => Point::class,
        'frozen_at' => 'datetime',
    ];

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * A frozen venue is suspended by an admin.
     * New events/promotions cannot be created for it and it is hidden from discovery.
     */
    public function isFrozen(): bool
    {
        return $this->frozen_at !== null;
    }

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

