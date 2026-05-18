<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VibeStory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'venue_id',
        'event_id',
        'video_url',
        'expires_at',
        'status',
        'removed_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function remover()
    {
        return $this->belongsTo(User::class, 'removed_by');
    }
}

