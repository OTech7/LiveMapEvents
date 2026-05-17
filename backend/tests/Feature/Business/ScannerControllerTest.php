<?php

namespace Tests\Feature\Business;

use App\Enums\PromotionClaimStatus;
use App\Models\Promotion;
use App\Models\PromotionClaim;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScannerControllerTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 0;

    private function makeUser(): User
    {
        return User::create([
            'phone' => '+9639190' . str_pad(++self::$counter, 6, '0', STR_PAD_LEFT),
            'profile_complete' => true,
        ]);
    }

    private function makePromotion(User $owner): array
    {
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

        return [$venue, $promo];
    }

    private function makeClaim(Promotion $promo, User $user, string $code, array $overrides = []): PromotionClaim
    {
        return PromotionClaim::create(array_merge([
            'promotion_id' => $promo->id,
            'user_id' => $user->id,
            'voucher_code' => $code,
            'status' => PromotionClaimStatus::CLAIMED->value,
            'claimed_at' => now(),
            'expires_at' => now()->addHour(),
        ], $overrides));
    }

    // ─── POST /business/scanner/redeem ────────────────────────────────────────

    public function test_redeem_marks_voucher_as_redeemed(): void
    {
        $owner = $this->makeUser();
        $claimant = $this->makeUser();
        [, $promo] = $this->makePromotion($owner);
        $claim = $this->makeClaim($promo, $claimant, 'VALIDCO1');

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/scanner/redeem', ['voucher_code' => 'VALIDCO1'])
            ->assertOk();

        $this->assertDatabaseHas('promotion_claims', [
            'id' => $claim->id,
            'status' => PromotionClaimStatus::REDEEMED->value,
        ]);
    }

    public function test_redeem_returns_claim_data_in_response(): void
    {
        $owner = $this->makeUser();
        $claimant = $this->makeUser();
        [, $promo] = $this->makePromotion($owner);
        $this->makeClaim($promo, $claimant, 'VALIDCO2');

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/scanner/redeem', ['voucher_code' => 'VALIDCO2'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'voucher_code', 'status']]);
    }

    public function test_redeem_returns_404_for_non_existent_voucher(): void
    {
        $owner = $this->makeUser();

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/scanner/redeem', ['voucher_code' => 'BADCODE1'])
            ->assertNotFound();
    }

    public function test_redeem_returns_403_when_voucher_belongs_to_different_venue(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $claimant = $this->makeUser();

        [, $promo] = $this->makePromotion($owner); // owned by $owner
        $this->makeClaim($promo, $claimant, 'WRNGVN01');

        $this->actingAs($stranger, 'sanctum')
            ->postJson('/api/v1/business/scanner/redeem', ['voucher_code' => 'WRNGVN01'])
            ->assertForbidden();
    }

    public function test_redeem_returns_422_when_voucher_already_redeemed(): void
    {
        $owner = $this->makeUser();
        $claimant = $this->makeUser();
        [, $promo] = $this->makePromotion($owner);
        $this->makeClaim($promo, $claimant, 'ALRDRD01', [
            'status' => PromotionClaimStatus::REDEEMED->value,
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/scanner/redeem', ['voucher_code' => 'ALRDRD01'])
            ->assertUnprocessable();
    }

    public function test_redeem_returns_422_when_voucher_is_expired(): void
    {
        $owner = $this->makeUser();
        $claimant = $this->makeUser();
        [, $promo] = $this->makePromotion($owner);
        $this->makeClaim($promo, $claimant, 'EXPVCH01', [
            'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/scanner/redeem', ['voucher_code' => 'EXPVCH01'])
            ->assertUnprocessable();
    }

    public function test_redeem_is_case_insensitive_for_voucher_code(): void
    {
        $owner = $this->makeUser();
        $claimant = $this->makeUser();
        [, $promo] = $this->makePromotion($owner);
        $this->makeClaim($promo, $claimant, 'UPPERCS1');

        // Send lowercase — service normalises with strtoupper()
        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/scanner/redeem', ['voucher_code' => 'uppercs1'])
            ->assertOk();
    }

    public function test_redeem_returns_422_for_missing_voucher_code(): void
    {
        $owner = $this->makeUser();

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/scanner/redeem', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['voucher_code']);
    }
}
