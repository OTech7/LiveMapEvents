<?php

namespace Tests\Feature\Validation;

use App\Models\Interest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HTTP-level validation tests for Profile FormRequests.
 */
class ProfileRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 0;

    private function makeUser(array $attrs = []): User
    {
        return User::create(array_merge([
            'phone' => '+9639150' . str_pad(++self::$counter, 6, '0', STR_PAD_LEFT),
            'first_name' => 'Test',
            'last_name' => 'User',
            'profile_complete' => true,
        ], $attrs));
    }

    // ─── UpdateProfileRequest ─────────────────────────────────────────────────

    public function test_update_profile_rejects_invalid_gender(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', ['gender' => 'nonbinary'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['gender']);
    }

    public function test_update_profile_accepts_valid_gender_values(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', ['gender' => 'female'])
            ->assertOk();
    }

    public function test_update_profile_rejects_lat_above_90(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', ['lat' => 91, 'lng' => 36.3])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lat']);
    }

    public function test_update_profile_rejects_lat_below_minus_90(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', ['lat' => -91, 'lng' => 36.3])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lat']);
    }

    public function test_update_profile_rejects_lng_above_180(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', ['lat' => 33.5, 'lng' => 181])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lng']);
    }

    public function test_update_profile_accepts_valid_coordinates(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', ['lat' => 33.5, 'lng' => 36.3])
            ->assertOk();
    }

    public function test_update_profile_rejects_first_name_exceeding_255_chars(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', ['first_name' => str_repeat('a', 256)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['first_name']);
    }

    // ─── UpdateDiscoverySettingsRequest ───────────────────────────────────────

    public function test_discovery_settings_requires_radius(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/discovery-settings', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['radius']);
    }

    public function test_discovery_settings_rejects_radius_below_100(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/discovery-settings', ['radius' => 50])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['radius']);
    }

    public function test_discovery_settings_rejects_radius_above_5000(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/discovery-settings', ['radius' => 6000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['radius']);
    }

    public function test_discovery_settings_accepts_radius_at_boundary_values(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/discovery-settings', ['radius' => 100])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/discovery-settings', ['radius' => 5000])
            ->assertOk();
    }

    public function test_discovery_settings_notifications_field_is_optional(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/discovery-settings', ['radius' => 500])
            ->assertOk();
    }

    public function test_discovery_settings_rejects_non_boolean_notifications(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/discovery-settings', [
                'radius' => 500,
                'notifications' => 'yes',  // should be boolean
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['notifications']);
    }

    // ─── UpdateInterestsRequest ───────────────────────────────────────────────

    public function test_update_interests_requires_interests_array(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/interests', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['interests']);
    }

    public function test_update_interests_requires_at_least_one_interest(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/interests', ['interests' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['interests']);
    }

    public function test_update_interests_rejects_more_than_10_interests(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/interests', [
                'interests' => array_fill(0, 11, 'slug'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['interests']);
    }

    public function test_update_interests_rejects_non_existent_slugs(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/interests', [
                'interests' => ['does-not-exist'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['interests.0']);
    }

    public function test_update_interests_accepts_valid_slugs(): void
    {
        Interest::create(['name' => 'Music', 'slug' => 'music']);
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/interests', ['interests' => ['music']])
            ->assertOk();
    }
}
