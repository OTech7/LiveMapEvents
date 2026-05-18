<?php

namespace Tests\Feature\Profile;

use App\Models\Interest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the user-facing interest endpoints:
 *   GET    /api/v1/interests                       — global catalog
 *   GET    /api/v1/profile/interests               — current user's selection
 *   PUT    /api/v1/profile/interests               — replace selection
 *   POST   /api/v1/profile/interests/{slug}        — add one
 *   DELETE /api/v1/profile/interests/{slug}        — remove one
 */
class GetInterestsTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): array
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        return [$user, $token];
    }

    public function test_authenticated_user_can_fetch_global_interest_catalog(): void
    {
        Interest::query()->create(['name' => 'Music', 'slug' => 'music']);
        Interest::query()->create(['name' => 'Business', 'slug' => 'business']);
        Interest::query()->create(['name' => 'Technology', 'slug' => 'technology']);

        [, $token] = $this->actingUser();

        $response = $this->withToken($token)->getJson('/api/v1/interests');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => __('messages.interests_fetched_successfully'),
            ])
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'slug'],
                ],
                'errors',
            ])
            ->assertJsonFragment(['slug' => 'music'])
            ->assertJsonFragment(['slug' => 'business'])
            ->assertJsonFragment(['slug' => 'technology']);
    }

    public function test_my_interests_starts_empty_and_reflects_selection(): void
    {
        $music = Interest::query()->create(['name' => 'Music', 'slug' => 'music']);
        $sport = Interest::query()->create(['name' => 'Sport', 'slug' => 'sport']);

        [$user, $token] = $this->actingUser();

        // Initially nothing selected.
        $this->withToken($token)
            ->getJson('/api/v1/profile/interests')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $user->interests()->sync([$music->id, $sport->id]);

        $this->withToken($token)
            ->getJson('/api/v1/profile/interests')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => __('messages.my_interests_fetched_successfully'),
            ])
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['slug' => 'music'])
            ->assertJsonFragment(['slug' => 'sport']);
    }

    public function test_authenticated_user_can_replace_interests_with_selected_slugs(): void
    {
        $music = Interest::query()->create(['name' => 'Music', 'slug' => 'music']);
        $business = Interest::query()->create(['name' => 'Business', 'slug' => 'business']);
        $technology = Interest::query()->create(['name' => 'Technology', 'slug' => 'technology']);

        [$user, $token] = $this->actingUser();

        $response = $this->withToken($token)->putJson('/api/v1/profile/interests', [
            'interests' => [$music->slug, $technology->slug],
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => __('messages.interests_updated_successfully'),
            ]);

        $this->assertDatabaseHas('user_interests', [
            'user_id' => $user->id,
            'interest_id' => $music->id,
        ]);

        $this->assertDatabaseHas('user_interests', [
            'user_id' => $user->id,
            'interest_id' => $technology->id,
        ]);

        $this->assertDatabaseMissing('user_interests', [
            'user_id' => $user->id,
            'interest_id' => $business->id,
        ]);
    }

    public function test_authenticated_user_can_add_a_single_interest(): void
    {
        $music = Interest::query()->create(['name' => 'Music', 'slug' => 'music']);

        [$user, $token] = $this->actingUser();

        $this->withToken($token)
            ->postJson('/api/v1/profile/interests/music')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => __('messages.interest_added_successfully'),
                'data' => ['slug' => 'music'],
            ]);

        $this->assertDatabaseHas('user_interests', [
            'user_id' => $user->id,
            'interest_id' => $music->id,
        ]);
    }

    public function test_adding_an_interest_twice_is_idempotent(): void
    {
        $music = Interest::query()->create(['name' => 'Music', 'slug' => 'music']);

        [$user, $token] = $this->actingUser();

        $this->withToken($token)->postJson('/api/v1/profile/interests/music')->assertOk();
        $this->withToken($token)->postJson('/api/v1/profile/interests/music')->assertOk();

        $this->assertDatabaseCount('user_interests', 1);
    }

    public function test_authenticated_user_can_remove_a_single_interest(): void
    {
        $music = Interest::query()->create(['name' => 'Music', 'slug' => 'music']);
        $sport = Interest::query()->create(['name' => 'Sport', 'slug' => 'sport']);

        [$user, $token] = $this->actingUser();
        $user->interests()->sync([$music->id, $sport->id]);

        $this->withToken($token)
            ->deleteJson('/api/v1/profile/interests/music')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => __('messages.interest_removed_successfully'),
            ]);

        $this->assertDatabaseMissing('user_interests', [
            'user_id' => $user->id,
            'interest_id' => $music->id,
        ]);

        // Sport should still be there.
        $this->assertDatabaseHas('user_interests', [
            'user_id' => $user->id,
            'interest_id' => $sport->id,
        ]);
    }

    public function test_adding_unknown_interest_returns_404(): void
    {
        [, $token] = $this->actingUser();

        $this->withToken($token)
            ->postJson('/api/v1/profile/interests/does-not-exist')
            ->assertNotFound();
    }
}
