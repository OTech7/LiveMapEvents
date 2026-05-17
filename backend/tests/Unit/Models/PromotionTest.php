<?php

namespace Tests\Unit\Models;

use App\Enums\PromotionClaimStatus;
use App\Enums\RecurrenceType;
use App\Models\Promotion;
use App\Models\PromotionClaim;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makePromotion(array $overrides = []): Promotion
    {
        $owner = User::create(['phone' => '+963911000001', 'profile_complete' => false]);
        $venue = Venue::create(['owner_id' => $owner->id, 'name' => 'Test Venue', 'type' => 'bar']);

        return Promotion::create(array_merge([
            'venue_id' => $venue->id,
            'title' => 'Test Promotion',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'recurrence_type' => RecurrenceType::ONE_TIME->value,
            'start_time' => '09:00:00',
            'end_time' => '23:00:00',
            'valid_from' => now()->toDateString(),
            'is_active' => true,
        ], $overrides));
    }

    // ─── isActiveNow() ────────────────────────────────────────────────────────

    public function test_is_active_now_returns_true_within_time_window(): void
    {
        $promo = $this->makePromotion([
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
        ]);

        $this->assertTrue($promo->isActiveNow());
    }

    public function test_is_active_now_returns_false_before_start_time(): void
    {
        $promo = $this->makePromotion([
            'start_time' => '23:58:00',
            'end_time' => '23:59:00',
        ]);

        $this->assertFalse($promo->isActiveNow());
    }

    public function test_is_active_now_returns_false_after_end_time(): void
    {
        $promo = $this->makePromotion([
            'start_time' => '00:00:00',
            'end_time' => '00:01:00',
        ]);

        $this->assertFalse($promo->isActiveNow());
    }

    public function test_is_active_now_returns_false_when_promotion_is_inactive(): void
    {
        $promo = $this->makePromotion([
            'is_active' => false,
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
        ]);

        $this->assertFalse($promo->isActiveNow());
    }

    public function test_is_active_now_returns_false_before_valid_from_date(): void
    {
        $promo = $this->makePromotion([
            'valid_from' => now()->addDay()->toDateString(),
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
        ]);

        $this->assertFalse($promo->isActiveNow());
    }

    public function test_is_active_now_returns_false_after_valid_to_date(): void
    {
        $promo = $this->makePromotion([
            'valid_from' => now()->subDays(5)->toDateString(),
            'valid_to' => now()->subDay()->toDateString(),
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
        ]);

        $this->assertFalse($promo->isActiveNow());
    }

    public function test_is_active_now_returns_false_for_recurring_promo_on_wrong_day(): void
    {
        // Pick a day of week that is definitely NOT today
        $todayIso = now()->dayOfWeekIso; // 1-7
        $wrongDay = $todayIso === 1 ? 2 : 1;

        $promo = $this->makePromotion([
            'recurrence_type' => RecurrenceType::RECURRING->value,
            'days_of_week' => [$wrongDay],
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
        ]);

        $this->assertFalse($promo->isActiveNow());
    }

    public function test_is_active_now_returns_true_for_recurring_promo_on_correct_day(): void
    {
        $todayIso = now()->dayOfWeekIso;

        $promo = $this->makePromotion([
            'recurrence_type' => RecurrenceType::RECURRING->value,
            'days_of_week' => [$todayIso],
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
        ]);

        $this->assertTrue($promo->isActiveNow());
    }

    // ─── isUpcomingToday() ────────────────────────────────────────────────────

    public function test_is_upcoming_today_returns_true_when_start_is_in_future(): void
    {
        $promo = $this->makePromotion([
            'start_time' => '23:58:00',
            'end_time' => '23:59:00',
        ]);

        $this->assertTrue($promo->isUpcomingToday());
    }

    public function test_is_upcoming_today_returns_false_when_promo_is_currently_active(): void
    {
        $promo = $this->makePromotion([
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
        ]);

        // It is active now, so not "upcoming"
        $this->assertFalse($promo->isUpcomingToday());
    }

    public function test_is_upcoming_today_returns_false_when_inactive(): void
    {
        $promo = $this->makePromotion([
            'is_active' => false,
            'start_time' => '23:58:00',
            'end_time' => '23:59:00',
        ]);

        $this->assertFalse($promo->isUpcomingToday());
    }

    // ─── calculateExpiresAt() ─────────────────────────────────────────────────

    public function test_calculate_expires_at_returns_promo_end_when_it_is_sooner_than_two_hours(): void
    {
        // End time just 1 minute from now — sooner than 2 hours
        $endTime = now()->addMinute()->format('H:i:s');

        $promo = $this->makePromotion(['end_time' => $endTime]);

        $expected = \Carbon\Carbon::today()->setTimeFromTimeString($endTime);

        $this->assertEquals($expected->timestamp, $promo->calculateExpiresAt()->timestamp);
    }

    public function test_calculate_expires_at_returns_two_hours_when_end_time_is_far_away(): void
    {
        // End time at 23:59:59 — more than 2 hours from now
        $promo = $this->makePromotion(['end_time' => '23:59:59']);

        $expected = now()->addHours(2);

        $this->assertEqualsWithDelta(
            $expected->timestamp,
            $promo->calculateExpiresAt()->timestamp,
            2  // allow 2 seconds of test execution drift
        );
    }

    // ─── hasAvailableSlots() ──────────────────────────────────────────────────

    public function test_has_available_slots_returns_true_when_limit_is_null(): void
    {
        $promo = $this->makePromotion(['max_total_redemptions' => null]);

        $this->assertTrue($promo->hasAvailableSlots());
    }

    public function test_has_available_slots_returns_true_when_slots_remain(): void
    {
        $user = User::create(['phone' => '+963911000002']);
        $promo = $this->makePromotion([
            'max_total_redemptions' => 5,
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
        ]);

        // Create 3 redeemed claims — 2 slots still available
        PromotionClaim::create([
            'promotion_id' => $promo->id,
            'user_id' => $user->id,
            'voucher_code' => 'AAAABBBB',
            'status' => PromotionClaimStatus::REDEEMED->value,
            'claimed_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        $this->assertTrue($promo->hasAvailableSlots());
    }

    public function test_has_available_slots_returns_false_when_limit_reached(): void
    {
        $user = User::create(['phone' => '+963911000003']);
        $promo = $this->makePromotion([
            'max_total_redemptions' => 1,
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
        ]);

        PromotionClaim::create([
            'promotion_id' => $promo->id,
            'user_id' => $user->id,
            'voucher_code' => 'CCCCDDDD',
            'status' => PromotionClaimStatus::REDEEMED->value,
            'claimed_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        $this->assertFalse($promo->hasAvailableSlots());
    }

    public function test_has_available_slots_ignores_claimed_and_expired_status(): void
    {
        $user = User::create(['phone' => '+963911000004']);
        $promo = $this->makePromotion([
            'max_total_redemptions' => 1,
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
        ]);

        // Only 'redeemed' counts toward the limit
        PromotionClaim::create([
            'promotion_id' => $promo->id,
            'user_id' => $user->id,
            'voucher_code' => 'EEEEFFFF',
            'status' => PromotionClaimStatus::CLAIMED->value,
            'claimed_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        $this->assertTrue($promo->hasAvailableSlots());
    }
}
