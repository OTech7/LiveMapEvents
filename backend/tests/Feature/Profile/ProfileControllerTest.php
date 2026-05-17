<?php

namespace Tests\Feature\Profile;

use App\Models\Interest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\FeatureTestCase;

class ProfileControllerTest extends FeatureTestCase
{

    private static int $counter = 0;

    private function makeUser(array $attrs = []): User
    {
        return User::create(array_merge([
            'phone' => '+9639170' . str_pad(++self::$counter, 6, '0', STR_PAD_LEFT),
            'first_name' => 'Test',
            'last_name' => 'User',
            'gender' => 'male',
            'profile_complete' => true,
        ], $attrs));
    }

    // ─── GET /profile (show) ──────────────────────────────────────────────────

    public function test_show_returns_authenticated_users_profile(): void
    {
        $user = $this->makeUser(['first_name' => 'Omar']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Omar');
    }

    public function test_show_response_contains_expected_fields(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonStructure([
                'success', 'message', 'data' => ['id', 'first_name', 'last_name'],
            ]);
    }

    public function test_show_does_not_include_interests_when_not_loaded(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/profile')
            ->assertOk();

        // UserResource only includes interests when the relation is eager-loaded
        $this->assertArrayNotHasKey('interests', $response->json('data'));
    }

    // ─── PUT /profile (update) ────────────────────────────────────────────────

    public function test_update_changes_first_name(): void
    {
        $user = $this->makeUser(['first_name' => 'Old']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', ['first_name' => 'New'])
            ->assertOk()
            ->assertJsonPath('data.first_name', 'New');
    }

    public function test_update_changes_gender(): void
    {
        $user = $this->makeUser(['gender' => 'male']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', ['gender' => 'female'])
            ->assertOk()
            ->assertJsonPath('data.gender', 'female');
    }

    public function test_update_does_not_affect_unspecified_fields(): void
    {
        $user = $this->makeUser(['first_name' => 'Omar', 'last_name' => 'Allouni']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', ['first_name' => 'Ahmad'])
            ->assertOk();

        $this->assertSame('Allouni', $user->fresh()->last_name);
    }

    // ─── POST /profile/avatar ─────────────────────────────────────────────────

    public function test_upload_avatar_stores_file_and_returns_url(): void
    {
        Storage::fake('public');

        $user = $this->makeUser();
        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/avatar', ['avatar' => $file])
            ->assertOk()
            ->assertJsonStructure(['data' => ['avatar_url']]);

        $avatarUrl = $user->fresh()->avatar_url;
        $this->assertNotNull($avatarUrl);
        Storage::disk('public')->assertExists($avatarUrl);
    }

    public function test_upload_avatar_rejects_non_image_file(): void
    {
        Storage::fake('public');
        $user = $this->makeUser();
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/avatar', ['avatar' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['avatar']);
    }

    public function test_upload_avatar_rejects_file_larger_than_2mb(): void
    {
        Storage::fake('public');
        $user = $this->makeUser();
        // 3MB image
        $file = UploadedFile::fake()->image('big.jpg')->size(3000);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/avatar', ['avatar' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['avatar']);
    }

    // ─── PUT /profile/discovery-settings ─────────────────────────────────────

    public function test_update_discovery_settings_marks_settings_complete(): void
    {
        $user = $this->makeUser();
        $this->assertFalse($user->discovery_settings_complete);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/discovery-settings', ['radius' => 1000])
            ->assertOk();

        $this->assertTrue($user->fresh()->discovery_settings_complete);
    }

    // ─── GET /profile/interests (myInterests) ─────────────────────────────────

    public function test_my_interests_returns_users_attached_interests(): void
    {
        $interest = Interest::create(['name' => 'Music', 'slug' => 'music']);
        $user = $this->makeUser();
        $user->interests()->attach($interest->id);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/profile/interests')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_my_interests_returns_empty_when_user_has_none(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/profile/interests')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // ─── PUT /profile/interests (updateInterests) ─────────────────────────────

    public function test_update_interests_syncs_interests_by_slug(): void
    {
        Interest::create(['name' => 'Sports', 'slug' => 'sports']);
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/interests', ['interests' => ['sports']])
            ->assertOk();

        $this->assertCount(1, $user->fresh()->interests);
    }

    // ─── POST /profile/interests/{interest} (addInterest) ─────────────────────

    public function test_add_interest_attaches_interest_by_slug(): void
    {
        Interest::create(['name' => 'Art', 'slug' => 'art']);
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/interests/art')
            ->assertOk();

        $this->assertCount(1, $user->fresh()->interests);
    }

    public function test_add_interest_returns_404_for_unknown_slug(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/interests/does-not-exist')
            ->assertNotFound();
    }

    // ─── DELETE /profile/interests/{interest} (removeInterest) ───────────────

    public function test_remove_interest_detaches_interest(): void
    {
        $interest = Interest::create(['name' => 'Gaming', 'slug' => 'gaming']);
        $user = $this->makeUser();
        $user->interests()->attach($interest->id);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/profile/interests/gaming')
            ->assertOk();

        $this->assertCount(0, $user->fresh()->interests);
    }

    public function test_remove_interest_returns_404_for_unknown_slug(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/profile/interests/unknown-slug')
            ->assertNotFound();
    }
}
