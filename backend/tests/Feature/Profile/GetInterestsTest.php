<?php

namespace Tests\Feature\Profile;

use App\Models\Interest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetInterestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_fetch_all_interests(): void
    {
        Interest::query()->create(['name' => 'Music', 'slug' => 'music']);
        Interest::query()->create(['name' => 'Business', 'slug' => 'business']);
        Interest::query()->create(['name' => 'Technology', 'slug' => 'technology']);

        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/profile/interests');

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

    public function test_authenticated_user_can_update_interests_with_selected_slugs(): void
    {
        $music = Interest::query()->create(['name' => 'Music', 'slug' => 'music']);
        $business = Interest::query()->create(['name' => 'Business', 'slug' => 'business']);
        $technology = Interest::query()->create(['name' => 'Technology', 'slug' => 'technology']);

        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

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
}
