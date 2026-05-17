<?php

namespace Tests\Unit\Models;

use App\Enums\PromotionClaimStatus;
use App\Models\Promotion;
use App\Models\PromotionClaim;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionClaimTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeClaimForPromotion(array $claimOverrides = []): PromotionClaim
    {
        $owner = User::create(['phone' => '+963911100001']);
        $user = User::create(['phone' => '+963911100002']);
        $venue = Venue::create(['owner_id' => $owner->id, 'name' => 'V', 'type' => 'bar']);
        $promo = Promotion::create([
            'venue_id' => $venue->id,
            'title' => 'Promo',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'recurrence_type' => 'one_time',
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'valid_from' => now()->toDateString(),
            'is_active' => true,
        ]);

        return PromotionClaim::create(array_merge([
            'promotion_id' => $promo->id,
            'user_id' => $user->id,
            'voucher_code' => 'TESTCODE',
            'status' => PromotionClaimStatus::CLAIMED->value,
            'claimed_at' => now(),
            'expires_at' => now()->addHour(),
        ], $claimOverrides));
    }

    // ─── isExpired() ──────────────────────────────────────────────────────────

    public function test_is_expired_returns_true_when_status_is_expired(): void
    {
        $claim = $this->makeClaimForPromotion([
            'status' => PromotionClaimStatus::EXPIRED->value,
            'expires_at' => now()->addHour(), // time is fine, but status says expired
        ]);

        $this->assertTrue($claim->isExpired());
    }

    public function test_is_expired_returns_true_when_expires_at_is_in_the_past(): void
    {
        $claim = $this->makeClaimForPromotion([
            'status' => PromotionClaimStatus::CLAIMED->value,
            'expires_at' => now()->subMinute(),
        ]);

        $this->assertTrue($claim->isExpired());
    }

    public function test_is_expired_returns_false_when_active_and_not_yet_expired(): void
    {
        $claim = $this->makeClaimForPromotion([
            'status' => PromotionClaimStatus::CLAIMED->value,
            'expires_at' => now()->addHour(),
        ]);

        $this->assertFalse($claim->isExpired());
    }

    public function test_is_expired_returns_false_for_redeemed_claim_with_future_expires_at(): void
    {
        // Redeemed status is not expired by definition (status check uses OR logic)
        $claim = $this->makeClaimForPromotion([
            'voucher_code' => 'TESTCOD2',
            'status' => PromotionClaimStatus::REDEEMED->value,
            'expires_at' => now()->addHour(),
        ]);

        // status is 'redeemed' (not 'expired') and time is in future → not expired
        $this->assertFalse($claim->isExpired());
    }
}
