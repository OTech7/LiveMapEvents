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
        'is_online_event',
        'online_event_url',
        'rsvp_limit',
        'guest_limit',
        'publish_status',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_free' => 'boolean',
        'is_online_event' => 'boolean',
        'rsvp_limit' => 'integer',
        'guest_limit' => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function venue(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function pin(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Pin::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Only return published events (used by discovery endpoints).
     */
    public function scopePublished($query)
    {
        return $query->where('publish_status', 'published');
    }

    /**
     * Upcoming events — starts_at is in the future.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('starts_at', '>=', now());
    }
}
