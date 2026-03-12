<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'loyalty_account_id',
        'checkin_id',
        'points_change',
        'reason',
    ];

    public function loyaltyAccount()
    {
        return $this->belongsTo(LoyaltyAccount::class);
    }

    public function checkin()
    {
        return $this->belongsTo(Checkin::class);
    }
}

