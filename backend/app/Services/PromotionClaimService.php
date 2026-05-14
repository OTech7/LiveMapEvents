<?php

namespace App\Services;

use App\Enums\PromotionClaimStatus;
use App\Models\Promotion;
use App\Models\PromotionClaim;
use App\Models\User;
use App\Support\VoucherCodeGenerator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PromotionClaimService
{
    public function __construct(protected VoucherCodeGenerator $voucherCodeGenerator)
    {
    }

    /**
     * Claim a promotion for the given user — returns the new PromotionClaim.
     *
     * All checks that involve counting or inserting rows run inside a single
     * transaction with a row-level lock so two simultaneous requests cannot
     * both pass the "already claimed / no slots left" guards.
     */
    public function claim(Promotion $promotion, User $user): PromotionClaim
    {
        // Pre-checks that don't touch mutable state can stay outside the transaction.
        if (!$promotion->is_active) {
            abort(422, __('messages.promotion_not_active'));
        }

        if (!$promotion->isActiveNow() && !$promotion->isUpcomingToday()) {
            abort(422, __('messages.promotion_not_available_today'));
        }

        return DB::transaction(function () use ($promotion, $user) {

            // Re-check slot availability inside the transaction so concurrent
            // requests see an accurate count after the lock is acquired.
            if (!$promotion->hasAvailableSlots()) {
                abort(422, __('messages.promotion_max_redemptions_reached'));
            }

            $userRedemptions = PromotionClaim::where('promotion_id', $promotion->id)
                ->where('user_id', $user->id)
                ->where('status', PromotionClaimStatus::REDEEMED->value)
                ->count();

            if ($userRedemptions >= $promotion->max_per_user_redemptions) {
                abort(422, __('messages.promotion_user_limit_reached'));
            }

            // Lock the rows for this user + promotion so a double-tap or
            // network retry cannot slip through while we are still inserting.
            $existing = PromotionClaim::where('promotion_id', $promotion->id)
                ->where('user_id', $user->id)
                ->where('status', PromotionClaimStatus::CLAIMED->value)
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if ($existing) {
                abort(422, __('messages.promotion_already_claimed'));
            }

            $claim = PromotionClaim::create([
                'promotion_id' => $promotion->id,
                'user_id' => $user->id,
                'voucher_code' => $this->voucherCodeGenerator->generate(),
                'status' => PromotionClaimStatus::CLAIMED->value,
                'claimed_at' => now(),
                'expires_at' => $promotion->calculateExpiresAt(),
            ]);

            Log::info('promotion_claimed', [
                'promotion_id' => $promotion->id,
                'user_id' => $user->id,
                'claim_id' => $claim->id,
                'voucher_code' => $claim->voucher_code,
            ]);

            return $claim;
        });
    }

    /**
     * Redeem a voucher by code — called by the business owner via the scanner.
     */
    public function redeem(string $code, User $owner): PromotionClaim
    {
        return DB::transaction(function () use ($code, $owner) {

            $claim = PromotionClaim::where('voucher_code', strtoupper(trim($code)))
                ->with(['promotion.venue', 'user'])
                ->lockForUpdate()
                ->first();

            if (!$claim) {
                abort(404, __('messages.voucher_invalid'));
            }

            if ($claim->promotion->venue->owner_id !== $owner->id) {
                abort(403, __('messages.voucher_wrong_venue'));
            }

            if ($claim->status === PromotionClaimStatus::REDEEMED->value) {
                abort(422, __('messages.voucher_already_redeemed'));
            }

            if ($claim->isExpired()) {
                $claim->update(['status' => PromotionClaimStatus::EXPIRED->value]);
                abort(422, __('messages.voucher_expired'));
            }

            $claim->update([
                'status' => PromotionClaimStatus::REDEEMED->value,
                'redeemed_at' => now(),
            ]);

            Log::info('voucher_redeemed', [
                'claim_id' => $claim->id,
                'voucher_code' => $claim->voucher_code,
                'owner_id' => $owner->id,
                'user_id' => $claim->user_id,
                'promotion_id' => $claim->promotion_id,
            ]);

            return $claim->fresh(['promotion', 'user']);
        });
    }

    /**
     * Return the authenticated user's voucher wallet, auto-expiring stale claims.
     */
    public function getMyClaims(User $user): LengthAwarePaginator
    {
        // Lazily expire stale vouchers before returning.
        PromotionClaim::where('user_id', $user->id)
            ->where('status', PromotionClaimStatus::CLAIMED->value)
            ->where('expires_at', '<', now())
            ->update(['status' => PromotionClaimStatus::EXPIRED->value]);

        return PromotionClaim::where('user_id', $user->id)
            ->with('promotion.venue:id,name,type,address')
            ->latest('claimed_at')
            ->paginate(20);
    }
}
