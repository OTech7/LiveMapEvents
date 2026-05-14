<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'venue_id',
        'title',
        'description',
        'discount_type',
        'discount_value',
        'recurrence_type',
        'days_of_week',
        'start_time',
        'end_time',
        'valid_from',
        'valid_to',
        'max_total_redemptions',
        'max_per_user_redemptions',
        'terms',
        'is_active',
        'manually_deactivated',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'is_active' => 'boolean',
        'manually_deactivated' => 'boolean',
        'discount_value' => 'decimal:2',
        'max_total_redemptions' => 'integer',
        'max_per_user_redemptions' => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function claims()
    {
        return $this->hasMany(PromotionClaim::class);
    }

    // ─── Status helpers ───────────────────────────────────────────────────────

    /**
     * Is this promotion running right now (within its time window today)?
     */
    public function isActiveNow(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        $todayDate = $now->toDateString();

        if ($todayDate < $this->valid_from->toDateString()) {
            return false;
        }

        if ($this->valid_to && $todayDate > $this->valid_to->toDateString()) {
            return false;
        }

        if ($this->recurrence_type === 'recurring') {
            // dayOfWeekIso: 1=Monday … 7=Sunday
            if (!in_array($now->dayOfWeekIso, $this->days_of_week ?? [])) {
                return false;
            }
        }

        $currentTime = $now->format('H:i:s');

        return $currentTime >= $this->start_time && $currentTime <= $this->end_time;
    }

    /**
     * Is the promotion valid today but hasn't started yet?
     */
    public function isUpcomingToday(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        $todayDate = $now->toDateString();

        if ($todayDate < $this->valid_from->toDateString()) {
            return false;
        }

        if ($this->valid_to && $todayDate > $this->valid_to->toDateString()) {
            return false;
        }

        if ($this->recurrence_type === 'recurring') {
            if (!in_array($now->dayOfWeekIso, $this->days_of_week ?? [])) {
                return false;
            }
        }

        return $now->format('H:i:s') < $this->start_time;
    }

    /**
     * When should a newly claimed voucher expire?
     * Earlier of: (now + 2 hours) OR (today's promo end_time).
     */
    public function calculateExpiresAt(): Carbon
    {
        $promoEndToday = Carbon::today()->setTimeFromTimeString($this->end_time);
        $twoHoursFromNow = now()->addHours(2);

        return $promoEndToday->lt($twoHoursFromNow) ? $promoEndToday : $twoHoursFromNow;
    }

    /**
     * Are there still total redemption slots left?
     */
    public function hasAvailableSlots(): bool
    {
        if ($this->max_total_redemptions === null) {
            return true;
        }

        $redeemed = $this->claims()->where('status', 'redeemed')->count();

        return $redeemed < $this->max_total_redemptions;
    }
}
