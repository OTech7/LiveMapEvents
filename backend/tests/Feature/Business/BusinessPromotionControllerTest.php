<?php

namespace Tests\Feature\Business;

use App\Enums\PromotionClaimStatus;
use App\Models\Promotion;
use App\Models\PromotionClaim;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessPromotionControllerTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 0;

    private function makeUser(): User
    {
        return User::create([
            'phone' => '+9639180' . str_pad(++self::$counter, 6, '0', STR_PAD_LEFT),
            'profile_complete' => true,
        ]);
    }

    private function makeVenueFor(User $owner): Venue
    {
        return Venue::create(['owner_id' => $owner->id, 'name' => 'My Venue', 'type' => 'bar']);
    }

    private function makePromotion(Venue $venue, array $overrides = []): Promotion
    {
        return Promotion::create(array_merge([
            'venue_id' => $venue->id,
            'title' => 'Happy Hour',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'recurrence_type' => 'one_time',
            'start_time' => '18:00:00',
            'end_time' => '21:00:00',
            'valid_from' => now()->toDateString(),
            'is_active' => true,
            'max_per_user_redemptions' => 1,
        ], $overrides));
    }

    // ─── GET /business/promotions ─────────────────────────────────────────────

    public function test_index_returns_only_owners_promotions(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $myVenue = $this->makeVenueFor($owner);
        $otherVenue = $this->makeVenueFor($other);

        $this->makePromotion($myVenue, ['title' => 'Mine']);
        $this->makePromotion($otherVenue, ['title' => 'Not Mine']);

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/business/promotions')
            ->assertOk()
            ->assertJsonCount(1, 'data.data');
    }

    public function test_index_filters_by_venue_id_when_provided(): void
    {
        $owner = $this->makeUser();
        $venue1 = $this->makeVenueFor($owner);
        $venue2 = $this->makeVenueFor($owner);

        $this->makePromotion($venue1, ['title' => 'Venue 1 Promo']);
        $this->makePromotion($venue2, ['title' => 'Venue 2 Promo']);

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/business/promotions?venue_id={$venue1->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.title', 'Venue 1 Promo');
    }

    public function test_index_returns_empty_when_owner_has_no_promotions(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/business/promotions')
            ->assertOk()
            ->assertJsonCount(0, 'data.data');
    }

    // ─── POST /business/promotions ────────────────────────────────────────────

    public function test_store_creates_promotion_for_own_venue(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/promotions', [
                'venue_id' => $venue->id,
                'title' => 'New Deal',
                'discount_type' => 'percentage',
                'discount_value' => 15,
                'recurrence_type' => 'one_time',
                'start_time' => '10:00:00',
                'end_time' => '20:00:00',
                'valid_from' => now()->toDateString(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'New Deal');

        $this->assertDatabaseHas('promotions', ['title' => 'New Deal', 'venue_id' => $venue->id]);
    }

    public function test_store_returns_404_when_venue_belongs_to_another_user(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $otherVenue = $this->makeVenueFor($other);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/promotions', [
                'venue_id' => $otherVenue->id,
                'title' => 'Stolen',
                'discount_type' => 'fixed',
                'discount_value' => 5,
                'recurrence_type' => 'one_time',
                'start_time' => '10:00:00',
                'end_time' => '20:00:00',
                'valid_from' => now()->toDateString(),
            ])
            ->assertNotFound();
    }

    public function test_store_rejects_percentage_discount_above_100(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/promotions', [
                'venue_id' => $venue->id,
                'title' => 'Bad Promo',
                'discount_type' => 'percentage',
                'discount_value' => 110,
                'recurrence_type' => 'one_time',
                'start_time' => '10:00:00',
                'end_time' => '20:00:00',
                'valid_from' => now()->toDateString(),
            ])
            ->assertUnprocessable();
    }

    // ─── GET /business/promotions/{promotion} ─────────────────────────────────

    public function test_show_returns_promotion_details(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $promo = $this->makePromotion($venue, ['title' => 'My Promo']);

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/business/promotions/{$promo->id}")
            ->assertOk()
            ->assertJsonPath('data.title', 'My Promo');
    }

    public function test_show_returns_404_for_non_existent_promotion(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/business/promotions/99999')
            ->assertNotFound();
    }

    // ─── PUT /business/promotions/{promotion} ─────────────────────────────────

    public function test_update_changes_promotion_title(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $promo = $this->makePromotion($venue);

        $this->actingAs($owner, 'sanctum')
            ->putJson("/api/v1/business/promotions/{$promo->id}", ['title' => 'Updated'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated');
    }

    public function test_update_returns_403_for_non_owner(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $promo = $this->makePromotion($venue);

        $this->actingAs($stranger, 'sanctum')
            ->putJson("/api/v1/business/promotions/{$promo->id}", ['title' => 'Hacked'])
            ->assertForbidden();
    }

    // ─── DELETE /business/promotions/{promotion} ──────────────────────────────

    public function test_destroy_soft_deletes_promotion(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $promo = $this->makePromotion($venue);

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/business/promotions/{$promo->id}")
            ->assertOk();

        $this->assertSoftDeleted('promotions', ['id' => $promo->id]);
    }

    public function test_destroy_returns_403_for_non_owner(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $promo = $this->makePromotion($venue);

        $this->actingAs($stranger, 'sanctum')
            ->deleteJson("/api/v1/business/promotions/{$promo->id}")
            ->assertForbidden();
    }

    // ─── GET /business/promotions/{promotion}/claims ──────────────────────────

    public function test_claims_returns_all_claims_for_own_promotion(): void
    {
        $owner = $this->makeUser();
        $claimant = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $promo = $this->makePromotion($venue);

        PromotionClaim::create([
            'promotion_id' => $promo->id,
            'user_id' => $claimant->id,
            'voucher_code' => 'TESTCL01',
            'status' => PromotionClaimStatus::CLAIMED->value,
            'claimed_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/business/promotions/{$promo->id}/claims")
            ->assertOk()
            ->assertJsonCount(1, 'data.data');
    }

    public function test_claims_returns_403_for_non_owner(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $promo = $this->makePromotion($venue);

        $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/v1/business/promotions/{$promo->id}/claims")
            ->assertForbidden();
    }
}
