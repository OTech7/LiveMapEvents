<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interest extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Bind {interest} route parameters by slug instead of id.
     * Lets us use clean URLs like /profile/interests/music.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_interests');
    }
}
