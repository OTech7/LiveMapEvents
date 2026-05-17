<?php

namespace Tests\Unit\Services;

use App\Models\Interest;
use App\Models\User;
use App\Services\InterestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterestServiceTest extends TestCase
{
    use RefreshDatabase;

    private InterestService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InterestService::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function createInterest(string $name, string $slug): Interest
    {
        return Interest::create(['name' => $name, 'slug' => $slug]);
    }

    private function createUser(): User
    {
        static $counter = 0;
        return User::create(['phone' => '+9639110000' . str_pad(++$counter, 2, '0', STR_PAD_LEFT)]);
    }

    // ─── getAll() ─────────────────────────────────────────────────────────────

    public function test_get_all_returns_all_interests(): void
    {
        $this->createInterest('Music', 'music');
        $this->createInterest('Sports', 'sports');

        $result = $this->service->getAll();

        $this->assertCount(2, $result);
    }

    public function test_get_all_returns_interests_ordered_alphabetically(): void
    {
        $this->createInterest('Sports', 'sports');
        $this->createInterest('Art', 'art');
        $this->createInterest('Music', 'music');

        $names = $this->service->getAll()->pluck('name')->toArray();

        $this->assertSame(['Art', 'Music', 'Sports'], $names);
    }

    public function test_get_all_returns_empty_collection_when_no_interests_exist(): void
    {
        $result = $this->service->getAll();

        $this->assertCount(0, $result);
    }

    // ─── getForUser() ─────────────────────────────────────────────────────────

    public function test_get_for_user_returns_the_users_attached_interests(): void
    {
        $user = $this->createUser();
        $music = $this->createInterest('Music', 'music');
        $sports = $this->createInterest('Sports', 'sports');

        $user->interests()->attach([$music->id, $sports->id]);

        $result = $this->service->getForUser($user);

        $this->assertCount(2, $result);
    }

    public function test_get_for_user_returns_empty_when_user_has_no_interests(): void
    {
        $user = $this->createUser();

        $result = $this->service->getForUser($user);

        $this->assertCount(0, $result);
    }

    public function test_get_for_user_does_not_return_other_users_interests(): void
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $music = $this->createInterest('Music', 'music');

        $user1->interests()->attach($music->id);

        $result = $this->service->getForUser($user2);

        $this->assertCount(0, $result);
    }

    // ─── syncBySlug() ─────────────────────────────────────────────────────────

    public function test_sync_by_slug_attaches_interests_by_slug(): void
    {
        $user = $this->createUser();
        $this->createInterest('Music', 'music');
        $this->createInterest('Sports', 'sports');

        $this->service->syncBySlug($user, ['music', 'sports']);

        $this->assertCount(2, $user->fresh()->interests);
    }

    public function test_sync_by_slug_replaces_previous_interests(): void
    {
        $user = $this->createUser();
        $music = $this->createInterest('Music', 'music');
        $sports = $this->createInterest('Sports', 'sports');

        $user->interests()->attach($music->id);

        $this->service->syncBySlug($user, ['sports']);

        $slugs = $user->fresh()->interests->pluck('slug')->toArray();
        $this->assertSame(['sports'], $slugs);
    }

    public function test_sync_by_slug_clears_all_interests_when_given_empty_array(): void
    {
        $user = $this->createUser();
        $music = $this->createInterest('Music', 'music');
        $user->interests()->attach($music->id);

        $this->service->syncBySlug($user, []);

        $this->assertCount(0, $user->fresh()->interests);
    }

    public function test_sync_by_slug_ignores_non_existent_slugs(): void
    {
        $user = $this->createUser();
        $this->createInterest('Music', 'music');

        $this->service->syncBySlug($user, ['music', 'does-not-exist']);

        // Only the valid slug gets attached
        $this->assertCount(1, $user->fresh()->interests);
    }

    // ─── attach() ─────────────────────────────────────────────────────────────

    public function test_attach_adds_interest_to_user(): void
    {
        $user = $this->createUser();
        $interest = $this->createInterest('Music', 'music');

        $this->service->attach($user, $interest);

        $this->assertTrue($user->interests()->where('interests.id', $interest->id)->exists());
    }

    public function test_attach_does_not_detach_existing_interests(): void
    {
        $user = $this->createUser();
        $music = $this->createInterest('Music', 'music');
        $sports = $this->createInterest('Sports', 'sports');

        $user->interests()->attach($music->id);

        $this->service->attach($user, $sports);

        $this->assertCount(2, $user->fresh()->interests);
    }

    public function test_attach_is_idempotent_when_interest_already_attached(): void
    {
        $user = $this->createUser();
        $interest = $this->createInterest('Music', 'music');

        $user->interests()->attach($interest->id);
        $this->service->attach($user, $interest); // attach again

        $this->assertCount(1, $user->fresh()->interests);
    }

    // ─── detach() ─────────────────────────────────────────────────────────────

    public function test_detach_removes_interest_from_user(): void
    {
        $user = $this->createUser();
        $interest = $this->createInterest('Music', 'music');

        $user->interests()->attach($interest->id);

        $this->service->detach($user, $interest);

        $this->assertCount(0, $user->fresh()->interests);
    }

    public function test_detach_only_removes_the_specified_interest(): void
    {
        $user = $this->createUser();
        $music = $this->createInterest('Music', 'music');
        $sports = $this->createInterest('Sports', 'sports');

        $user->interests()->attach([$music->id, $sports->id]);

        $this->service->detach($user, $music);

        $remaining = $user->fresh()->interests->pluck('slug')->toArray();
        $this->assertSame(['sports'], $remaining);
    }
}
