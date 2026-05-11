<?php

namespace App\Services;

use App\Models\Promotion;
use App\Models\PromotionClaim;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class PromotionClaimService
{
    /**
     * Claim a promotion for the given user — returns the new PromotionClaim.
     */
    public function claim(Promotion $promotion, User $user): PromotionClaim
    {
        if (!$promotion->is_active) {
            abort(422, __('messages.promotion_not_active'));
        }

        if (!$promotion->isActiveNow() && !$promotion->isUpcomingToday()) {
            abort(422, __('messages.promotion_not_available_today'));
        }

        if (!$promotion->hasAvailableSlots()) {
            abort(422, __('messages.promotion_max_redemptions_reached'));
        }

        $userRedemptions = PromotionClaim::where('promotion_id', $promotion->id)
            ->where('user_id', $user->id)
            ->where('status', 'redeemed')
            ->count();

        if ($userRedemptions >= $promotion->max_per_user_redemptions) {
            abort(422, __('messages.promotion_user_limit_reached'));
        }

        // Don't allow a second live voucher for the same promotion
        $existing = PromotionClaim::where('promotion_id', $promotion->id)
            ->where('user_id', $user->id)
            ->where('status', 'claimed')
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            abort(422, __('messages.promotion_already_claimed'));
        }

        // Generate unique 8-char uppercase voucher code
        do {
            $code = strtoupper(Str::random(8));
        } while (PromotionClaim::where('voucher_code', $code)->exists());

        return PromotionClaim::create([
            'promotion_id' => $promotion->id,
            'user_id' => $user->id,
            'voucher_code' => $code,
            'status' => 'claimed',
            'claimed_at' => now(),
            'expires_at' => $promotion->calculateExpiresAt(),
        ]);
    }

    /**
     * Redeem a voucher by code — called by the business owner via the scanner.
     */
    public function redeem(string $code, User $owner): PromotionClaim
    {
        $claim = PromotionClaim::where('voucher_code', strtoupper(trim($code)))
            ->with(['promotion.venue', 'user'])
            ->first();

        if (!$claim) {
            abort(404, __('messages.voucher_invalid'));
        }

        if ($claim->promotion->venue->owner_id !== $owner->id) {
            abort(403, __('messages.voucher_wrong_venue'));
        }

        if ($claim->status === 'redeemed') {
            abort(422, __('messages.voucher_already_redeemed'));
        }

        if ($claim->isExpired()) {
            $claim->update(['status' => 'expired']);
            abort(422, __('messages.voucher_expired'));
        }

        $claim->update([
            'status' => 'redeemed',
            'redeemed_at' => now(),
        ]);

        return $claim->fresh(['promotion', 'user']);
    }

    /**
     * Return the authenticated user's voucher wallet, auto-expiring stale claims.
     */
    public function getMyClaims(User $user): LengthAwarePaginator
    {
        // Lazily expire stale vouchers before returning
        PromotionClaim::where('user_id', $user->id)
            ->where('status', 'claimed')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        return PromotionClaim::where('user_id', $user->id)
            ->with('promotion.venue:id,name,type,address')
            ->latest('claimed_at')
            ->paginate(20);
    }
}
