<?php

namespace App\Models;

use App\Enums\PromotionClaimStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'promotion_id',
        'user_id',
        'voucher_code',
        'status',
        'claimed_at',
        'redeemed_at',
        'expires_at',
    ];

    protected $casts = [
        'claimed_at' => 'datetime',
        'redeemed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->status === PromotionClaimStatus::EXPIRED->value
            || now()->gt($this->expires_at);
    }
}
