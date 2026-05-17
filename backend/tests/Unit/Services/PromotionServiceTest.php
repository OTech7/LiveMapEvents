<?php

namespace Tests\Unit\Services;

use App\Enums\DiscountType;
use App\Models\Promotion;
use App\Models\User;
use App\Models\Venue;
use App\Services\PromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PromotionServiceTest extends TestCase
{
    use RefreshDatabase;

    private PromotionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PromotionService::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeOwner(): User
    {
        static $counter = 0;
        return User::create(['phone' => '+9639110' . str_pad(++$counter, 6, '0', STR_PAD_LEFT)]);
    }

    private function makeVenueFor(User $owner, array $overrides = []): Venue
    {
        return Venue::create(array_merge([
            'owner_id' => $owner->id,
            'name' => 'Test Venue',
            'type' => 'bar',
        ], $overrides));
    }

    private function basePromotionData(int $venueId): array
    {
        return [
            'venue_id' => $venueId,
            'title' => 'Happy Hour',
            'discount_type' => DiscountType::PERCENTAGE->value,
            'discount_value' => 20,
            'recurrence_type' => 'one_time',
            'start_time' => '18:00:00',
            'end_time' => '21:00:00',
            'valid_from' => now()->toDateString(),
            'is_active' => true,
        ];
    }

    private function makePromotion(User $owner, Venue $venue, array $overrides = []): Promotion
    {
        return Promotion::create(array_merge($this->basePromotionData($venue->id), $overrides));
    }

    // ─── create() ─────────────────────────────────────────────────────────────

    public function test_create_persists_promotion_for_venue_owner(): void
    {
        $owner = $this->makeOwner();
        $venue = $this->makeVenueFor($owner);

        $promo = $this->service->create($owner, $this->basePromotionData($venue->id));

        $this->assertInstanceOf(Promotion::class, $promo);
        $this->assertDatabaseHas('promotions', ['id' => $promo->id, 'title' => 'Happy Hour']);
    }

    public function test_create_returns_404_when_venue_does_not_belong_to_owner(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $venue = $this->makeVenueFor($otherOwner);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->create($owner, $this->basePromotionData($venue->id));
    }

    public function test_create_rejects_percentage_discount_above_100(): void
    {
        $owner = $this->makeOwner();
        $venue = $this->makeVenueFor($owner);

        $data = array_merge($this->basePromotionData($venue->id), [
            'discount_type' => DiscountType::PERCENTAGE->value,
            'discount_value' => 110,
        ]);

        $this->expectException(HttpException::class);

        $this->service->create($owner, $data);
    }

    public function test_create_allows_fixed_discount_above_100(): void
    {
        $owner = $this->makeOwner();
        $venue = $this->makeVenueFor($owner);

        $data = array_merge($this->basePromotionData($venue->id), [
            'discount_type' => DiscountType::FIXED->value,
            'discount_value' => 150,
        ]);

        $promo = $this->service->create($owner, $data);

        $this->assertSame(DiscountType::FIXED->value, $promo->discount_type);
    }

    // ─── update() ─────────────────────────────────────────────────────────────

    public function test_update_changes_promotion_title(): void
    {
        $owner = $this->makeOwner();
        $venue = $this->makeVenueFor($owner);
        $promo = $this->makePromotion($owner, $venue);

        $updated = $this->service->update($owner, $promo, ['title' => 'New Title']);

        $this->assertSame('New Title', $updated->title);
        $this->assertDatabaseHas('promotions', ['id' => $promo->id, 'title' => 'New Title']);
    }

    public function test_update_returns_403_when_user_does_not_own_promotion(): void
    {
        $owner = $this->makeOwner();
        $stranger = $this->makeOwner();
        $venue = $this->makeVenueFor($owner);
        $promo = $this->makePromotion($owner, $venue);

        $this->expectException(HttpException::class);

        $this->service->update($stranger, $promo, ['title' => 'Hacked']);
    }

    public function test_update_sets_manually_deactivated_when_is_active_false(): void
    {
        $owner = $this->makeOwner();
        $venue = $this->makeVenueFor($owner);
        $promo = $this->makePromotion($owner, $venue);

        $this->service->update($owner, $promo, ['is_active' => false]);

        $this->assertDatabaseHas('promotions', [
            'id' => $promo->id,
            'manually_deactivated' => true,
        ]);
    }

    public function test_update_clears_manually_deactivated_when_is_active_true(): void
    {
        $owner = $this->makeOwner();
        $venue = $this->makeVenueFor($owner);
        $promo = $this->makePromotion($owner, $venue, ['manually_deactivated' => true]);

        $this->service->update($owner, $promo, ['is_active' => true]);

        $this->assertDatabaseHas('promotions', [
            'id' => $promo->id,
            'manually_deactivated' => false,
        ]);
    }

    // ─── delete() ─────────────────────────────────────────────────────────────

    public function test_delete_soft_deletes_the_promotion(): void
    {
        $owner = $this->makeOwner();
        $venue = $this->makeVenueFor($owner);
        $promo = $this->makePromotion($owner, $venue);

        $this->service->delete($owner, $promo);

        $this->assertSoftDeleted('promotions', ['id' => $promo->id]);
    }

    public function test_delete_returns_403_when_user_does_not_own_promotion(): void
    {
        $owner = $this->makeOwner();
        $stranger = $this->makeOwner();
        $venue = $this->makeVenueFor($owner);
        $promo = $this->makePromotion($owner, $venue);

        $this->expectException(HttpException::class);

        $this->service->delete($stranger, $promo);
    }

    // ─── getForOwner() ────────────────────────────────────────────────────────

    public function test_get_for_owner_returns_only_owners_promotions(): void
    {
        $owner = $this->makeOwner();
        $otherOwner = $this->makeOwner();
        $venue = $this->makeVenueFor($owner);
        $otherVenue = $this->makeVenueFor($otherOwner);

        $this->makePromotion($owner, $venue);
        $this->makePromotion($otherOwner, $otherVenue);

        $result = $this->service->getForOwner($owner);

        $this->assertSame(1, $result->total());
    }

    public function test_get_for_owner_filters_by_venue_id_when_provided(): void
    {
        $owner = $this->makeOwner();
        $venue1 = $this->makeVenueFor($owner, ['name' => 'Venue 1']);
        $venue2 = $this->makeVenueFor($owner, ['name' => 'Venue 2']);

        $this->makePromotion($owner, $venue1);
        $this->makePromotion($owner, $venue2);

        $result = $this->service->getForOwner($owner, $venue1->id);

        $this->assertSame(1, $result->total());
        $this->assertSame($venue1->id, $result->first()->venue_id);
    }

    public function test_get_for_owner_returns_paginated_results(): void
    {
        $owner = $this->makeOwner();
        $venue = $this->makeVenueFor($owner);

        for ($i = 0; $i < 3; $i++) {
            $this->makePromotion($owner, $venue);
        }

        $result = $this->service->getForOwner($owner);

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);
        $this->assertSame(3, $result->total());
    }
}
