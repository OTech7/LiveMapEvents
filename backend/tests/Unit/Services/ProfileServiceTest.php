<?php

namespace Tests\Unit\Services;

use App\Exceptions\ProfileAlreadyCompletedException;
use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProfileService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ProfileService::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeIncompleteUser(array $overrides = []): User
    {
        static $counter = 0;
        return User::create(array_merge([
            'google_id' => 'google-' . ++$counter,
            'profile_complete' => false,
        ], $overrides));
    }

    private function baseProfileData(): array
    {
        return [
            'first_name' => 'Omar',
            'last_name' => 'Allouni',
            'gender' => 'male',
            'dob' => '1995-06-15',
            'lat' => 33.5138,
            'lng' => 36.2765,
            'avatar_url' => null,
        ];
    }

    // ─── completeProfile() ────────────────────────────────────────────────────

    public function test_complete_profile_saves_name_gender_and_dob(): void
    {
        $user = $this->makeIncompleteUser();

        $updated = $this->service->completeProfile($user, $this->baseProfileData());

        $this->assertSame('Omar', $updated->first_name);
        $this->assertSame('Allouni', $updated->last_name);
        $this->assertSame('male', $updated->gender);
        $this->assertSame('1995-06-15', $updated->dob->toDateString());
    }

    public function test_complete_profile_sets_profile_complete_to_true(): void
    {
        $user = $this->makeIncompleteUser();

        $updated = $this->service->completeProfile($user, $this->baseProfileData());

        $this->assertTrue($updated->profile_complete);
    }

    public function test_complete_profile_sets_phone_for_google_user_without_phone(): void
    {
        $user = $this->makeIncompleteUser(['phone' => null]);

        $data = array_merge($this->baseProfileData(), ['phone' => '+963912345678']);

        $updated = $this->service->completeProfile($user, $data);

        $this->assertSame('+963912345678', $updated->phone);
    }

    public function test_complete_profile_does_not_override_existing_phone(): void
    {
        $user = $this->makeIncompleteUser(['phone' => '+963911111111']);

        // Even if phone is passed, it should not overwrite the existing one
        $data = array_merge($this->baseProfileData(), ['phone' => '+963999999999']);

        $updated = $this->service->completeProfile($user, $data);

        $this->assertSame('+963911111111', $updated->phone);
    }

    public function test_complete_profile_uses_existing_avatar_when_none_provided(): void
    {
        $user = $this->makeIncompleteUser(['avatar_url' => 'https://example.com/avatar.png']);

        $data = $this->baseProfileData(); // avatar_url = null

        $updated = $this->service->completeProfile($user, $data);

        $this->assertSame('https://example.com/avatar.png', $updated->avatar_url);
    }

    public function test_complete_profile_saves_provided_avatar_url(): void
    {
        $user = $this->makeIncompleteUser();

        $data = array_merge($this->baseProfileData(), ['avatar_url' => 'https://example.com/new.png']);

        $updated = $this->service->completeProfile($user, $data);

        $this->assertSame('https://example.com/new.png', $updated->avatar_url);
    }

    public function test_complete_profile_throws_if_profile_already_complete(): void
    {
        $user = $this->makeIncompleteUser(['profile_complete' => true]);

        $this->expectException(ProfileAlreadyCompletedException::class);

        $this->service->completeProfile($user, $this->baseProfileData());
    }

    // ─── updateProfile() ──────────────────────────────────────────────────────

    public function test_update_profile_updates_first_name(): void
    {
        $user = User::create([
            'phone' => '+963911000001',
            'first_name' => 'Old',
            'profile_complete' => true,
        ]);

        $updated = $this->service->updateProfile($user, ['first_name' => 'New']);

        $this->assertSame('New', $updated->first_name);
    }

    public function test_update_profile_does_not_clear_fields_not_in_payload(): void
    {
        $user = User::create([
            'phone' => '+963911000002',
            'first_name' => 'Omar',
            'last_name' => 'Allouni',
            'profile_complete' => true,
        ]);

        $updated = $this->service->updateProfile($user, ['first_name' => 'Ahmad']);

        // last_name must remain untouched
        $this->assertSame('Allouni', $updated->last_name);
    }

    // ─── isProfileComplete() ──────────────────────────────────────────────────

    public function test_is_profile_complete_returns_true_when_required_fields_are_set(): void
    {
        $user = $this->makeIncompleteUser();

        // completeProfile sets location + profile_complete; we do it via service
        $this->service->completeProfile($user, $this->baseProfileData());

        $this->assertTrue($this->service->isProfileComplete($user->fresh()));
    }

    public function test_is_profile_complete_returns_false_when_first_name_missing(): void
    {
        $user = User::create([
            'phone' => '+963911000003',
            'last_name' => 'Allouni',
            'gender' => 'male',
            'dob' => '1995-06-15',
        ]);

        $this->assertFalse($this->service->isProfileComplete($user));
    }

    public function test_is_profile_complete_returns_false_for_brand_new_user(): void
    {
        $user = User::create(['phone' => '+963911000004']);

        $this->assertFalse($this->service->isProfileComplete($user));
    }

    // ─── updateDiscoverySettings() ────────────────────────────────────────────

    public function test_update_discovery_settings_marks_settings_as_complete(): void
    {
        $user = User::create(['phone' => '+963911000005']);
        $this->assertFalse($user->discovery_settings_complete);

        $this->service->updateDiscoverySettings($user, [
            'radius' => 2000,
            'notifications' => true,
        ]);

        $this->assertTrue($user->fresh()->discovery_settings_complete);
    }
}
