<?php

namespace Tests\Unit\Services;

use App\Enums\PromotionClaimStatus;
use App\Models\Promotion;
use App\Models\PromotionClaim;
use App\Models\User;
use App\Models\Venue;
use App\Services\PromotionClaimService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PromotionClaimServiceTest extends TestCase
{
    use RefreshDatabase;

    private PromotionClaimService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PromotionClaimService::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private static int $userCounter = 0;

    private function makeUser(): User
    {
        return User::create(['phone' => '+9639120' . str_pad(++self::$userCounter, 6, '0', STR_PAD_LEFT)]);
    }

    /**
     * Active promotion valid right now (00:00:00 – 23:59:59, today, one_time).
     */
    private function makeActivePromotion(array $overrides = []): Promotion
    {
        $owner = $this->makeUser();
        $venue = Venue::create(['owner_id' => $owner->id, 'name' => 'V', 'type' => 'bar']);

        return Promotion::create(array_merge([
            'venue_id' => $venue->id,
            'title' => 'Promo',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'recurrence_type' => 'one_time',
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'valid_from' => now()->toDateString(),
            'is_active' => true,
            'max_total_redemptions' => null,
            'max_per_user_redemptions' => 5,
        ], $overrides));
    }

    private function makeClaim(Promotion $promo, User $user, array $overrides = []): PromotionClaim
    {
        static $codeCounter = 0;
        return PromotionClaim::create(array_merge([
            'promotion_id' => $promo->id,
            'user_id' => $user->id,
            'voucher_code' => 'CODE' . str_pad(++$codeCounter, 4, '0', STR_PAD_LEFT),
            'status' => PromotionClaimStatus::CLAIMED->value,
            'claimed_at' => now(),
            'expires_at' => now()->addHour(),
        ], $overrides));
    }

    // ─── claim() ──────────────────────────────────────────────────────────────

    public function test_claim_creates_a_new_promotion_claim(): void
    {
        $promo = $this->makeActivePromotion();
        $user = $this->makeUser();

        $claim = $this->service->claim($promo, $user);

        $this->assertInstanceOf(PromotionClaim::class, $claim);
        $this->assertDatabaseHas('promotion_claims', [
            'promotion_id' => $promo->id,
            'user_id' => $user->id,
            'status' => PromotionClaimStatus::CLAIMED->value,
        ]);
    }

    public function test_claim_generates_a_non_empty_voucher_code(): void
    {
        $promo = $this->makeActivePromotion();
        $user = $this->makeUser();

        $claim = $this->service->claim($promo, $user);

        $this->assertNotEmpty($claim->voucher_code);
        $this->assertSame(8, strlen($claim->voucher_code));
    }

    public function test_claim_fails_when_promotion_is_inactive(): void
    {
        $promo = $this->makeActivePromotion(['is_active' => false]);
        $user = $this->makeUser();

        $this->expectException(HttpException::class);

        $this->service->claim($promo, $user);
    }

    public function test_claim_fails_when_promotion_is_not_available_today(): void
    {
        // End time already passed for today
        $promo = $this->makeActivePromotion([
            'start_time' => '00:00:00',
            'end_time' => '00:01:00',   // already over
        ]);
        $user = $this->makeUser();

        $this->expectException(HttpException::class);

        $this->service->claim($promo, $user);
    }

    public function test_claim_fails_when_max_total_redemptions_reached(): void
    {
        $user = $this->makeUser();
        $promo = $this->makeActivePromotion(['max_total_redemptions' => 1]);

        // Redeem 1 — fills the slot
        $this->makeClaim($promo, $user, [
            'status' => PromotionClaimStatus::REDEEMED->value,
            'expires_at' => now()->addHour(),
        ]);

        $user2 = $this->makeUser();

        $this->expectException(HttpException::class);

        $this->service->claim($promo, $user2);
    }

    public function test_claim_fails_when_user_already_has_active_claimed_voucher(): void
    {
        $promo = $this->makeActivePromotion();
        $user = $this->makeUser();

        // First claim succeeds
        $this->service->claim($promo, $user);

        $this->expectException(HttpException::class);

        // Second immediate claim on the same promotion → duplicate
        $this->service->claim($promo, $user);
    }

    public function test_claim_fails_when_user_per_user_redemption_limit_reached(): void
    {
        $user = $this->makeUser();
        $promo = $this->makeActivePromotion(['max_per_user_redemptions' => 1]);

        // Simulate one redemption already recorded
        $this->makeClaim($promo, $user, [
            'status' => PromotionClaimStatus::REDEEMED->value,
            'expires_at' => now()->addHour(),
        ]);

        $this->expectException(HttpException::class);

        $this->service->claim($promo, $user);
    }

    // ─── getActiveClaimForUser() ──────────────────────────────────────────────

    public function test_get_active_claim_returns_the_latest_claim(): void
    {
        $promo = $this->makeActivePromotion();
        $user = $this->makeUser();

        $claim1 = $this->makeClaim($promo, $user, ['voucher_code' => 'FIRST000']);
        $claim2 = $this->makeClaim($promo, $user, ['voucher_code' => 'SECOND00']);

        $result = $this->service->getActiveClaimForUser($promo, $user);

        $this->assertSame($claim2->id, $result->id);
    }

    public function test_get_active_claim_returns_null_when_user_has_no_claims(): void
    {
        $promo = $this->makeActivePromotion();
        $user = $this->makeUser();

        $result = $this->service->getActiveClaimForUser($promo, $user);

        $this->assertNull($result);
    }

    // ─── redeem() ─────────────────────────────────────────────────────────────

    public function test_redeem_marks_claim_as_redeemed(): void
    {
        $owner = $this->makeUser();
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
            'max_per_user_redemptions' => 5,
        ]);
        $user = $this->makeUser();
        $claim = $this->makeClaim($promo, $user, [
            'voucher_code' => 'REDEEM01',
            'expires_at' => now()->addHour(),
        ]);

        $this->service->redeem('REDEEM01', $owner);

        $this->assertDatabaseHas('promotion_claims', [
            'id' => $claim->id,
            'status' => PromotionClaimStatus::REDEEMED->value,
        ]);
    }

    public function test_redeem_fails_for_non_existent_voucher_code(): void
    {
        $owner = $this->makeUser();

        $this->expectException(HttpException::class);

        $this->service->redeem('INVALID0', $owner);
    }

    public function test_redeem_fails_when_owner_does_not_own_the_venue(): void
    {
        $owner = $this->makeUser();
        $venue = Venue::create(['owner_id' => $owner->id, 'name' => 'V', 'type' => 'bar']);
        $stranger = $this->makeUser();
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
            'max_per_user_redemptions' => 5,
        ]);
        $user = $this->makeUser();
        $this->makeClaim($promo, $user, ['voucher_code' => 'WRONGVN1']);

        $this->expectException(HttpException::class);

        $this->service->redeem('WRONGVN1', $stranger);
    }

    public function test_redeem_fails_when_voucher_already_redeemed(): void
    {
        $owner = $this->makeUser();
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
            'max_per_user_redemptions' => 5,
        ]);
        $user = $this->makeUser();
        $this->makeClaim($promo, $user, [
            'voucher_code' => 'ALRDRED1',
            'status' => PromotionClaimStatus::REDEEMED->value,
        ]);

        $this->expectException(HttpException::class);

        $this->service->redeem('ALRDRED1', $owner);
    }

    public function test_redeem_fails_when_voucher_is_expired(): void
    {
        $owner = $this->makeUser();
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
            'max_per_user_redemptions' => 5,
        ]);
        $user = $this->makeUser();
        $this->makeClaim($promo, $user, [
            'voucher_code' => 'EXPVCH01',
            'expires_at' => now()->subMinute(), // already expired
        ]);

        $this->expectException(HttpException::class);

        $this->service->redeem('EXPVCH01', $owner);
    }

    // ─── getMyClaims() ────────────────────────────────────────────────────────

    public function test_get_my_claims_returns_paginated_claims_for_user(): void
    {
        $promo = $this->makeActivePromotion();
        $user = $this->makeUser();

        $this->makeClaim($promo, $user, ['voucher_code' => 'MINE0001']);
        $this->makeClaim($promo, $user, ['voucher_code' => 'MINE0002']);

        $result = $this->service->getMyClaims($user);

        $this->assertSame(2, $result->total());
    }

    public function test_get_my_claims_auto_expires_stale_claimed_vouchers(): void
    {
        $promo = $this->makeActivePromotion();
        $user = $this->makeUser();

        $staleClaim = $this->makeClaim($promo, $user, [
            'voucher_code' => 'STALE001',
            'status' => PromotionClaimStatus::CLAIMED->value,
            'expires_at' => now()->subMinute(), // already past
        ]);

        $this->service->getMyClaims($user);

        $this->assertDatabaseHas('promotion_claims', [
            'id' => $staleClaim->id,
            'status' => PromotionClaimStatus::EXPIRED->value,
        ]);
    }

    public function test_get_my_claims_does_not_return_other_users_claims(): void
    {
        $promo = $this->makeActivePromotion();
        $user = $this->makeUser();
        $other = $this->makeUser();

        $this->makeClaim($promo, $other, ['voucher_code' => 'OTHER001']);

        $result = $this->service->getMyClaims($user);

        $this->assertSame(0, $result->total());
    }
}
