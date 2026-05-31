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

    // ─── Computed attributes ──────────────────────────────────────────────────

    /**
     * True when the event is published AND currently in progress
     * (starts_at has passed, ends_at has not yet passed).
     *
     * This is a pure computed value — no DB column.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->publish_status === 'published'
            && $this->starts_at !== null
            && $this->ends_at !== null
            && $this->starts_at->lte(now())
            && $this->ends_at->gte(now());
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

    /**
     * Currently active events — published, already started, not yet ended.
     */
    public function scopeActive($query)
    {
        return $query->where('publish_status', 'published')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }
}
