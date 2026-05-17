<?php

namespace Tests\Feature\Authorization;

use App\Models\Promotion;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies that every route behind auth:sanctum properly rejects
 * unauthenticated requests and accepts authenticated ones.
 */
class ProtectedRoutesTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private static int $counter = 0;

    private function makeAuthenticatedUser(): User
    {
        return User::create([
            'phone' => '+9639140' . str_pad(++self::$counter, 6, '0', STR_PAD_LEFT),
            'first_name' => 'Test',
            'last_name' => 'User',
            'profile_complete' => true,
        ]);
    }

    // ─── Auth endpoints (public) ──────────────────────────────────────────────

    public function test_request_otp_is_publicly_accessible(): void
    {
        $this->postJson('/api/v1/auth/phone/request-otp', ['phone' => '+963911000001'])
            ->assertStatus(200);
    }

    public function test_verify_otp_is_publicly_accessible(): void
    {
        // No OTP in Redis → 422 (not 401), meaning the route itself is public
        $this->postJson('/api/v1/auth/phone/verify-otp', ['phone' => '+963911000001', 'otp' => '000000'])
            ->assertStatus(422);
    }

    public function test_google_login_is_publicly_accessible(): void
    {
        // Bad token → 422/500 from Google verification, NOT 401 (route is public)
        $response = $this->postJson('/api/v1/auth/google', ['id_token' => 'invalid']);
        $this->assertNotSame(401, $response->status());
    }

    // ─── /me (protected) ──────────────────────────────────────────────────────

    public function test_me_returns_401_for_unauthenticated_request(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_me_returns_200_for_authenticated_user(): void
    {
        $user = $this->makeAuthenticatedUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me')
            ->assertOk();
    }

    // ─── /profile (protected) ─────────────────────────────────────────────────

    public function test_profile_show_returns_401_for_unauthenticated_request(): void
    {
        $this->getJson('/api/v1/profile')
            ->assertUnauthorized();
    }

    public function test_profile_show_returns_200_for_authenticated_user(): void
    {
        $user = $this->makeAuthenticatedUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/profile')
            ->assertOk();
    }

    public function test_profile_update_returns_401_for_unauthenticated_request(): void
    {
        $this->putJson('/api/v1/profile', ['first_name' => 'Hacker'])
            ->assertUnauthorized();
    }

    // ─── /interests (protected) ───────────────────────────────────────────────

    public function test_interests_index_returns_401_for_unauthenticated_request(): void
    {
        $this->getJson('/api/v1/interests')
            ->assertUnauthorized();
    }

    public function test_interests_index_returns_200_for_authenticated_user(): void
    {
        $user = $this->makeAuthenticatedUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/interests')
            ->assertOk();
    }

    // ─── /business/promotions (protected) ────────────────────────────────────

    public function test_business_promotions_index_returns_401_for_unauthenticated(): void
    {
        $this->getJson('/api/v1/business/promotions')
            ->assertUnauthorized();
    }

    public function test_business_promotions_index_returns_200_for_authenticated_user(): void
    {
        $user = $this->makeAuthenticatedUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/business/promotions')
            ->assertOk();
    }

    public function test_business_promotions_store_returns_401_for_unauthenticated(): void
    {
        $this->postJson('/api/v1/business/promotions', [])
            ->assertUnauthorized();
    }

    // ─── /me/claims (protected) ───────────────────────────────────────────────

    public function test_my_claims_returns_401_for_unauthenticated(): void
    {
        $this->getJson('/api/v1/me/claims')
            ->assertUnauthorized();
    }

    public function test_my_claims_returns_200_for_authenticated_user(): void
    {
        $user = $this->makeAuthenticatedUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/claims')
            ->assertOk();
    }

    // ─── /scanner/redeem (protected) ──────────────────────────────────────────

    public function test_scanner_redeem_returns_401_for_unauthenticated(): void
    {
        $this->postJson('/api/v1/business/scanner/redeem', ['voucher_code' => 'ABCD1234'])
            ->assertUnauthorized();
    }

    // ─── /pins/nearby (protected) ─────────────────────────────────────────────

    public function test_pins_nearby_returns_401_for_unauthenticated(): void
    {
        $this->getJson('/api/v1/pins/nearby')
            ->assertUnauthorized();
    }

    // ─── Promotion ownership via HTTP ────────────────────────────────────────

    public function test_non_owner_cannot_update_another_users_promotion_via_api(): void
    {
        $owner = $this->makeAuthenticatedUser();
        $stranger = $this->makeAuthenticatedUser();

        $venue = Venue::create(['owner_id' => $owner->id, 'name' => 'V', 'type' => 'bar']);
        $promo = Promotion::create([
            'venue_id' => $venue->id,
            'title' => 'My Promo',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'recurrence_type' => 'one_time',
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'valid_from' => now()->toDateString(),
            'is_active' => true,
            'max_per_user_redemptions' => 1,
        ]);

        $this->actingAs($stranger, 'sanctum')
            ->putJson("/api/v1/business/promotions/{$promo->id}", ['title' => 'Stolen'])
            ->assertForbidden();
    }

    public function test_non_owner_cannot_delete_another_users_promotion_via_api(): void
    {
        $owner = $this->makeAuthenticatedUser();
        $stranger = $this->makeAuthenticatedUser();

        $venue = Venue::create(['owner_id' => $owner->id, 'name' => 'V', 'type' => 'bar']);
        $promo = Promotion::create([
            'venue_id' => $venue->id,
            'title' => 'My Promo',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'recurrence_type' => 'one_time',
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'valid_from' => now()->toDateString(),
            'is_active' => true,
            'max_per_user_redemptions' => 1,
        ]);

        $this->actingAs($stranger, 'sanctum')
            ->deleteJson("/api/v1/business/promotions/{$promo->id}")
            ->assertForbidden();
    }

    public function test_owner_can_update_their_own_promotion_via_api(): void
    {
        $owner = $this->makeAuthenticatedUser();
        $venue = Venue::create(['owner_id' => $owner->id, 'name' => 'V', 'type' => 'bar']);
        $promo = Promotion::create([
            'venue_id' => $venue->id,
            'title' => 'My Promo',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'recurrence_type' => 'one_time',
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'valid_from' => now()->toDateString(),
            'is_active' => true,
            'max_per_user_redemptions' => 1,
        ]);

        $this->actingAs($owner, 'sanctum')
            ->putJson("/api/v1/business/promotions/{$promo->id}", ['title' => 'Updated Title'])
            ->assertOk();
    }

    // ─── Logout (protected) ───────────────────────────────────────────────────

    public function test_logout_returns_401_for_unauthenticated(): void
    {
        $this->postJson('/api/v1/auth/logout')
            ->assertUnauthorized();
    }

    public function test_logout_succeeds_for_authenticated_user(): void
    {
        $user = $this->makeAuthenticatedUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/logout')
            ->assertOk();
    }
}
