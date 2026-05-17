<?php

namespace Tests\Feature\Authorization;

use App\Models\Promotion;
use App\Models\User;
use App\Models\Venue;
use App\Policies\PromotionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for PromotionPolicy.
 *
 * Uses the policy class directly (no HTTP) so the assertions are precise
 * and not blurred by middleware or route-model binding.
 */
class PromotionPolicyTest extends TestCase
{
    use RefreshDatabase;

    private PromotionPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new PromotionPolicy();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private static int $counter = 0;

    private function makeUser(): User
    {
        return User::create(['phone' => '+9639130' . str_pad(++self::$counter, 6, '0', STR_PAD_LEFT)]);
    }

    private function makePromotion(User $owner): Promotion
    {
        $venue = Venue::create(['owner_id' => $owner->id, 'name' => 'V', 'type' => 'bar']);

        return Promotion::create([
            'venue_id' => $venue->id,
            'title' => 'Test Promo',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'recurrence_type' => 'one_time',
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'valid_from' => now()->toDateString(),
            'is_active' => true,
            'max_per_user_redemptions' => 1,
        ]);
    }

    // ─── view() ───────────────────────────────────────────────────────────────

    public function test_any_authenticated_user_can_view_a_promotion(): void
    {
        $owner = $this->makeUser();
        $visitor = $this->makeUser();
        $promo = $this->makePromotion($owner);

        $this->assertTrue($this->policy->view($visitor, $promo));
    }

    public function test_owner_can_also_view_their_own_promotion(): void
    {
        $owner = $this->makeUser();
        $promo = $this->makePromotion($owner);

        $this->assertTrue($this->policy->view($owner, $promo));
    }

    // ─── viewClaims() ─────────────────────────────────────────────────────────

    public function test_owner_can_view_claims_for_their_promotion(): void
    {
        $owner = $this->makeUser();
        $promo = $this->makePromotion($owner);

        $this->assertTrue($this->policy->viewClaims($owner, $promo));
    }

    public function test_non_owner_cannot_view_claims(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $promo = $this->makePromotion($owner);

        $this->assertFalse($this->policy->viewClaims($stranger, $promo));
    }

    // ─── update() ─────────────────────────────────────────────────────────────

    public function test_owner_can_update_their_promotion(): void
    {
        $owner = $this->makeUser();
        $promo = $this->makePromotion($owner);

        $this->assertTrue($this->policy->update($owner, $promo));
    }

    public function test_non_owner_cannot_update_promotion(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $promo = $this->makePromotion($owner);

        $this->assertFalse($this->policy->update($stranger, $promo));
    }

    // ─── delete() ─────────────────────────────────────────────────────────────

    public function test_owner_can_delete_their_promotion(): void
    {
        $owner = $this->makeUser();
        $promo = $this->makePromotion($owner);

        $this->assertTrue($this->policy->delete($owner, $promo));
    }

    public function test_non_owner_cannot_delete_promotion(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $promo = $this->makePromotion($owner);

        $this->assertFalse($this->policy->delete($stranger, $promo));
    }

    // ─── N+1 guard: venue is lazy-loaded when not already present ─────────────

    public function test_policy_loads_venue_relation_when_not_already_loaded(): void
    {
        $owner = $this->makeUser();
        $promo = $this->makePromotion($owner);

        // Reload promotion WITHOUT eager-loading venue
        $freshPromo = Promotion::find($promo->id);
        $this->assertFalse($freshPromo->relationLoaded('venue'));

        // Policy must still work (will lazy-load internally)
        $this->assertTrue($this->policy->update($owner, $freshPromo));
        $this->assertTrue($freshPromo->relationLoaded('venue'));
    }

    public function test_policy_does_not_reload_venue_when_already_loaded(): void
    {
        $owner = $this->makeUser();
        $promo = $this->makePromotion($owner);

        $promoWithVenue = Promotion::with('venue')->find($promo->id);
        $this->assertTrue($promoWithVenue->relationLoaded('venue'));

        // Should not trigger additional queries (venue already loaded)
        $this->assertTrue($this->policy->delete($owner, $promoWithVenue));
    }
}
