<?php

namespace Tests\Feature\Validation;

use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HTTP-level validation tests for Promotion FormRequests.
 */
class PromotionRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 0;

    private function makeUser(): User
    {
        return User::create([
            'phone' => '+9639160' . str_pad(++self::$counter, 6, '0', STR_PAD_LEFT),
            'profile_complete' => true,
        ]);
    }

    private function makeVenueFor(User $owner): Venue
    {
        return Venue::create(['owner_id' => $owner->id, 'name' => 'V', 'type' => 'bar']);
    }

    private function validStorePayload(int $venueId): array
    {
        return [
            'venue_id' => $venueId,
            'title' => 'Happy Hour',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'recurrence_type' => 'one_time',
            'start_time' => '18:00:00',
            'end_time' => '21:00:00',
            'valid_from' => now()->toDateString(),
        ];
    }

    // ─── StorePromotionRequest ────────────────────────────────────────────────

    public function test_store_promotion_requires_venue_id(): void
    {
        $user = $this->makeUser();
        $payload = $this->validStorePayload(1);
        unset($payload['venue_id']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/business/promotions', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['venue_id']);
    }

    public function test_store_promotion_requires_venue_to_exist(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/business/promotions', $this->validStorePayload(99999))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['venue_id']);
    }

    public function test_store_promotion_requires_title(): void
    {
        $user = $this->makeUser();
        $venue = $this->makeVenueFor($user);
        $payload = $this->validStorePayload($venue->id);
        unset($payload['title']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/business/promotions', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_store_promotion_rejects_title_exceeding_120_chars(): void
    {
        $user = $this->makeUser();
        $venue = $this->makeVenueFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/business/promotions', array_merge(
                $this->validStorePayload($venue->id),
                ['title' => str_repeat('a', 121)]
            ))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_store_promotion_requires_valid_discount_type(): void
    {
        $user = $this->makeUser();
        $venue = $this->makeVenueFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/business/promotions', array_merge(
                $this->validStorePayload($venue->id),
                ['discount_type' => 'crypto']
            ))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['discount_type']);
    }

    public function test_store_promotion_requires_discount_value(): void
    {
        $user = $this->makeUser();
        $venue = $this->makeVenueFor($user);
        $payload = $this->validStorePayload($venue->id);
        unset($payload['discount_value']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/business/promotions', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['discount_value']);
    }

    public function test_store_promotion_rejects_end_time_before_start_time(): void
    {
        $user = $this->makeUser();
        $venue = $this->makeVenueFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/business/promotions', array_merge(
                $this->validStorePayload($venue->id),
                ['start_time' => '21:00:00', 'end_time' => '18:00:00']
            ))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_time']);
    }

    public function test_store_promotion_requires_days_of_week_for_recurring_type(): void
    {
        $user = $this->makeUser();
        $venue = $this->makeVenueFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/business/promotions', array_merge(
                $this->validStorePayload($venue->id),
                ['recurrence_type' => 'recurring']
            // days_of_week intentionally omitted
            ))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['days_of_week']);
    }

    public function test_store_promotion_rejects_invalid_day_of_week_values(): void
    {
        $user = $this->makeUser();
        $venue = $this->makeVenueFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/business/promotions', array_merge(
                $this->validStorePayload($venue->id),
                ['recurrence_type' => 'recurring', 'days_of_week' => [0, 8]]  // must be 1-7
            ))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['days_of_week.0', 'days_of_week.1']);
    }

    public function test_store_promotion_rejects_valid_from_in_the_past(): void
    {
        $user = $this->makeUser();
        $venue = $this->makeVenueFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/business/promotions', array_merge(
                $this->validStorePayload($venue->id),
                ['valid_from' => now()->subDay()->toDateString()]
            ))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['valid_from']);
    }

    public function test_store_promotion_rejects_valid_to_before_valid_from(): void
    {
        $user = $this->makeUser();
        $venue = $this->makeVenueFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/business/promotions', array_merge(
                $this->validStorePayload($venue->id),
                [
                    'valid_from' => now()->addDays(5)->toDateString(),
                    'valid_to' => now()->addDays(2)->toDateString(),
                ]
            ))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['valid_to']);
    }

    public function test_store_promotion_accepts_valid_payload(): void
    {
        $user = $this->makeUser();
        $venue = $this->makeVenueFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/business/promotions', $this->validStorePayload($venue->id))
            ->assertCreated();
    }

    // ─── UpdatePromotionRequest ───────────────────────────────────────────────

    public function test_update_promotion_rejects_invalid_discount_type(): void
    {
        $user = $this->makeUser();
        $venue = $this->makeVenueFor($user);
        $promo = \App\Models\Promotion::create(array_merge(
            $this->validStorePayload($venue->id),
            ['max_per_user_redemptions' => 1]
        ));

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/business/promotions/{$promo->id}", [
                'discount_type' => 'tokens',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['discount_type']);
    }

    public function test_update_promotion_rejects_end_time_before_start_time(): void
    {
        $user = $this->makeUser();
        $venue = $this->makeVenueFor($user);
        $promo = \App\Models\Promotion::create(array_merge(
            $this->validStorePayload($venue->id),
            ['max_per_user_redemptions' => 1]
        ));

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/business/promotions/{$promo->id}", [
                'start_time' => '21:00:00',
                'end_time' => '18:00:00',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_time']);
    }

    // ─── RedeemVoucherRequest ─────────────────────────────────────────────────

    public function test_redeem_requires_voucher_code(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/business/scanner/redeem', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['voucher_code']);
    }

    public function test_redeem_rejects_voucher_code_exceeding_12_chars(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/business/scanner/redeem', [
                'voucher_code' => str_repeat('A', 13),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['voucher_code']);
    }

    // ─── NearbyPromotionsRequest ──────────────────────────────────────────────

    public function test_nearby_promotions_requires_lat_and_lng(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/promotions/nearby')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lat', 'lng']);
    }

    public function test_nearby_promotions_rejects_lat_out_of_bounds(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/promotions/nearby?lat=95&lng=36')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lat']);
    }

    public function test_nearby_promotions_rejects_radius_below_minimum(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/promotions/nearby?lat=33.5&lng=36.3&radius=50')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['radius']);
    }

    // ─── NearbyPinsRequest ────────────────────────────────────────────────────

    public function test_nearby_pins_requires_lat_lng_and_radius(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/pins/nearby')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lat', 'lng', 'radius']);
    }

    public function test_nearby_pins_rejects_radius_above_50000(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/pins/nearby?lat=33.5&lng=36.3&radius=60000')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['radius']);
    }

    public function test_nearby_pins_rejects_radius_below_100(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/pins/nearby?lat=33.5&lng=36.3&radius=50')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['radius']);
    }

    public function test_nearby_pins_rejects_lng_below_minus_180(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/pins/nearby?lat=33.5&lng=-185&radius=500')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lng']);
    }
}
